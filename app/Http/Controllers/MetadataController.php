<?php namespace App\Http\Controllers;

Use App\Metadata;
Use App\Field;
Use App\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class MetadataController extends Controller {

    /*
    |--------------------------------------------------------------------------
    | Metadata Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles assigning/removing metadata for a field, as well as
    | displaying all records with their field's metadata, and changing the visibility
    | of metadata to the public, and mass assigning metadata
    |
    */

    /**
     * Constructs controller and makes sure user is authenticated.
     */
    public function __construct() {
        $this->middleware('auth', ['except'=>'records']);
        $this->middleware('active', ['except'=>'records']);
    }

    /**
     * Gets all records in the form and their RDF XML output.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @return string - html for file download
     */
    public function records2($pid, $fid) {
        if(!FormController::validProjForm($pid, $fid))
            return redirect('projects/'.$pid)->with('k3_global_error', 'form_invalid');

        $rids = DB::table("records")->where("fid", "=", $fid)->select("rid")->get();

        // The DB call returns an array of StdObj so we get the rids out of the objects.
        $rids = array_map( function($obj) {
            return $obj->rid;
        }, $rids);

        $eC = new ExportController();
//        $output_file = $eC->exportWithRids($rids,'META');
//
//        if($output_file=='no_records')
//            echo "There are no metadata records to export";
//
//        if(file_exists($output_file)) {
//            header("Content-Disposition: attachment; filename=\"" . basename($output_file) . "\"");
//            header("Content-Type: application/rdf+xml");
//            header("Content-Length: " . filesize($output_file));
//
//            readfile($output_file);
//            exit;
//        }
    }

    /**
     * Gets individual record using the value of the form's primary index.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  string $resource - Index text value
     * @return string - html for file download
     */
    public function singleRecord($pid, $fid, $resource) {
        if(!FormController::validProjForm($pid, $fid))
            return redirect('projects/'.$pid)->with('k3_global_error', 'form_invalid');

        //Get the record id of the text field whose primary index is equal to the resource
        $rids = DB::table("text_fields")->where("fid", "=", $fid)->where("text","=",$resource)->select("rid")->get();

        // The DB call returns an array of StdObj so we get the rids out of the objects.
        $rids = array_map( function($obj) {
            return $obj->rid;
        }, $rids);

        $eC = new ExportController();
//        $output_file = $eC->exportWithRids($rids,'META');
//
//        if($output_file=='no_records')
//            echo "There are no metadata records to export";
//
//        if(file_exists($output_file)) {
//            header("Content-Disposition: attachment; filename=\"" . basename($output_file) . "\"");
//            header("Content-Type: application/rdf+xml");
//            header("Content-Length: " . filesize($output_file));
//
//            readfile($output_file);
//            exit;
//        }
    }

    /**
     * Gets view for Linked Open Data management page.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @return View
     */
    public function index($pid,$fid) {
        if(!FormController::validProjForm($pid, $fid))
            return redirect('projects/'.$pid)->with('k3_global_error', 'form_invalid');

        //Fields that already have metadata do not get sent to the view to be listed
        $all_fields = Field::where('pid',$pid)->where('fid',$fid)->get();
        $available_fields = new \Illuminate\Support\Collection; //fields without a tag
        $assigned_fields = array(); //fields with a tag
        foreach($all_fields as $field) {
            if($field->metadata()->first() !== null)
                array_push($assigned_fields,$field);
            else
                $available_fields->push($field);
        }

        $fields = $available_fields->pluck('name','flid');
        $form = Form::find($fid);

        $resource_title = $form->lod_resource;

        return view('metadata.index',compact('pid','fid','form','fields','assigned_fields','resource_title'));
    }

    /**
     * Saves/updates existing metadata info for a form or changes metadata visibility.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     * @return mixed
     */
    public function store($pid, $fid, Request $request) {
        //Changing metadata visibility or adding metadata to a field?
        $this->validate($request,[
            'type' => 'required',
        ]);

        if($request->input('type')=='visibility') {
            //Make the metadata public or private
            $form = Form::find($fid);

            //Couple checks to make sure things are set up right
            $resourceTitle = $form->lod_resource;
            if(is_null($resourceTitle) || $resourceTitle=='')
                return response()->json(["status"=>false,"message"=>"resource_title_missing"],500);

            $primeIndex = Metadata::where('fid','=',$fid)->where('primary','=',1)->get()->count();
            if($primeIndex!=1)
                return response()->json(["status"=>false,"message"=>"primary_index_missing"],500);

            if($request->input('state') == 'true')
                $form->public_metadata = true;
            else
                $form->public_metadata = false;
            $form->save();

            return response()->json(["status"=>true,"message"=>"metadata_created"],200);
        } else if($request->input('type')=='addmetadata') {
            //Add metadata to a field
            $this->validate($request,[
                'name' => 'required',
                'field' => 'required|unique:metadatas,flid', //field can only have 1 metadata
            ]);

            if(!$this->isUniqueToForm($fid,$request->input('name')))
                return redirect()->back()->with('k3_global_error', 'resource_name_inuse');

            $field = Field::where('pid',$pid)->where('fid',$fid)->where('flid','=',$request->input('field'))->first();
            $metadata = new Metadata(['pid'=>$pid,'fid'=>$fid, 'name'=>$request->input('name')]);
            $metadata->primary = 0;
            $metadata->field()->associate($field);
            $field->metadata()->save($metadata);

            //Laravel form submission needs this
            return redirect()->action('MetadataController@index',compact('pid','fid'))
                ->with('k3_global_success', 'resource_created');
        }
    }

    /**
     * Update the resource title for the form.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     * @return Redirect
     */
    public function updateResource($pid, $fid, Request $request) {
        $form = FormController::getForm($fid);
        $title = $request->title;

        $form->lod_resource = $title;
        $form->save();

        //Laravel form submission needs this
        return redirect()->action('MetadataController@index',compact('pid','fid'))
            ->with('k3_global_success', 'resource_updated');
    }

    /**
     * Make a textfield the primary index value for metadata.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     */
    public function makePrimary($pid, $fid, Request $request) {
        $metadatas = Metadata::where('fid','=',$fid)->get();
        $pFlid = $request->flid;

        foreach($metadatas as $meta) {
            if($meta->flid==$pFlid)
                $meta->primary = 1;
            else
                $meta->primary = 0;

            $meta->save();
        }
    }

    /**
     * Delete a field from use in metadata.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     * @return JsonResponse
     */
    public function destroy($pid,$fid,Request $request) {
        $meta = Field::find($request->input('flid'))->metadata()->first();
        if($meta !== null)
            $meta->delete();
        return response()->json(["status"=>true,"message"=>"resource_deleted"],200);

    }

    /**
     * Check if metadata name is unique to form.
     *
     * @param  int $fid - Form ID
     * @param  string $name - Name to check
     * @return bool - Result of the check
     */
    public function isUniqueToForm($fid, $name) {
        $count = Metadata::where("fid", "=", $fid)
            ->where("name", "=", $name)
            ->count();

        return $count == 0;
    }

    /**
     * Mass assigns metadata names to each field in the form based on its nickname.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     */
    public function massAssign($pid, $fid) {
        $fields = Field::where("fid", "=", $fid)->get();
        foreach($fields as $field) {
            if(is_null($field->metadata()->first())) { // Only associate a new metadata if it does not exist already.
                if($this->isUniqueToForm($fid,$field->name)) {
                    $metadata = new Metadata(['pid'=>$pid,'fid'=>$fid, 'name'=>$field->name]);
                } else {
                    if($this->isUniqueToForm($fid,$field->name."_".$field->slug)) {
                        $metadata = new Metadata(['pid'=>$pid,'fid'=>$fid, 'name'=>$field->name."_".$field->slug]);
                    } else {
                        $count = 0;
                        $name = $field->name."_".$field->slug."0";
                        while(!$this->isUniqueToForm($fid,$name)) {
                            $name = $field->name."_".$field->slug.$count;
                            $count++;
                        }
                        $metadata = new Metadata(['pid'=>$pid,'fid'=>$fid, 'name'=>$name]);
                    }
                }

                $metadata->field()->associate($field);
                $field->metadata()->save($metadata);
            }
        }
    }

    //case self::META: //TODO:: we will eventually rebuild this
    /*//Check to see if any records in form
    if(sizeof($rids)==0)
        return "no_records";

    //We need one rid from the set to determine the project and form used in this metadata
    $tempRid = $rids[0];
    $tempKid = Record::where('rid','=',$tempRid)->first()->kid;
    $kidParts = explode('-',$tempKid);

    $resourceTitle = Form::where('fid','=',$kidParts[1])->first()->lod_resource;
    $metaUrl = url("projects/".$kidParts[0]."/forms/".$kidParts[1]."/metadata/public#");

    $records = '<?xml version="1.0"?><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" ';
    $records .= 'xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#" ';
    $records .= "xmlns:$resourceTitle=\"$metaUrl\">";
    $recordData = [];

    foreach($chunks as $chunk) {
        $datafields = self::getDataRows($chunk);

        foreach($datafields as $data) {
            $kid = $data->pid.'-'.$data->fid.'-'.$data->rid;
            $metaObj = Metadata::where('flid','=',$data->flid)->first();
            if(is_null($metaObj))
                continue;

            $metaFieldName = $metaObj->name;

            if($data->type==Field::_ASSOCIATOR)
                $fieldxml = "<".$resourceTitle.":".$metaFieldName.">";
            else
                $fieldxml = "<".$resourceTitle.":".$metaFieldName." rdf:parseType=\"Collection\">";

            switch($data->type) {
                case Field::_TEXT:
                    $fieldxml .= htmlspecialchars($data->value, ENT_XML1, 'UTF-8');
                    break;
                case Field::_NUMBER:
                    $fieldxml .= htmlspecialchars((float)$data->value, ENT_XML1, 'UTF-8');
                    break;
                case Field::_LIST:
                    $fieldxml .= htmlspecialchars($data->value, ENT_XML1, 'UTF-8');
                    break;
                case Field::_MULTI_SELECT_LIST:
                    $fieldxml .= '<rdf:Seq>';
                    $opts = explode('[!]',$data->value);
                    foreach($opts as $opt) {
                        $fieldxml .= '<rdf:li>'.htmlspecialchars($opt, ENT_XML1, 'UTF-8').'</rdf:li>';
                    }
                    $fieldxml .= '</rdf:Seq>';
                    break;
                case Field::_GENERATED_LIST:
                    $fieldxml .= '<rdf:Seq>';
                    $opts = explode('[!]',$data->value);
                    foreach($opts as $opt) {
                        $fieldxml .= '<rdf:li>'.htmlspecialchars($opt, ENT_XML1, 'UTF-8').'</rdf:li>';
                    }
                    $fieldxml .= '</rdf:Seq>';
                    break;
                case Field::_DATE:
                    $info = "";
                    if($data->value==1)
                        $info .= 'circa ';
                    if($data->val2!="")
                        $info .= date("F", mktime(0, 0, 0, $data->val2, 10)).' ';
                    if($data->val3!="")
                        $info .= $data->val3.' ';
                    if($data->val4!="")
                        $info .= $data->val4.' ';
                    if($data->val5!="")
                        $info .= $data->val5.' ';

                    $fieldxml .= htmlspecialchars(trim($info), ENT_XML1, 'UTF-8');
                    break;
                case Field::_GEOLOCATOR:
                    $fieldxml .= '<rdf:Seq>';

                    $latlon = explode('[!latlon!]',$data->val3);
                    $desc = explode('[!]',$data->value);
                    $cnt = sizeof($desc);

                    for($i=0;$i<$cnt;$i++) {
                        $ll = explode('[!]',$latlon[$i]);
                        $lat = "<geo:lat>".$ll[0]."</geo:lat>";
                        $long = "<geo:long>".$ll[1]."</geo:long>";
                        $fieldxml .= "<geo:Point>".$lat.$long."</geo:Point>";
                    }
                    $fieldxml .= '</rdf:Seq>';
                    break;
                case Field::_ASSOCIATOR:
                    $aRecs = explode(',',$data->value);

                    foreach($aRecs as $aRec) {
                        $aKidParts = explode('-',$aRec);

                        $aPrimary = Metadata::where('fid','=',$aKidParts[1])->where('primary','=',1)->first()->flid;
                        $aResourceIndexValue = TextField::where('flid','=',$aPrimary)->where('rid','=',$aKidParts[2])->first()->text;

                        $fieldxml .= "<rdf:Description rdf:about=\""
                            .url("projects/".$aKidParts[0]."/forms/".$aKidParts[1]."/metadata/public/$aResourceIndexValue")."\" />";
                    }
                    break;
                default:
                    break;
            }

            $fieldxml .= "</".$resourceTitle.":".$metaFieldName.">";

            if(isset($recordData[$kid]))
                $recordData[$kid] .= $fieldxml;
            else
                $recordData[$kid] = $fieldxml;
        }
    }

    //Now we have an array of kids to their field data
    //We need to loop back and add them to the xml
    foreach($recordData as $kid => $data) {
        $records .= "<rdf:Description ";

        $parts = explode('-',$kid);
        $primary = Metadata::where('fid','=',$parts[1])->where('primary','=',1)->first()->flid;
        $resourceIndexValue = TextField::where('flid','=',$primary)->where('rid','=',$parts[2])->first()->text;

        $records .= "rdf:about=\"".url("projects/".$parts[0]."/forms/".$parts[1]."/metadata/public/".$resourceIndexValue)."\">";
        $records .= "$data</rdf:Description>";
    }

    $records .= '</rdf:RDF>';

    if($dataOnly) {
        return $records;
    } else {
        $dt = new \DateTime();
        $format = $dt->format('Y_m_d_H_i_s');
        $path = storage_path("app/exports/record_export_$format.rdf");

        file_put_contents($path, $records);

        return $path;
    }
    break;*/
}