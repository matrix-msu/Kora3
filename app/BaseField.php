<?php namespace App;

use App\Http\Controllers\FieldController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

abstract class BaseField extends Model {

    /*
    |--------------------------------------------------------------------------
    | Base Field
    |--------------------------------------------------------------------------
    |
    | This model represents the abstract class for all typed fields in Kora3
    |
    */

    /**
     * @var array - Maps field constant names to table names
     */
    public static $MAPPED_FIELD_TYPES = [
        Field::_TEXT => "text_fields",
        Field::_RICH_TEXT => "rich_text_fields",
        Field::_NUMBER => "number_fields",
        Field::_LIST => "list_fields",
        Field::_MULTI_SELECT_LIST => "multi_select_list_fields",
        Field::_GENERATED_LIST => "generated_list_fields",
        Field::_COMBO_LIST => "combo_list_fields",
        Field::_DATE => "date_fields",
        Field::_SCHEDULE => "schedule_fields",
        Field::_GEOLOCATOR => "geolocator_fields",
        Field::_DOCUMENTS => "documents_fields",
        Field::_GALLERY => "gallery_fields",
        Field::_PLAYLIST => "playlist_fields",
        Field::_VIDEO => "video_fields",
        Field::_3D_MODEL => "model_fields",
        Field::_ASSOCIATOR => "associator_fields"
    ];

    /**
     * @var array - Maps field constant names to their support table names
     */
    public static $MAPPED_SUPPORT_FIELD_TYPES = [
        Field::_COMBO_LIST => ComboListField::SUPPORT_NAME,
        Field::_SCHEDULE => ScheduleField::SUPPORT_NAME,
        Field::_GEOLOCATOR => GeolocatorField::SUPPORT_NAME,
        Field::_ASSOCIATOR => AssociatorField::SUPPORT_NAME
    ];

    /**
     * Record that the field belongs to.
     *
     * @return BelongsTo
     */
    public function record() {
        return $this->belongsTo('App\Record');
    }

    /**
     * Find every record that does not have data for this field.
     *
     * @param  int $flid - Field ID
     * @return array - The RIDs that are empty
     */
    public function getEmptyFieldRecords($flid) {
        $field = FieldController::getField($flid);

        //If field uses a support table, we want to scan that one
        if(array_key_exists($field->type, self::$MAPPED_SUPPORT_FIELD_TYPES))
            $table = self::$MAPPED_SUPPORT_FIELD_TYPES[$field->type];
        else
            $table = self::$MAPPED_FIELD_TYPES[$field->type];

        $allRIDs = Record::where('fid', '=', $field->fid)->pluck('rid')->toArray();
        $withValueRIDs = DB::table($table)
            ->select("rid")
            ->where("flid", "=", $flid)
            ->distinct()
            ->pluck('rid')
            ->toArray();

        return array_diff($allRIDs,$withValueRIDs);
    }

    /**
     * Turns a typical list of field options, that exists in a string for DB purposes, into an array. These are typical
     * in the structure of 'Opt[!]Opt[!]Opt'.
     *
     * @param  string $string - The option list in string form
     * @param  bool $blankOpt - Has blank option as first array element
     * @return array - The values
     */
    public static function getListOptionsFromString($string, $blankOpt=false) {
        $options = array();

        //if it's blank, we'll just send an empty array
        if($string!='') {
            $opts = explode('[!]', $string);
            foreach($opts as $opt) {
                $options[$opt] = $opt;
            }
        }

        //add the blank value to front of array
        if($blankOpt)
            $options = array('' => '') + $options;

        return $options;
    }

    /**
     * Gets formatted value of record field to compare for sort. Only implement if field is sortable.
     *
     * @return string - The value
     */
    public function getValueForSort() {
        return '';
    }

    /**
     * Deletes all the BaseFields with a certain rid in a clean way.
     *
     * @param  int $rid - Record id
     */
    public static function deleteBaseFieldsByRID($rid) {
        foreach(self::$MAPPED_FIELD_TYPES as $table_name) {
            DB::table($table_name)->where("rid", "=", $rid)->delete();
        }

        // Delete support tables.
        foreach(self::$MAPPED_SUPPORT_FIELD_TYPES as $support_table) {
            DB::table($support_table)->where("rid", "=", $rid)->delete();
        }
    }

    /**
     * Deletes all the BaseFields with a certain flid in a clean way.
     *
     * @param  int $flid - Field id
     */
    public static function deleteBaseFieldsByFLID($flid) {
        foreach(self::$MAPPED_FIELD_TYPES as $table_name) {
            DB::table($table_name)->where("flid", "=", $flid)->delete();
        }

        // Delete support tables.
        foreach(self::$MAPPED_SUPPORT_FIELD_TYPES as $support_table) {
            DB::table($support_table)->where("flid", "=", $flid)->delete();
        }
    }

    /**
     * Get the field options view.
     *
     * @return string - The view
     */
    abstract public function getFieldOptionsView();

    /**
     * Get the field options view for advanced field creation.
     *
     * @return string - The view
     */
    abstract public function getAdvancedFieldOptionsView();

    /**
     * Get the field options view for advanced field creation.
     *
     * @return string - Column name
     */
    abstract public function getSortColumn();

    /**
     * Gets the default options string for a new field.
     *
     * @param  Request $request
     * @return string - The default options
     */
    abstract public function getDefaultOptions(Request $request);

    /**
     * Gets an array of all the fields options.
     *
     * @param  Field $field
     * @return array - The options array
     */
    abstract public function getOptionsArray(Field $field);

    /**
     * Update the options for a field
     *
     * @param  Field $field - Field to update options
     * @param  Request $request
     * @return mixed - The result
     */
    abstract public function updateOptions($field, Request $request);

    /**
     * Creates a typed field to store record data.
     *
     * @param  Field $field - The field to represent record data
     * @param  Record $record - Record being created
     * @param  string $value - Data to add
     * @param  Request $request
     */
    abstract public function createNewRecordField($field, $record, $value, $request);

    /**
     * Edits a typed field that has record data.
     *
     * @param  string $value - Data to add
     * @param  Request $request
     */
    abstract public function editRecordField($value, $request);

    /**
     * Takes data from a mass assignment operation and applies it to an individual field.
     *
     * @param  Field $field - The field to represent record data
     * @param  String $formFieldValue - The value to be assigned
     * @param  Request $request
     * @param  bool $overwrite - Overwrite if data exists
     */
    abstract public function massAssignRecordField($field, $formFieldValue, $request, $overwrite=0);

    /**
     * Takes data from a mass assignment operation and applies it to an individual field for a record subset.
     *
     * @param  Field $field - The field to represent record data
     * @param  String $formFieldValue - The value to be assigned
     * @param  Request $request
     * @param  array $rids - Overwrite if data exists
     */
    abstract public function massAssignSubsetRecordField($field, $formFieldValue, $request, $rids);

    /**
     * For a test record, add test data to field.
     *
     * @param  Field $field - The field to represent record data
     * @param  Record $record - Test record being created
     */
    abstract public function createTestRecordField($field, $record);

    /**
     * Validates the record data for a field against the field's options.
     *
     * @param  Field $field - The field to validate
     * @param  Request $request
     * @param  bool $forceReq - Do we want to force a required value even if the field itself is not required?
     * @return array - Array of errors
     */
    abstract public function validateField($field, $request, $forceReq = false);

    /**
     * Performs a rollback function on an individual field's record data.
     *
     * @param  Field $field - The field being rolled back
     * @param  Revision $revision - The revision being rolled back
     * @param  bool $exists - Field for record exists
     */
    abstract public function rollbackField($field, Revision $revision, $exists=true);

    /**
     * Get the arrayed version of the field data to store in a record preset.
     *
     * @param  array $data - The data array representing the record preset
     * @param  bool $exists - Typed field exists and has data
     * @return array - The updated $data
     */
    abstract public function getRecordPresetArray($data, $exists=true);

    /**
     * Get the required information for a revision data array.
     *
     * @param  Field $field - Optional field to get storage options for certain typed fields
     * @return mixed - The revision data
     */
    abstract public function getRevisionData($field = null);

    /**
     * Provides an example of the field's structure in an export to help with importing records.
     *
     * @param  string $slug - Field nickname
     * @param  string $expType - Type of export
     * @return mixed - The example
     */
    abstract public function getExportSample($slug,$type);

    /**
     * Updates the request for an API search to mimic the advanced search structure.
     *
     * @param  array $data - Data from the search
     * @param  int $flid - Field ID
     * @param  Request $request
     * @return Request - The update request
     */
    abstract public function setRestfulAdvSearch($data, $flid, $request);

    /**
     * Updates the request for an API to mimic record creation .
     *
     * @param  array $jsonField - JSON representation of field data
     * @param  int $flid - Field ID
     * @param  Request $recRequest
     * @param  int $uToken - Custom generated user token for file fields and tmp folders
     * @return Request - The update request
     */
    abstract public function setRestfulRecordData($jsonField, $flid, $recRequest, $uToken=null);

    /**
     * Performs a keyword search on this field and returns any results.
     * NOTE::Please use the DB method to call the table and DO NOT reference the model itself in the builder.
     *
     * @param  int $flid - Field ID
     * @param  string $arg - The keywords
     * @return array - The RIDs that match search
     */
    abstract public function keywordSearchTyped($flid, $arg);

    /**
     * Performs an advanced search on this field and returns any results.
     *
     * @param  int $flid - Field ID
     * @param  array $query - The advance search user query
     * @return array - The RIDs that match search
     */
    abstract public function advancedSearchTyped($flid, $query);
}