<?php namespace App;

use App\Http\Controllers\FieldController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModelField extends FileTypeField  {

    const FIELD_OPTIONS_VIEW = "fields.options.3dmodel";
    const FIELD_ADV_OPTIONS_VIEW = "partials.field_option_forms.3dmodel";

    protected $fillable = [
        'rid',
        'flid',
        'model'
    ];

    public function getFieldOptionsView(){
        return self::FIELD_OPTIONS_VIEW;
    }

    public function getAdvancedFieldOptionsView(){
        return self::FIELD_ADV_OPTIONS_VIEW;
    }

    public function getDefaultOptions(Request $request){
        return '[!FieldSize!]0[!FieldSize!][!MaxFiles!]1[!MaxFiles!][!FileTypes!][!FileTypes!]';
    }

    public function updateOptions($field, Request $request, $return=true) {
        $filetype = $request->filetype[0];
        for($i=1;$i<sizeof($request->filetype);$i++){
            $filetype .= '[!]'.$request->filetype[$i];
        }

        if($request->filesize==''){
            $request->filesize = 0;
        }

        $field->updateRequired($request->required);
        $field->updateSearchable($request);
        $field->updateOptions('FieldSize', $request->filesize);
        $field->updateOptions('FileTypes', $filetype);

        if($return) {
            flash()->overlay(trans('controller_field.optupdate'), trans('controller_field.goodjob'));
            return redirect('projects/' . $field->pid . '/forms/' . $field->fid . '/fields/' . $field->flid . '/options');
        } else {
            return '';
        }
    }

    public function createNewRecordField($field, $record, $value, $request){
        if(glob(env('BASE_PATH') . 'storage/app/tmpFiles/' . $value . '/*.*') != false){
            $this->flid = $field->flid;
            $this->rid = $record->rid;
            $this->fid = $field->fid;
            $infoString = '';
            $infoArray = array();
            $newPath = env('BASE_PATH') . 'storage/app/files/p' . $field->pid . '/f' . $field->fid . '/r' . $record->rid . '/fl' . $field->flid;
            mkdir($newPath, 0775, true);
            if (file_exists(env('BASE_PATH') . 'storage/app/tmpFiles/' . $value)) {
                $types = self::getMimeTypes();
                foreach (new \DirectoryIterator(env('BASE_PATH') . 'storage/app/tmpFiles/' . $value) as $file) {
                    if ($file->isFile()) {
                        if (!array_key_exists($file->getExtension(), $types))
                            $type = 'application/octet-stream';
                        else
                            $type = $types[$file->getExtension()];
                        $info = '[Name]' . $file->getFilename() . '[Name][Size]' . $file->getSize() . '[Size][Type]' . $type . '[Type]';
                        $infoArray[$file->getFilename()] = $info;
                        copy(env('BASE_PATH') . 'storage/app/tmpFiles/' . $value . '/' . $file->getFilename(),
                            $newPath . '/' . $file->getFilename());
                    }
                }
                foreach($request->input('file'.$field->flid) as $fName){
                    if($fName!=''){
                        if ($infoString == '') {
                            $infoString = $infoArray[$fName];
                        } else {
                            $infoString .= '[!]' . $infoArray[$fName];
                        }
                    }
                }
            }
            $this->model = $infoString;
            $this->save();
        }
    }

    public function editRecordField($value, $request) {
        if(glob(env('BASE_PATH').'storage/app/tmpFiles/'.$value.'/*.*') != false){
            $mod_files_exist = false; // if this remains false, then the files were deleted and row should be removed from table

            //clear the old files before moving the update over
            //we only want to remove files that are being replaced by new versions
            //we keep old files around for revision purposes
            $newNames = array();
            //scan the tmpFile as these will be the "new ones"
            if(file_exists(env('BASE_PATH') . 'storage/app/tmpFiles/' . $value)) {
                foreach (new \DirectoryIterator(env('BASE_PATH') . 'storage/app/tmpFiles/' . $value) as $file) {
                    array_push($newNames,$file->getFilename());
                }
            }
            //actually clear them
            $field = FieldController::getField($this->flid);
            foreach (new \DirectoryIterator(env('BASE_PATH').'storage/app/files/p'.$field->pid.'/f'.$field->fid.'/r'.$this->rid.'/fl'.$field->flid) as $file) {
                if ($file->isFile() and in_array($file->getFilename(),$newNames)) {
                    unlink(env('BASE_PATH').'storage/app/files/p'.$field->pid.'/f'.$field->fid.'/r'.$this->rid.'/fl'.$field->flid.'/'.$file->getFilename());
                }
            }
            //build new stuff
            $infoString = '';
            $infoArray = array();
            if(file_exists(env('BASE_PATH') . 'storage/app/tmpFiles/' . $value)) {
                $types = self::getMimeTypes();
                foreach (new \DirectoryIterator(env('BASE_PATH') . 'storage/app/tmpFiles/' . $value) as $file) {
                    if ($file->isFile()) {
                        if(!array_key_exists($file->getExtension(),$types))
                            $type = 'application/octet-stream';
                        else
                            $type =  $types[$file->getExtension()];
                        $info = '[Name]' . $file->getFilename() . '[Name][Size]' . $file->getSize() . '[Size][Type]' . $type . '[Type]';
                        $infoArray[$file->getFilename()] = $info;
                        copy(env('BASE_PATH') . 'storage/app/tmpFiles/' . $value . '/' . $file->getFilename(),
                            env('BASE_PATH').'storage/app/files/p'.$field->pid.'/f'.$field->fid.'/r'.$this->rid.'/fl'.$field->flid . '/' . $file->getFilename());
                        $mod_files_exist = true;
                    }
                }
                foreach($request->input('file'.$field->flid) as $fName){
                    if($fName!=''){
                        if ($infoString == '') {
                            $infoString = $infoArray[$fName];
                        } else {
                            $infoString .= '[!]' . $infoArray[$fName];
                        }
                    }
                }
            }
            $this->model = $infoString;
            $this->save();

            if(!$mod_files_exist){
                $this->delete();
            }
        }
    }

    public function massAssignRecordField($field, $record, $formFieldValue, $request, $overwrite=0) {
        //TODO::mass assign
    }

    public function createTestRecordField($field, $record){
        $this->flid = $field->flid;
        $this->rid = $record->rid;
        $this->fid = $field->fid;
        $newPath = env('BASE_PATH') . 'storage/app/files/p' . $field->pid . '/f' . $field->fid . '/r' . $record->rid . '/fl' . $field->flid;
        mkdir($newPath, 0775, true);

        $types = self::getMimeTypes();
        if (!array_key_exists('stl', $types))
            $type = 'application/octet-stream';
        else
            $type = $types['stl'];
        $infoString = '[Name]model.stl[Name][Size]9484[Size][Type]' . $type . '[Type]';
        copy(env('BASE_PATH') . 'public/testFiles/model.stl',
            $newPath . '/model.stl');

        $this->model = $infoString;
        $this->save();
    }

    public function validateField($field, $value, $request) {
        $req = $field->required;

        if($req==1){
            if(glob(env('BASE_PATH').'storage/app/tmpFiles/'.$value.'/*.*') == false)
                return $field->name.trans('fieldhelpers_val.file');
        }
    }

    public function rollbackField($field, Revision $revision, $exists=true) {
        if (!is_array($revision->data)) {
            $revision->data = json_decode($revision->data, true);
        }

        if (is_null($revision->data[Field::_3D_MODEL][$field->flid]['data'])) {
            return null;
        }

        // If the field doesn't exist or was explicitly deleted, we create a new one.
        if ($revision->type == Revision::DELETE || !$exists) {
            $this->flid = $field->flid;
            $this->fid = $revision->fid;
            $this->rid = $revision->rid;
        }

        $this->model = $revision->data[Field::_3D_MODEL][$field->flid]['data'];
        $this->save();
    }

    public function getRecordPresetArray($data, $exists=true) {
        if ($exists) {
            $data['model'] = $this->model;
        }
        else {
            $data['model'] = null;
        }

        return $data;
    }

    public function getExportSample($slug,$type) {
        switch ($type){
            case "XML":
                $xml = '<' . Field::xmlTagClear($slug) . ' type="3D-Model">';
                $xml .= '<File>';
                $xml .= '<Name>' . utf8_encode('FILENAME') . '</Name>';
                $xml .= '</File>';
                $xml .= '</' . Field::xmlTagClear($slug) . '>';

                return $xml;
                break;
            case "JSON":
                $fieldArray = array('name' => $slug, 'type' => '3D-Model');
                $fieldArray['files'] = array();

                $fileArray = array();
                $fileArray['name'] = 'FILENAME 1';
                array_push($fieldArray['files'], $fileArray);

                return $fieldArray;
                break;
        }

    }

    ///////////////////////////////////////////////END ABSTRACT FUNCTIONS///////////////////////////////////////////////

    public static function setRestfulAdvSearch($data, $field, $request){
        $request->request->add([$field->flid.'_input' => $data->input]);

        return $request;
    }

    public static function setRestfulRecordData($field, $flid, $recRequest, $uToken){
        $files = array();
        $currDir = env('BASE_PATH') . 'storage/app/tmpFiles/impU' . $uToken;
        $newDir = env('BASE_PATH') . 'storage/app/tmpFiles/f' . $flid . 'u' . $uToken;
        if(file_exists($newDir)) {
            foreach(new \DirectoryIterator($newDir) as $file) {
                if ($file->isFile())
                    unlink($newDir . '/' . $file->getFilename());
            }
        } else {
            mkdir($newDir, 0775, true);
        }
        foreach($field->files as $file) {
            $name = $file->name;
            //move file from imp temp to tmp files
            copy($currDir . '/' . $name, $newDir . '/' . $name);
            //add input for this file
            array_push($files, $name);
        }
        $recRequest['file' . $flid] = $files;
        $recRequest[$flid] = 'f' . $flid . 'u' . $uToken;

        return $recRequest;
    }

    /**
     * @param null $field
     * @return string
     */
    public function getRevisionData($field = null) {
        return $this->model;
    }

    /**
     * Build the advanced search query.
     *
     * @param $flid
     * @param $query
     * @return Builder
     */
    public static function getAdvancedSearchQuery($flid, $query) {
        $processed = $query[$flid."_input"]. "*[Name]";

        return DB::table("model_fields")
            ->select("rid")
            ->where("flid", "=", $flid)
            ->whereRaw("MATCH (`model`) AGAINST (? IN BOOLEAN MODE)", [$processed])
            ->distinct();
    }
}
