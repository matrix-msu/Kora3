<?php namespace App\KoraFields;

use App\Form;
use App\Record;
use App\Search;
use App\Http\Controllers\FormController;
use Illuminate\Http\Request;

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
     * Get the field input view for advanced field search.
     *
     * @return string - The view
     */
    public function getAdvancedSearchInputView() {
        return self::FIELD_ADV_INPUT_VIEW;
    }

    /**
     * Get the field input view for record creation.
     *
     * @return string - The view
     */
    public function getFieldInputView() {
        return self::FIELD_INPUT_VIEW;
    }

    /**
     * Get the field input view for record creation.
     *
     * @return string - The view
     */
    public function getFieldDisplayView() {
        return self::FIELD_DISPLAY_VIEW;
    }

    /**
     * Gets the default options string for a new field.
     *
     * @param  int $fid - Form ID
     * @param  string $slug - Name of database column based on field internal name
     * @param  array $options - Extra information we may need to set up about the field
     * @return array - The default options
     */
    public function addDatabaseColumn($fid, $slug, $options = null) {
        $table = new \CreateRecordsTable();
        $table->addJSONColumn($fid, $slug);
    }

    /**
     * Gets the default options string for a new field.
     *
     * @return string - The default options
     */
    public function getDefaultOptions() {
        return ['Options' => ['Please Modify List Values']];
    }

    /**
     * Update the options for a field
     *
     * @param  array $field - Field to update options
     * @param  Request $request
     * @return array - The updated field array
     */
    public function updateOptions($field, Request $request, $slug = null) {
        if(is_null($request->options)) {
            $request->options = array();
        }

        $field['default'] = $request->default;
        $field['options']['Options'] = $request->options;

        return $field;
    }

    /**
     * Validates the record data for a field against the field's options.
     *
     * @param  int $flid - The field internal name
     * @param  array $field - The field data array to validate
     * @param  Request $request
     * @param  bool $forceReq - Do we want to force a required value even if the field itself is not required?
     * @return array - Array of errors
     */
    public function validateField($flid, $field, $request, $forceReq = false) {
        $req = $field['required'];
        $value = $request->{$flid};
        $options = $field['options']['Options'];

        if(($req==1 | $forceReq) && empty($value))
            return [$flid => $field['name'].' is required'];

        if(!empty($value) && array_diff($value, $options))
            return [$flid => $field['name'].' has an invalid value not in the list.'];

        return array();
    }

    /**
     * Formats data for record entry.
     *
     * @param  array $field - The field to represent record data
     * @param  string $value - Data to add
     * @param  Request $request
     *
     * @return mixed - Processed data
     */
    public function processRecordData($field, $value, $request) {
        return json_encode($value);
    }

    /**
     * Formats data for revision entry.
     *
     * @param  mixed $data - The data to store
     * @param  Request $request
     *
     * @return mixed - Processed data
     */
    public function processRevisionData($data) {
        return $data;
    }

    /**
     * Formats data for record entry.
     *
     * @param  string $flid - Field ID
     * @param  array $field - The field to represent record data
     * @param  array $value - Data to add
     * @param  Request $request
     *
     * @return Request - Processed data
     */
    public function processImportData($flid, $field, $value, $request) {
        $request[$flid] = $value;

        return $request;
    }

    /**
     * Formats data for record entry.
     *
     * @param  string $flid - Field ID
     * @param  array $field - The field to represent record data
     * @param  \SimpleXMLElement $value - Data to add
     * @param  Request $request
     * @param  bool $simple - Is this a simple xml field value
     *
     * @return Request - Processed data
     */
    public function processImportDataXML($flid, $field, $value, $request, $simple = false) {
        $request[$flid] = (array)$value;

        return $request;
    }

    /**
     * Formats data for record display.
     *
     * @param  array $field - The field to represent record data
     * @param  string $value - Data to display
     *
     * @return mixed - Processed data
     */
    public function processDisplayData($field, $value) {
        return json_decode($value);
    }

    /**
     * Formats data for XML record display.
     *
     * @param  string $field - Field ID
     * @param  string $value - Data to format
     *
     * @return mixed - Processed data
     */
    public function processXMLData($field, $value) {
        return "<$field>".htmlspecialchars($value, ENT_XML1, 'UTF-8')."</$field>";
    }

    /**
     * Formats data for XML record display.
     *
     * @param  string $value - Data to format
     *
     * @return mixed - Processed data
     */
    public function processLegacyData($value) {
        return $value;
    }

    /**
     * Takes data from a mass assignment operation and applies it to an individual field.
     *
     * @param  Form $form - Form model
     * @param  string $flid - Field ID
     * @param  String $formFieldValue - The value to be assigned
     * @param  Request $request
     * @param  bool $overwrite - Overwrite if data exists
     */
    public function massAssignRecordField($form, $flid, $formFieldValue, $request, $overwrite=0) {
        $recModel = new Record(array(),$form->id);
        if($overwrite)
            $recModel->newQuery()->update([$flid => $formFieldValue]);
        else
            $recModel->newQuery()->whereNull($flid)->update([$flid => $formFieldValue]);
    }

    /**
     * For a test record, add test data to field.
     */
    public function getTestData($url = null) {
        return [
            'This is one of the list options that was selected.',
            'This is another list option that was selected.'
        ];
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
                $xml = '<' . $slug . '>';
                $xml .= '<value>' . utf8_encode('This is one of the list options that was selected') . '</value>';
                $xml .= '<value>' . utf8_encode('This is another list option that was selected') . '</value>';
                $xml = '<' . $slug . '>';

                return $xml;
                break;
            case "JSON":
                $fieldArray[$slug] = array('This is one of the list options that was selected',
                    'This is another list option that was selected');

                return $fieldArray;
                break;
        }

    }

    /**
     * Performs a keyword search on this field and returns any results.
     *
     * @param  string $flid - Field ID
     * @param  string $arg - The keywords
     * @param  Record $recordMod - Model to search through
     * @param  boolean $negative - Get opposite results of the search
     * @return array - The RIDs that match search
     */
    public function keywordSearchTyped($flid, $arg, $recordMod, $negative = false) {
        if($negative)
            $param = 'NOT LIKE';
        else
            $param = 'LIKE';

        return $recordMod->newQuery()
            ->select("id")
            ->where($flid, $param,"%$arg%")
            ->pluck('id')
            ->toArray();
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
     * Build the advanced query for a text field.
     *
     * @param  $flid, field id
     * @param  $query, contents of query.
     * @param  Record $recordMod - Model to search through
     * @param  boolean $negative - Get opposite results of the search
     * @return array - The RIDs that match search
     */
    public function advancedSearchTyped($flid, $query, $recordMod, $negative = false) {
        $arg = $query[$flid . "_input"];
        $arg = Search::prepare($arg);

        if($negative)
            $param = '!=';
        else
            $param = '=';

        return $recordMod->newQuery()
            ->select("id")
            ->where($flid, $param,"$arg")
            ->pluck('id')
            ->toArray();
    }

    ///////////////////////////////////////////////END ABSTRACT FUNCTIONS///////////////////////////////////////////////

    /**
     * Gets the list options for a multi-select list field.
     *
     * @param  Field $field - Field to pull options from
     * @return array - The list options
     */
    public static function getList($field) {
        $options = array();
        foreach ($field['options']['Options'] as $option) {
            $options['Options'][$option] = $option;
        }
        return $options;
    }
}
