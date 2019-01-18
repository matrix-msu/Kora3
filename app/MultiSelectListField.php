<?php namespace App;

use App\Http\Controllers\FieldController;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class MultiSelectListField extends BaseField {

    /*
    |--------------------------------------------------------------------------
    | Multi-Select List Field
    |--------------------------------------------------------------------------
    |
    | This model represents the multi-select list field in Kora3
    |
    */

    /**
     * @var string - Views for the typed field options
     */
    const FIELD_OPTIONS_VIEW = "partials.fields.options.mslist";
    const FIELD_ADV_OPTIONS_VIEW = "partials.fields.advanced.mslist";
    const FIELD_ADV_INPUT_VIEW = "partials.records.advanced.mslist";
    const FIELD_INPUT_VIEW = "partials.records.input.mslist";
    const FIELD_DISPLAY_VIEW = "partials.records.display.mslist";

    /**
     * @var string - Data column used for sort
     */
    const SORT_COLUMN = null;

    /**
     * @var array - Attributes that can be mass assigned to model
     */
    protected $fillable = [
        'rid',
        'flid',
        'options'
    ];

    /**
     * Get the field options view.
     *
     * @return string - The view
     */
    public function getFieldOptionsView() {
        return self::FIELD_OPTIONS_VIEW;
    }

    /**
     * Get the field options view for advanced field creation.
     *
     * @return string - The view
     */
    public function getAdvancedFieldOptionsView() {
        return self::FIELD_ADV_OPTIONS_VIEW;
    }

    /**
     * Get the field options view for advanced field creation.
     *
     * @return string - Column name
     */
    public function getSortColumn() {
        return self::SORT_COLUMN;
    }

    /**
     * Gets the default options string for a new field.
     *
     * @param  Request $request
     * @return string - The default options
     */
    public function getDefaultOptions(Request $request) {
        return '[!Options!][!Options!]';
    }

    /**
     * Gets an array of all the fields options.
     *
     * @param  Field $field
     * @return array - The options array
     */
    public function getOptionsArray(Field $field) {
        $options = array();

        $options['Options'] = explode('[!]',FieldController::getFieldOption($field, 'Options'));

        return $options;
    }

    /**
     * Update the options for a field
     *
     * @param  Field $field - Field to update options
     * @param  Request $request
     * @return Redirect
     */
    public function updateOptions($field, Request $request) {
        $reqDefs = $request->default;
        $default = $reqDefs[0];
        if(!is_null($default)) {
            for($i = 1; $i < sizeof($reqDefs); $i++) {
                $default .= '[!]' . $reqDefs[$i];
            }
        }

        $reqOpts = $request->options;
        $options = $reqOpts[0];
        if(!is_null($options)) {
            for($i = 1; $i < sizeof($reqOpts); $i++) {
                $options .= '[!]' . $reqOpts[$i];
            }
        }

        $field->updateRequired($request->required);
        $field->updateSearchable($request);
        $field->updateDefault($default);
        $field->updateOptions('Options', $options);

        return redirect('projects/' . $field->pid . '/forms/' . $field->fid . '/fields/' . $field->flid . '/options')
            ->with('k3_global_success', 'field_options_updated');
    }

    /**
     * Creates a typed field to store record data.
     *
     * @param  Field $field - The field to represent record data
     * @param  Record $record - Record being created
     * @param  string $value - Data to add
     * @param  Request $request
     */
    public function createNewRecordField($field, $record, $value, $request) {
        $this->flid = $field->flid;
        $this->rid = $record->rid;
        $this->fid = $field->fid;
        $this->options = implode("[!]",$value);
        $this->save();
    }

    /**
     * Edits a typed field that has record data.
     *
     * @param  string $value - Data to add
     * @param  Request $request
     */
    public function editRecordField($value, $request) {
        if(!is_null($this) && !is_null($value)) {
            $this->options = implode("[!]",$value);
            $this->save();
        } else if(!is_null($this) && is_null($value)) {
            $this->delete();
        }
    }

    /**
     * Takes data from a mass assignment operation and applies it to an individual field.
     *
     * @param  Field $field - The field to represent record data
     * @param  String $formFieldValue - The value to be assigned
     * @param  Request $request
     * @param  bool $overwrite - Overwrite if data exists
     */
    public function massAssignRecordField($field, $formFieldValue, $request, $overwrite=0) {
        //Get array of all RIDs in form
        $rids = Record::where('fid','=',$field->fid)->pluck('rid')->toArray();
        //Get list of RIDs that have the value for that field
        $ridsValue = MultiSelectListField::where('flid','=',$field->flid)->where('options','!=','')->where('options','!=',NULL)->pluck('rid')->toArray();
        //Subtract to get RIDs with no value
        $ridsNoVal = array_diff($rids, $ridsValue);

        foreach(array_chunk($ridsNoVal,1000) as $chunk) {
            //Create data array and store values for no value RIDs
            $dataArray = [];
            foreach($chunk as $rid) {
                $dataArray[] = [
                    'rid' => $rid,
                    'fid' => $field->fid,
                    'flid' => $field->flid,
                    'options' => implode("[!]", $formFieldValue)
                ];
            }
            MultiSelectListField::insert($dataArray);
        }

        if($overwrite) {
            foreach(array_chunk($ridsValue, 1000) as $chunk) {
                MultiSelectListField::where('flid', '=', $field->flid)->whereIn('rid', $chunk)->update(['options' => implode("[!]", $formFieldValue)]);
            }
        }
    }

    /**
     * Takes data from a mass assignment operation and applies it to an individual field for a record subset.
     *
     * @param  Field $field - The field to represent record data
     * @param  String $formFieldValue - The value to be assigned
     * @param  Request $request
     * @param  array $rids - Overwrite if data exists
     */
    public function massAssignSubsetRecordField($field, $formFieldValue, $request, $rids) {
        //Delete the old data
        MultiSelectListField::where('flid','=',$field->flid)->whereIn('rid', $rids)->delete();

        foreach(array_chunk($rids,1000) as $chunk) {
            //Create data array and store values for no value RIDs
            $dataArray = [];
            foreach($chunk as $rid) {
                $dataArray[] = [
                    'rid' => $rid,
                    'fid' => $field->fid,
                    'flid' => $field->flid,
                    'options' => implode("[!]", $formFieldValue)
                ];
            }
            MultiSelectListField::insert($dataArray);
        }
    }

    /**
     * For a test record, add test data to field.
     *
     * @param  Field $field - The field to represent record data
     * @param  Record $record - Test record being created
     */
    public function createTestRecordField($field, $record) {
        $this->flid = $field->flid;
        $this->rid = $record->rid;
        $this->fid = $field->fid;
        $this->options = 'This is one of the list options that was selected[!]This is another list option that was selected';
        $this->save();
    }

    /**
     * Validates the record data for a field against the field's options.
     *
     * @param  Field $field - The field to validate
     * @param  Request $request
     * @param  bool $forceReq - Do we want to force a required value even if the field itself is not required?
     * @return array - Array of errors
     */
    public function validateField($field, $request, $forceReq = false) {
        $req = $field->required;
        $value = $request->{$field->flid};
        $list = MultiSelectListField::getList($field);

        if(($req==1 | $forceReq) && ($value==null | $value==""))
            return ['list'.$field->flid.'_chosen' => $field->name.' is required'];

        if($value!=null && sizeof(array_diff($value,$list))>0)
            return ['list'.$field->flid.'_chosen' => $field->name.' has an invalid value not in the list'];

        return array();
    }

    /**
     * Performs a rollback function on an individual field's record data.
     *
     * @param  Field $field - The field being rolled back
     * @param  Revision $revision - The revision being rolled back
     * @param  bool $exists - Field for record exists
     */
    public function rollbackField($field, Revision $revision, $exists=true) {
        if(!is_array($revision->oldData))
            $revision->oldData = json_decode($revision->oldData, true);

        if(is_null($revision->oldData[Field::_MULTI_SELECT_LIST][$field->flid]['data']))
            return null;

        // If the field doesn't exist or was explicitly deleted, we create a new one.
        if($revision->type == Revision::DELETE || !$exists) {
            $this->flid = $field->flid;
            $this->rid = $revision->rid;
            $this->fid = $revision->fid;
        }

        $this->options = $revision->oldData[Field::_MULTI_SELECT_LIST][$field->flid]['data'];
        $this->save();
    }

    /**
     * Get the arrayed version of the field data to store in a record preset.
     *
     * @param  array $data - The data array representing the record preset
     * @param  bool $exists - Typed field exists and has data
     * @return array - The updated $data
     */
    public function getRecordPresetArray($data, $exists=true) {
        if($exists)
            $data['options'] = explode('[!]', $this->options);
        else
            $data['options'] = null;

        return $data;
    }

    /**
     * Get the required information for a revision data array.
     *
     * @param  Field $field - Optional field to get storage options for certain typed fields
     * @return mixed - The revision data
     */
    public function getRevisionData($field = null) {
        return $this->options;
    }

    /**
     * Provides an example of the field's structure in an export to help with importing records.
     *
     * @param  string $slug - Field nickname
     * @param  string $expType - Type of export
     * @return mixed - The example
     */
    public function getExportSample($slug,$type) {
        switch($type) {
            case "XML":
                $xml = '<' . Field::xmlTagClear($slug) . ' type="Multi-Select List">';
                $xml .= '<value>' . utf8_encode('This is one of the list options that was selected') . '</value>';
                $xml .= '<value>' . utf8_encode('This is another list option that was selected') . '</value>';
                $xml .= '</' . Field::xmlTagClear($slug) . '>';

                return $xml;
                break;
            case "JSON":
                $fieldArray = [$slug => ['type' => 'Multi-Select List']];
                $fieldArray[$slug]['value'] = array('This is one of the list options that was selected',
                    'This is another list option that was selected');

                return $fieldArray;
                break;
        }

    }

    /**
     * Updates the request for an API search to mimic the advanced search structure.
     *
     * @param  array $data - Data from the search
     * @param  int $flid - Field ID
     * @param  Request $request
     * @return Request - The update request
     */
    public function setRestfulAdvSearch($data, $flid, $request) {
        $request->request->add([$flid.'_input' => $data->input]);

        return $request;
    }

    /**
     * Updates the request for an API to mimic record creation .
     *
     * @param  array $jsonField - JSON representation of field data
     * @param  int $flid - Field ID
     * @param  Request $recRequest
     * @param  int $uToken - Custom generated user token for file fields and tmp folders
     * @return Request - The update request
     */
    public function setRestfulRecordData($jsonField, $flid, $recRequest, $uToken=null) {
        $recRequest[$flid] = $jsonField->value;

        return $recRequest;
    }

    /**
     * Performs a keyword search on this field and returns any results.
     *
     * @param  int $flid - Field ID
     * @param  string $arg - The keywords
     * @return array - The RIDs that match search
     */
    public function keywordSearchTyped($flid, $arg) {
        return DB::table("multi_select_list_fields")
            ->select("rid")
            ->where("flid", "=", $flid)
            ->where('options','LIKE',"%$arg%")
            ->distinct()
            ->pluck('rid')
            ->toArray();
    }

    /**
     * Performs an advanced search on this field and returns any results.
     *
     * @param  int $flid - Field ID
     * @param  array $query - The advance search user query
     * @return array - The RIDs that match search
     */
    public function advancedSearchTyped($flid, $query) {
        $inputs = $query[$flid."_input"];

        $query = DB::table("multi_select_list_fields")
            ->select("rid")
            ->where("flid", "=", $flid);

        self::buildAdvancedMultiSelectListQuery($query, $inputs);

        return $query->distinct()
            ->pluck('rid')
            ->toArray();
    }

    /**
     * Build the advanced search query for a multi-select list.
     *
     * @param  Builder $db_query - Pointer to the query object
     * @param  array $inputs - Input values
     */
    private static function buildAdvancedMultiSelectListQuery(Builder &$db_query, $inputs) {
        $db_query->where(function($db_query) use ($inputs) {
            foreach($inputs as $input) {
                $input = Search::prepare($input);
                //since we want to look for the exact term when data is concatenated string
                $db_query->orWhere('options','LIKE',$input."[!]%"); //is it the first term
                $db_query->orWhere('options','LIKE',"%[!]".$input); //is it the last term
                $db_query->orWhere('options','LIKE',"%[!]".$input."[!]%"); //is it in the middle
                $db_query->orWhere(function($db_query) use ($input) {
                    $db_query->where('options','NOT LIKE',"%[!]%")->where('options','=',$input); //is it the only item
                });
            }
        });
    }

    ///////////////////////////////////////////////END ABSTRACT FUNCTIONS///////////////////////////////////////////////

    /**
     * Gets the list options for a multi-select list field.
     *
     * @param  Field $field - Field to pull options from
     * @param  bool $blankOpt - Has blank option as first array element
     * @return array - The list options
     */
    public static function getList($field, $blankOpt=false) {
        $dbOpt = FieldController::getFieldOption($field, 'Options');
        return self::getListOptionsFromString($dbOpt,$blankOpt);
    }
}
