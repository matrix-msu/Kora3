<?php namespace App\Http\Controllers;

use App\ComboListField;
use App\Field;
use App\FieldHelpers\UploadHandler;
use App\GeolocatorField;
use App\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ImportMultiFormController extends Controller { //TODO::CASTLE

    /*
    |--------------------------------------------------------------------------
    | Import Multi Form Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the import process for importing records into multiple
    | Forms
    |
    */

    /**
     * @var string - Valid formats for record import
     */
    const JSON = "JSON";
    const XML = "XML";
    const CSV = "CSV";

    /**
     * Constructs controller and makes sure user is authenticated.
     */
    public function __construct() {
        $this->middleware('auth');
        $this->middleware('active');
    }

    /**
     * Gets the view for the multi-form record import process.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @return View
     */
    public function index($pid) {
        if(!ProjectController::validProj($pid))
            return redirect('projects')->with('k3_global_error', 'project_invalid');

        $project = ProjectController::getProject($pid);

        if(!\Auth::user()->isProjectAdmin($project))
            return redirect('projects')->with('k3_global_error', 'not_project_admin');

        //Clear import directory
        $dir = storage_path('app/tmpFiles/MFf0u'.\Auth::user()->id);
        if(file_exists($dir)) {
            //clear import directory
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach($files as $file) {
                // Skip directories (they would be added automatically)
                if(!$file->isDir())
                    unlink($file);
            }
        } else {
            mkdir($dir, 0775, true);
        }

        $formObjs = $project->forms()->get();
        $forms = [];
        foreach($formObjs as $obj) {
            $forms[$obj->id] = $obj->name;
        }

        return view('projects.importMF',compact('project','forms'));
    }

    /**
     * Saves a temporary version of an uploaded file.
     *
     * @param  Request $request
     */
    public function saveTmpFile() {
        $uid = \Auth::user()->id;

        $options = array();
        $options['fid'] = 0;
        $options['flid'] = 0;
        $options['folder'] = 'MFf0u'.$uid;

        $upload_handler = new UploadHandler($options);
    }

    /**
     * Removes a temporary file for a multi form record import.
     *
     * @param  string $name - Name of the file to delete
     * @param  Request $request
     */
    public function delTmpFile($filename) {
        $uid = \Auth::user()->id;

        $options = array();
        $options['fid'] = 0;
        $options['flid'] = 0;
        $options['filename'] = $filename;
        $options['folder'] = 'MFf0u'.$uid;
        $options['deleteThat'] = true;

        $upload_handler = new UploadHandler($options);
    }

    /**
     * Begin the import process.
     *
     * @param  int $pid - Project ID
     * @param  Request $request
     */
    public function beginImport($pid, Request $request) {
        $project = ProjectController::getProject($pid);

        if(!\Auth::user()->isProjectAdmin($project))
            return redirect('projects')->with('k3_global_error', 'not_project_admin');

        //if zip file
        if(!is_null($request->file('files'))) {
            $zip = new \ZipArchive();
            $res = $zip->open($request->file('files'));
            if($res) {
                $dir = storage_path('app/tmpFiles/impU'.\Auth::user()->id);
                if(file_exists($dir)) {
                    //clear import directory
                    $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($dir),
                        \RecursiveIteratorIterator::LEAVES_ONLY
                    );
                    foreach($files as $file) {
                        // Skip directories (they would be added automatically)
                        if(!$file->isDir())
                            unlink($file);
                    }
                }
                $zip->extractTo($dir.'/');
                $zip->close();
            }
        }

        //The forms we will import to
        $fids = json_decode($request->importForms);
        $order = json_decode($request->formOrder);
        //The record file for each form
        $recordSets = json_decode($request->records);
        //The type of file for each form
        $fileTypes = json_decode($request->types);

        $response = [];
        if(sizeof($fids) != sizeof($recordSets))
            return response()->json(["status"=>false,"message"=>"file_form_mismatch"],500);

        for($i=0;$i<sizeof($fids);$i++) {
            $data = [];

            $fid = $fids[$order[$i]];
            $records = storage_path('app/tmpFiles/MFf0u'.\Auth::user()->id.'/'.$recordSets[$i]);
            $type = strtoupper($fileTypes[$i]);

            $tagNames = $recordObjs = array();

            switch($type) {
                case self::XML:
                    $xml = simplexml_load_file($records);

                    foreach($xml->children() as $record) {
                        array_push($recordObjs, $record->asXML());
                        foreach($record->children() as $fields) {
                            array_push($tagNames, $fields->getName());
                        }
                    }

                    break;
                case self::JSON:
                    $json = json_decode(file_get_contents($records), true);

                    foreach($json as $kid => $record) {
                        $recordObjs[$kid] = $record;
                        foreach(array_keys($record) as $field) {
                            array_push($tagNames, $field);
                        }
                    }

                    break;
                case self::CSV:
                    $csv = parseCSV($records);

                    foreach($csv as $kid => $record) {
                        $recordObjs[$kid] = $record;
                        foreach(array_keys($record) as $field) {
                            array_push($tagNames, $field);
                        }
                    }

                    break;
            }

            $form = FormController::getForm($fid);
            $tagNames = array_unique($tagNames);

            $fields = $form->layout['fields'];
            $table = '<div class="get-fid-js" fid="'.$fid.'">';

            //Then build the field matchups
            foreach($fields as $flid => $field) {
                $table .= '<div class="form-group mt-xl half">';
                $table .= '<div class="solid-box get-slug-js" slug="'.$flid.'">';
                $table .= $field['name'].' ('.$flid.')';
                $table .= '</div></div>';
                $table .= '<div class="form-group mt-xl half">';
                $table .= '<select class="single-select get-tag-js" data-placeholder="Select field if applicable">';
                $table .= '<option></option>';
                foreach($tagNames as $name) {
                    // Matching three different naming conventions
                    if(
                        $flid==$name |
                        $flid==str_replace(' ', '_', $name) |
                        $flid==$field['name']
                    )
                        $table .= '<option val="'.$name.'" selected>' . $name . '</option>';
                    else
                        $table .= '<option val="'.$name.'">'.$name.'</option>';
                }
                $table .= '</select>';
                $table .= '</div>';
                $table .= '<div class="form-group"></div>';
            }

            //For reverse associations
            $table .= '<div class="form-group mt-xl half">';
            $table .= '<div class="solid-box get-slug-js" slug="reverseAssociations">reverseAssociations</div></div>';
            $table .= '<div class="form-group mt-xl half">';
            $table .= '<select class="single-select get-tag-js" data-placeholder="Select field if applicable">';
            $table .= '<option></option>';
            foreach($tagNames as $name) {
                if($name == "reverseAssociations")
                    $table .= '<option val="'.$name.'" selected>' . $name . '</option>';
                else
                    $table .= '<option val="'.$name.'">'.$name.'</option>';
            }
            $table .= '</select>';
            $table .= '</div>';
            $table .= '<div class="form-group"></div>';
            $table .= '</div>';

            $data['records'] = $recordObjs;
            $data['type'] = $type;
            $data['matchup'] = $table;

            $response[$fid] = $data;
        }

        return $response;
    }

    /**
     * Import Kora 3 records via XML of JSON file. We will leave field specific stuff here because it's too specific.
     * There are some things here that are specific to MF record import, specifically associator related stuff.
     *
     * @param  int $pid - Project ID
     * @param  Request $request
     */
    public function importRecord($pid, Request $request) {
        $fid = $request->fid;
        $form = FormController::getForm($fid);

        if(!(\Auth::user()->isFormAdmin($form)))
            return redirect('projects/'.$pid)->with('k3_global_error', 'not_form_admin');

        $record = $request->record;

        $recRequest = new Request();
        $recRequest['userId'] = \Auth::user()->id;
        $recRequest['api'] = true;

        $matchup = $request->table[$fid];
        $matchup['KORA ID CONNECTION'] = 'connection';

        $assocTag = null;
        $assocArray = [];

        if($request->type==self::XML) {
            $record = simplexml_load_string($record);

            $originKid = $record->attributes()->kid;
            $originRid = null;
            if(!is_null($originKid)) {
                if(Record::isKIDPattern($originKid))
                    $originRid = explode('-', $originKid)[2];

                $assocTag = (string)$originKid;
            }

            foreach($record->children() as $key => $field) {
                //If value is not set, we assume no value so move on
                if($field->count() == 0 && (string)$field == '')
                    continue;

                //Deal with reverse associations and move on
                if($matchup[$key] == 'reverseAssociations') {
                    if(empty($field->Record))
                        return response()->json(["status"=>false,"message"=>"xml_validation_error",
                            "record_validation_error"=>[$request->kid => "$key format is incorrect for applying reverse associations"]],500);
                    $rFinal = [];
                    foreach($field->Record as $rAssoc) {
                        $rFinal[(string)$rAssoc['flid']][] = (string)$rAssoc;
                    }
                    $recRequest['newRecRevAssoc'] = $rFinal;
                    continue;
                }

                // TODO::this has to be tested still
                if($matchup[$flid] == 'connection') {
                    $recRequest['connection'] = (string)$field;
                    continue;
                }

                $flid = $matchup[$key];
                if(!isset($form->layout['fields'][$flid]))
                    return response()->json(["status"=>false,"message"=>"xml_validation_error",
                        "record_validation_error"=>[$request->kid => "Invalid provided field, $flid"]],500);

                $fieldMod = $form->layout['fields'][$flid];
                $typedField = $form->getFieldModel($fieldMod['type']);
                $recRequest = $typedField->processImportDataXML($flid,$fieldMod,$field,$recRequest);
            }
        } else if($request->type==self::JSON | $request->type==self::CSV) {
            $originKid = $request->kid;
            $originRid = null;
            if(!is_null($originKid)) {
                if(Record::isKIDPattern($originKid))
                    $originRid = explode('-', $originKid)[2];

                $assocTag = $originKid;
            }

            foreach($record as $slug => $field) {
                //Just in case there are extra/unused fields in the JSON
                if(!array_key_exists($slug,$matchup))
                    continue;

                //If value is not set, move on
                if(!$field | is_null($field))
                    continue;

                //Deal with reverse associations and move on
                if($slug == 'reverseAssociations') {
                    $recRequest['newRecRevAssoc'] = $field;
                    continue;
                }

                 //Deal with reverse associations and move on
                if($matchup[$slug] == 'reverseAssociations') {
                    $recRequest['newRecRevAssoc'] = $field;
                    continue;
                }

                //Kora id connection for associator
                if($matchup[$slug] == 'connection') {
                    $recRequest['connection'] = $field;
                    continue;
                }

                $flid = $matchup[$slug];

                $fieldMod = $form->layout['fields'][$flid];
                $typedField = $form->getFieldModel($fieldMod['type']);
                $recRequest = $typedField->processImportData($flid,$fieldMod,$field,$recRequest);
            }
        }

        $recCon = new RecordController();
        $result = $recCon->store($pid,$fid,$recRequest);

        $resData = $result->getData(true);
        $result->setData($resData);

        return $result;
    }

    /**
     * After all the records are built, we connect records together via associated fields using the identifier list
     * we've built.
     *
     * @param  int $pid - Project ID
     * @param  Request $request
     */
    public function crossFormAssociations($pid, Request $request) {
        $project = ProjectController::getProject($pid);

        if(!\Auth::user()->isProjectAdmin($project))
            return redirect('projects')->with('k3_global_error', 'not_project_admin');

        foreach($request->fids as $fid) {
            $form = FormController::getForm($fid);
            if (
                $request->has('assocTagConvert') &&
                $request->has('crossFormAssoc') &&
                array_key_exists($fid, $request->assocTagConvert) &&
                array_key_exists($fid, $request->crossFormAssoc)
            ) {
                //Conversion of record tag identifiers to KIDs
                $assocTagConvert = $request->assocTagConvert[$fid];

                //Actual associator field data to convert
                $crossFormAssoc = $request->crossFormAssoc[$fid];
                foreach($crossFormAssoc as $kid => $data) {
                    $record = RecordController::getRecord($kid);

                    if ($record) {
                        foreach($data as $flid => $akids) {
                            //Get values
                            $values = array();
                            foreach($akids as $tag) {
                                array_push($values,$assocTagConvert->{$tag});
                            }

                            $record->{$flid} = json_encode($values);
                        }
                    }
                }
            }

            if (
                $request->has('kids') &&
                $request->has('connections') &&
                array_key_exists($fid, $request->kids) &&
                array_key_exists($fid, $request->connections)
            ) {
                    //Single form assoc to new record
                $kids = $request->kids[$fid];
                $connections = $request->connections[$fid];

                $fieldsArray = $form->layout['fields'];

                $assocField = array();
                foreach ($fieldsArray as $flid => $field) {
                    if($field['type'] == \App\Form::_ASSOCIATOR)
                        $assocField[$flid] = $field;
                }

                if($assocField) {
                    foreach($kids as $kid) {
                        $record = RecordController::getRecord($kid);
                        $key = key($assocField);
                        $assoc = json_decode($record->{$key});
                        if($assoc) {
                            foreach ($connections as $connection => $kid) {
                                for($i=0;$i<count($assoc);$i++) {
                                    if($assoc[$i] == $connection) {
                                        $assoc[$i] = $kid;
                                    }
                                }
                            }
                            $record->{$key} = json_encode($assoc);
                            $record->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * Downloads the file with all the failed records.
     *
     * @param  int $pid - Project ID
     * @param  Request $request
     */
    public function downloadFailedRecords($pid, Request $request) {
        $failedRecords = json_decode($request->failures);
        $project = ProjectController::getProject($pid);

        if($request->type=='JSON' | $request->type=='CSV')
            $records = [];
        else if($request->type=='XML')
            $records = '<?xml version="1.0" encoding="utf-8"?><Records>';

        foreach($failedRecords as $element) {
            if($request->type=='JSON' | $request->type=='CSV')
                $records[$element[0]] = $element[1];
            else if($request->type=='XML')
                $records .= $element[1];
        }

        if($request->type=='JSON'  | $request->type=='CSV') {
            header("Content-Disposition: attachment; filename=" . $project->name . '_failedImports.json');
            header("Content-Type: application/octet-stream; ");

            echo json_encode($records);
            exit;
        }
        else if($request->type=='XML') {
            $records .= '</Records>';

            header("Content-Disposition: attachment; filename=" . $project->name . '_failedImports.xml');
            header("Content-Type: application/octet-stream; ");

            echo $records;
            exit;
        }
    }

    /**
     * Downloads the file with the reasons why records failed.
     *
     * @param  int $pid - Project ID
     * @param  Request $request
     */
    public function downloadFailedReasons($pid, Request $request) {
        $failedRecords = json_decode($request->failures);
        $project = ProjectController::getProject($pid);

        $messages = [];

        foreach($failedRecords as $element) {
            $id = $element[0];
            if(isset($element[2]->responseJSON->record_validation_error)) {
                $messageArray = $element[2]->responseJSON->record_validation_error;
                foreach($messageArray as $message) {
                    if($message != '' && $message != ' ')
                        $messages[$id] = $message;
                }
            } else {
                $messages[$id] = "Unable to determine error. This is usually caused by a structure issue in your XML/JSON, or an unexpected bug in Kora3.";
            }
        }

        header("Content-Disposition: attachment; filename=" . $project->name . '_importExplain.json');
        header("Content-Type: application/octet-stream; ");

        echo json_encode($messages);
        exit;
    }

}
