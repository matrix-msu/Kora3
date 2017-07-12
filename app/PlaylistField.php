<?php namespace App;

use App\Http\Controllers\FieldController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlaylistField extends FileTypeField  {

    const FIELD_OPTIONS_VIEW = "fields.options.playlist";
    const FIELD_ADV_OPTIONS_VIEW = "partials.field_option_forms.playlist";

    protected $fillable = [
        'rid',
        'flid',
        'audio'
    ];

    public function getFieldOptionsView(){
        return self::FIELD_OPTIONS_VIEW;
    }

    public function getAdvancedFieldOptionsView(){
        return self::FIELD_ADV_OPTIONS_VIEW;
    }

    public function getDefaultOptions(Request $request){
        return '[!FieldSize!]0[!FieldSize!][!MaxFiles!]0[!MaxFiles!][!FileTypes!][!FileTypes!]';
    }

    public function updateOptions($field, Request $request, $return=true) {
        $filetype = $request->filetype[0];
        for($i=1;$i<sizeof($request->filetype);$i++){
            $filetype .= '[!]'.$request->filetype[$i];
        }

        if($request->filesize==''){
            $request->filesize = 0;
        }
        if($request->maxfiles==''){
            $request->maxfiles = 0;
        }

        $field->updateRequired($request->required);
        $field->updateSearchable($request);
        $field->updateOptions('FieldSize', $request->filesize);
        $field->updateOptions('MaxFiles', $request->maxfiles);
        $field->updateOptions('FileTypes', $filetype);

        if($return) {
            flash()->overlay(trans('controller_field.optupdate'), trans('controller_field.goodjob'));
            return redirect('projects/' . $field->pid . '/forms/' . $field->fid . '/fields/' . $field->flid . '/options');
        } else {
            return '';
        }
    }

    public static function getExportSample($field,$type){
        switch ($type){
            case "XML":
                $xml = '<' . Field::xmlTagClear($field->slug) . ' type="' . $field->type . '">';
                $xml .= '<File>';
                $xml .= '<Name>' . utf8_encode('FILENAME 1') . '</Name>';
                $xml .= '</File>';
                $xml .= '<File>';
                $xml .= '<Name>' . utf8_encode('FILENAME 2') . '</Name>';
                $xml .= '</File>';
                $xml .= '<File>';
                $xml .= '<Name>' . utf8_encode('so on...') . '</Name>';
                $xml .= '</File>';
                $xml .= '</' . Field::xmlTagClear($field->slug) . '>';

                return $xml;
                break;
            case "JSON":
                $fieldArray = array('name' => $field->slug, 'type' => $field->type);
                $fieldArray['files'] = array();

                $fileArray = array();
                $fileArray['name'] = 'FILENAME 1';
                array_push($fieldArray['files'], $fileArray);

                $fileArray = array();
                $fileArray['name'] = 'FILENAME2';
                array_push($fieldArray['files'], $fileArray);

                $fileArray = array();
                $fileArray['name'] = 'so on...';
                array_push($fieldArray['files'], $fileArray);

                return $fieldArray;
                break;
        }

    }

    public static function createNewRecordField($field, $record, $value, $request){
        if(glob(env('BASE_PATH') . 'storage/app/tmpFiles/' . $value . '/*.*') != false){
            $pf = new self();
            $pf->flid = $field->flid;
            $pf->rid = $record->rid;
            $pf->fid = $field->fid;
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
            $pf->audio = $infoString;
            $pf->save();
        }
    }

    public static function editRecordField($field, $record, $value, $request){
        if(self::where('rid', '=', $record->rid)->where('flid', '=', $field->flid)->first() != null
            | glob(env('BASE_PATH').'storage/app/tmpFiles/'.$value.'/*.*') != false){
            $pla_files_exist = false; // if this remains false, then the files were deleted and row should be removed from table

            //we need to check if the field exist first
            if(self::where('rid', '=', $record->rid)->where('flid', '=', $field->flid)->first() != null){
                $pf = self::where('rid', '=', $record->rid)->where('flid', '=', $field->flid)->first();
            }else {
                $pf = new self();
                $pf->flid = $field->flid;
                $pf->rid = $record->rid;
                $pf->fid = $record->fid;
                $newPath = env('BASE_PATH').'storage/app/files/p'.$field->pid.'/f'.$field->fid.'/r'.$record->rid.'/fl'.$field->flid;
                if(!file_exists($newPath)) {
                    mkdir($newPath, 0775, true);
                }
            }
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
            foreach (new \DirectoryIterator(env('BASE_PATH').'storage/app/files/p'.$field->pid.'/f'.$field->fid.'/r'.$record->rid.'/fl'.$field->flid) as $file) {
                if ($file->isFile() and in_array($file->getFilename(),$newNames)) {
                    unlink(env('BASE_PATH').'storage/app/files/p'.$field->pid.'/f'.$field->fid.'/r'.$record->rid.'/fl'.$field->flid.'/'.$file->getFilename());
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
                            env('BASE_PATH').'storage/app/files/p'.$field->pid.'/f'.$field->fid.'/r'.$record->rid.'/fl'.$field->flid . '/' . $file->getFilename());
                        $pla_files_exist = true;
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
            $pf->audio = $infoString;
            $pf->save();

            if(!$pla_files_exist){
                $pf->delete();
                flash()->overlay(trans('controller_record.nofile'));
            }
        }
    }

    public static function massAssignRecordField($flid, $record, $form_field_value, $overwrite){
        //TODO::mass assign
    }

    public static function createTestRecordField($field, $record){
        $pf = new self();
        $pf->flid = $field->flid;
        $pf->rid = $record->rid;
        $pf->fid = $field->fid;
        $infoArray = array();
        $maxfiles = FieldController::getFieldOption($field,'MaxFiles');
        if($maxfiles==0){$maxfiles=1;}
        $newPath = env('BASE_PATH') . 'storage/app/files/p' . $field->pid . '/f' . $field->fid . '/r' . $record->rid . '/fl' . $field->flid;
        mkdir($newPath, 0775, true);
        for ($q=0;$q<$maxfiles;$q++) {
            $types = self::getMimeTypes();
            if (!array_key_exists('mp3', $types))
                $type = 'application/octet-stream';
            else
                $type = $types['mp3'];
            $info = '[Name]playlist' . $q . '.mp3[Name][Size]198658[Size][Type]' . $type . '[Type]';
            $infoArray['playlist' . $q . '.mp3'] = $info;
            copy(env('BASE_PATH') . 'public/testFiles/playlist.mp3',
                $newPath . '/playlist' . $q . '.mp3');
        }
        $infoString = implode('[!]',$infoArray);
        $pf->audio = $infoString;
        $pf->save();
    }

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

    public static function getRecordPresetArray($field, $record, $data, $flid_array){
        $playfield = PlaylistField::where('rid', '=', $record->rid)->where('flid', '=', $field->flid)->first();

        if (!empty($playfield->audio)) {
            $data['audio'] = explode('[!]', $playfield->audio);
        }
        else {
            $data['audio'] = null;
        }

        $flid_array[] = $field->flid;

        return array($data,$flid_array,true);
    }

    /**
     * @param null $field
     * @return string
     */
    public function getRevisionData($field = null) {
        return $this->audio;
    }

    /**
     * Rollback a playlist field based on a revision.
     *
     * @param Revision $revision
     * @param Field $field
     * @return PlaylistField
     */
    public static function rollback(Revision $revision, Field $field) {
        if (!is_array($revision->data)) {
            $revision->data = json_decode($revision->data, true);
        }

        if (is_null($revision->data[Field::_PLAYLIST][$field->flid]['data'])) {
            return null;
        }

        $playlistfield = self::where("flid", "=", $field->flid)->where("rid", "=", $revision->rid)->first();

        // If the field doesn't exist or was explicitly deleted, we create a new one.
        if ($revision->type == Revision::DELETE || is_null($playlistfield)) {
            $playlistfield = new self();
            $playlistfield->flid = $field->flid;
            $playlistfield->fid = $revision->fid;
            $playlistfield->rid = $revision->rid;
        }

        $playlistfield->audio = $revision->data[Field::_PLAYLIST][$field->flid]['data'];
        $playlistfield->save();

        return $playlistfield;
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

        return DB::table("playlist_fields")
            ->select("rid")
            ->where("flid", "=", $flid)
            ->whereRaw("MATCH (`audio`) AGAINST (? IN BOOLEAN MODE)", [$processed])
            ->distinct();
    }

    public static function validate($field, $value){
        $req = $field->required;

        if($req==1){
            if(glob(env('BASE_PATH').'storage/app/tmpFiles/'.$value.'/*.*') == false)
                return $field->name.trans('fieldhelpers_val.file');
        }
    }
}