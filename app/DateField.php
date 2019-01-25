<?php namespace App;

use App\Http\Controllers\FieldController;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class DateField extends BaseField {

    /*
    |--------------------------------------------------------------------------
    | Date Field
    |--------------------------------------------------------------------------
    |
    | This model represents the date field in Kora3
    |
    */

    /**
     * @var string - Views for the typed field options
     */
    const FIELD_OPTIONS_VIEW = "partials.fields.options.date";
    const FIELD_ADV_OPTIONS_VIEW = "partials.fields.advanced.date";
    const FIELD_ADV_INPUT_VIEW = "partials.records.advanced.date";
    const FIELD_INPUT_VIEW = "partials.records.input.date";
    const FIELD_DISPLAY_VIEW = "partials.records.display.date";

    /**
     * @var string - Data column used for sort
     */
    const SORT_COLUMN = "date_object";

    /**
     * @var string - Month day year format
     */
    const MONTH_DAY_YEAR = "MMDDYYYY";
    /**
     * @var string - Day month year format
     */
    const DAY_MONTH_YEAR = "DDMMYYYY";
    /**
     * @var string - Year month day format
     */
    const YEAR_MONTH_DAY = "YYYYMMDD";

    /**
     * @var array - The months of the year in different languages
     *
     * These are listed without special characters because the input will be converted to close characters.
     * Formatted with regular expression tags to find only the exact month so "march" does not match "marches" for example.
     */
    const MONTHS_IN_LANG = [
        // English
        ['/(\\W|^)january(\\W|$)/i', "/(\\W|^)february(\\W|$)/i", "/(\\W|^)march(\\W|$)/i",
            "/(\\W|^)april(\\W|$)/i", "/(\\W|^)may(\\W|$)/i", "/(\\W|^)june(\\W|$)/i", "/(\\W|^)july(\\W|$)/i",
            "/(\\W|^)august(\\W|$)/i", "/(\\W|^)september(\\W|$)/i", "/(\\W|^)october(\\W|$)/i",
            "/(\\W|^)november(\\W|$)/i", "/(\\W|^)december(\\W|$)/i"],

        // Spanish
        ["/(\\W|^)enero(\\W|$)/i", "/(\\W|^)febrero(\\W|$)/i", "/(\\W|^)marzo(\\W|$)/i",
            "/(\\W|^)abril(\\W|$)/i", "/(\\W|^)mayo(\\W|$)/i", "/(\\W|^)junio(\\W|$)/i", "/(\\W|^)julio(\\W|$)/i",
            "/(\\W|^)agosto(\\W|$)/i", "/(\\W|^)septiembre(\\W|$)/i", "/(\\W|^)octubre(\\W|$)/i",
            "/(\\W|^)noviembre(\\W|$)/i", "/(\\W|^)diciembre(\\W|$)/i"],

        // French
        ["/(\\W|^)janvier(\\W|$)/i", "/(\\W|^)fevrier(\\W|$)/i", "/(\\W|^)mars(\\W|$)/i",
            "/(\\W|^)avril(\\W|$)/i", "/(\\W|^)mai(\\W|$)/i", "/(\\W|^)juin(\\W|$)/i", "/(\\W|^)juillet(\\W|$)/i",
            "/(\\W|^)aout(\\W|$)/i", "/(\\W|^)septembre(\\W|$)/i", "/(\\W|^)octobre(\\W|$)/i",
            "/(\\W|^)novembre(\\W|$)/i", "/(\\W|^)decembre(\\W|$)/i"]
    ];

    /**
     * @var array - We currently support 3 languages, so this is an array of 3 copies of the number of 1 through 12
     */
    const MONTH_NUMBERS = [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12 ];

    /**
     * @var array - Attributes that can be mass assigned to model
     */
    protected $fillable = [
        'rid',
        'flid',
        'month',
        'day',
        'year',
        'era',
        'circa',
        'date_object'
    ];

    /**
     * Get the field options view.
     *
     * @return string - The view
     */
    public function getFieldOptionsView(){
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
        return '[!Circa!]No[!Circa!][!Start!]1900[!Start!][!End!]2020[!End!][!Format!]MMDDYYYY[!Format!][!Era!]No[!Era!]';
    }

    /**
     * Gets an array of all the fields options.
     *
     * @param  Field $field
     * @return array - The options array
     */
    public function getOptionsArray(Field $field) {
        $options = array();

        $options['CircaAllowed'] = FieldController::getFieldOption($field, 'Circa');
        $options['StartYear'] = FieldController::getFieldOption($field, 'Start');
        $options['EndYear'] = FieldController::getFieldOption($field, 'End');
        $options['DateFormat'] = FieldController::getFieldOption($field, 'Format');
        $options['EraAllowed'] = FieldController::getFieldOption($field, 'Era');

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
        if(DateField::validateDate($request->default_month,$request->default_day,$request->default_year)) {
            $default = '[M]' . $request->default_month . '[M][D]' . $request->default_day . '[D][Y]' . $request->default_year . '[Y]';
        } else {
            return redirect('projects/' . $field->pid . '/forms/' . $field->fid . '/fields/' . $field->flid . '/options')
                ->withInput()->with('k3_global_error', 'default_invalid_date');
        }

        if($request->start=='' | $request->start==0)
            $request->start = 1;

        if($request->end=='')
            $request->end = 9999;

        $field->updateRequired($request->required);
        $field->updateSearchable($request);
        $field->updateDefault($default);
        $field->updateOptions('Format', $request->format);
        $field->updateOptions('Start', $request->start);
        $field->updateOptions('End', $request->end);
        $field->updateOptions('Circa', $request->circa);
        $field->updateOptions('Era', $request->era);

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
        $this->circa = $request->input('circa_' . $field->flid, '');
        $this->month = $request->input('month_' . $field->flid, 0);
        $this->day = $request->input('day_' . $field->flid, 0);
        $this->year = $request->input('year_' . $field->flid, 0);
        $this->era = $request->input('era_' . $field->flid, 'CE');
        $this->save();
    }

    /**
     * Edits a typed field that has record data.
     *
     * @param  string $value - Data to add
     * @param  Request $request
     */
    public function editRecordField($value, $request) {
        if(!is_null($this) && !(empty($request->input('month_'.$this->flid)) && empty($request->input('day_'.$this->flid)) && empty($request->input('year_'.$this->flid)))) {
            $this->circa = $request->input('circa_'.$this->flid, '');
            $this->month = $request->input('month_'.$this->flid, 0);
            $this->day = $request->input('day_'.$this->flid, 0);
            $this->year = $request->input('year_'.$this->flid, 0);
            $this->era = $request->input('era_'.$this->flid, 'CE');
            $this->save();
        } else if(!is_null($this) && (empty($request->input('month_'.$this->flid)) && empty($request->input('day_'.$this->flid)) && empty($request->input('year_'.$this->flid)))) {
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
        $ridsValue = DateField::where('flid','=',$field->flid)->where('month','!=','')->where('month','!=',NULL)->pluck('rid')->toArray();
        //Subtract to get RIDs with no value
        $ridsNoVal = array_diff($rids, $ridsValue);

        foreach(array_chunk($ridsNoVal,1000) as $chunk) {
            //Create data array and store values for no value RIDs
            $dataArray = [];
            $flid = $field->flid;
            foreach($chunk as $rid) {
                $dataArray[] = [
                    'rid' => $rid,
                    'fid' => $field->fid,
                    'flid' => $flid,
                    'circa' => $request->input('circa_' . $flid, ''),
                    'month' => $request->input('month_' . $flid),
                    'day' => $request->input('day_' . $flid),
                    'year' => $request->input('year_' . $flid),
                    'era' => $request->input('era_' . $flid, 'CE')
                ];
            }
            DateField::insert($dataArray);
        }

        if($overwrite) {
            foreach(array_chunk($ridsValue, 1000) as $chunk) {
                DateField::where('flid', '=', $field->flid)->whereIn('rid', $chunk)->update([
                    'circa' => $request->input('circa_' . $flid, ''),
                    'month' => $request->input('month_' . $flid),
                    'day' => $request->input('day_' . $flid),
                    'year' => $request->input('year_' . $flid),
                    'era' => $request->input('era_' . $flid, 'CE')
                ]);
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
        DateField::where('flid','=',$field->flid)->whereIn('rid', $rids)->delete();

        foreach(array_chunk($rids,1000) as $chunk) {
            //Create data array and store values for no value RIDs
            $dataArray = [];
            $flid = $field->flid;
            foreach($chunk as $rid) {
                $dataArray[] = [
                    'rid' => $rid,
                    'fid' => $field->fid,
                    'flid' => $field->flid,
                    'circa' => $request->input('circa_' . $flid, ''),
                    'month' => $request->input('month_' . $flid),
                    'day' => $request->input('day_' . $flid),
                    'year' => $request->input('year_' . $flid),
                    'era' => $request->input('era_' . $flid, 'CE')
                ];
            }
            DateField::insert($dataArray);
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
        $this->circa = 0;
        $this->month = 3;
        $this->day = 3;
        $this->year = 2003;
        $this->era = 'CE';
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
        $start = FieldController::getFieldOption($field,'Start');
        $end = FieldController::getFieldOption($field,'End');
        $month = $request->input('month_'.$field->flid,'');
        $day = $request->input('day_'.$field->flid,'');
        $year = $request->input('year_'.$field->flid,'');

        if(($req==1 | $forceReq) && $month=='' && $day=='' && $year=='')
            return [
                'month_'.$field->flid.'_chosen' => $field->name.' is required',
                'day_'.$field->flid.'_chosen' => ' ',
                'year_'.$field->flid.'_chosen' => ' '
            ];

        if(($year<$start | $year>$end) && $year!='')
            return [
                'year_'.$field->flid.'_chosen' => $field->name.'\'s year is outside of the expected range'
            ];

        if(!DateField::validateDate($month,$day,$year))
            return [
                'month_'.$field->flid.'_chosen' => $field->name.' is an invalid date',
                'day_'.$field->flid.'_chosen' => ' ',
                'year_'.$field->flid.'_chosen' => ' '
            ];

        return array();
    }

    /**
     * Validates the month, day, year combonations so illegal dates can't happen.
     *
     * @param  int $m - Month
     * @param  int $d - Day
     * @param  int $y - Year
     * @return bool - Is valid
     */
    private static function validateDate($m,$d,$y) {
        //First off we cant have a date without a month.
        if($d!='' && !is_null($d) && $d!=0) {
            if($m == '' | is_null($m) | $m==0)
                return false;
        }

        //Next we need to make sure the date provided is legal (i.e. no Feb 30th, etc)
        //For the check we need to default any blank values to 1, cause checkdate doesn't like partial dates
        if($m == '' | is_null($m) | $m==0) {$m=1;}
        if($d == '' | is_null($d) | $d==0) {$d=1;}
        if($y == '' | is_null($y) | $y==0) {$y=1;}

        return checkdate($m, $d, $y);
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

        if(!isset($revision->oldData[Field::_DATE][$field->flid]['data']))
            return null;

        // If the field doesn't exist or was explicitly deleted, we create a new one.
        if($revision->type == Revision::DELETE || $exists) {
            $this->flid = $field->flid;
            $this->fid = $revision->fid;
            $this->rid = $revision->rid;
        }

        $this->circa = $revision->oldData[Field::_DATE][$field->flid]['data']['circa'];
        $this->month = $revision->oldData[Field::_DATE][$field->flid]['data']['month'];
        $this->day = $revision->oldData[Field::_DATE][$field->flid]['data']['day'];
        $this->year = $revision->oldData[Field::_DATE][$field->flid]['data']['year'];
        $this->era = $revision->oldData[Field::_DATE][$field->flid]['data']['era'];
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
        $date_array = array();

        if($exists) {
            $date_array['circa'] = $this->circa;
            $date_array['era'] = $this->era;
            $date_array['day'] = $this->day;
            $date_array['month'] = $this->month;
            $date_array['year'] = $this->year;
        } else {
            $date_array['circa'] = null;
            $date_array['era'] = null;
            $date_array['day'] = null;
            $date_array['month'] = null;
            $date_array['year'] = null;
        }

        $data['data'] = $date_array;

        return $data;
    }

    /**
     * Get the required information for a revision data array.
     *
     * @param  Field $field - Optional field to get storage options for certain typed fields
     * @return mixed - The revision data
     */
    public function getRevisionData($field = null) {
        return [
            'day' => $this->day,
            'month' => $this->month,
            'year' => $this->year,
            'format' => FieldController::getFieldOption($field, 'Format'),
            'circa' => FieldController::getFieldOption($field, 'Circa') == 'Yes' ? $this->circa : '',
            'era' => FieldController::getFieldOption($field, 'Era') == 'Yes' ? $this->era : ''
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
                $xml = '<' . Field::xmlTagClear($slug) . ' type="Date">';
                $value = '<Circa>' . utf8_encode('1 if CIRCA. 0 if NOT CIRCA (Tag is optional)') . '</Circa>';
                $value .= '<Month>' . utf8_encode('NUMERIC VALUE OF MONTH (i.e. 03)') . '</Month>';
                $value .= '<Day>' . utf8_encode('3') . '</Day>';
                $value .= '<Year>' . utf8_encode('2003') . '</Year>';
                $value .= '<Era>' . utf8_encode('CE, BCE, BP, or KYA BP (Tag is optional)') . '</Era>';
                $xml .= $value;
                $xml .= '</' . Field::xmlTagClear($slug) . '>';

                $xml .= '<' . Field::xmlTagClear($slug) . ' type="Date" simple="simple">';
                $xml .= utf8_encode('MM/DD/YYYY');
                $xml .= '</' . Field::xmlTagClear($slug) . '>';

                return $xml;
                break;
            case "JSON":
                $fieldArray = [$slug => ['type' => 'Date']];

                $fieldArray[$slug]['value']['circa'] = '1 if CIRCA. 0 if NOT CIRCA (Index is optional)';
                $fieldArray[$slug]['value']['month'] = 'NUMERIC VALUE OF MONTH (i.e. 03)';
                $fieldArray[$slug]['value']['day'] = 3;
                $fieldArray[$slug]['value']['year'] = 2003;
                $fieldArray[$slug]['value']['era'] = 'CE, BCE, BP, or KYA BP (Index is optional)';

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
        if(isset($data->begin_month))
            $beginMonth = $data->begin_month;
        else
            $beginMonth = '';

        if(isset($data->begin_day))
            $beginDay = $data->begin_day;
        else
            $beginDay = '';

        if(isset($data->begin_year))
            $beginYear = $data->begin_year;
        else
            $beginYear = '';

        $request->request->add([$flid.'_begin_month' => $beginMonth]);
        $request->request->add([$flid.'_begin_day' => $beginDay]);
        $request->request->add([$flid.'_begin_year' => $beginYear]);

        if(isset($data->end_month))
            $endMonth = $data->end_month;
        else
            $endMonth = '';

        if(isset($data->end_day))
            $endDay = $data->end_day;
        else
            $endDay = '';

        if(isset($data->end_year))
            $endYear = $data->end_year;
        else
            $endYear = '';

        $request->request->add([$flid.'_end_month' => $endMonth]);
        $request->request->add([$flid.'_end_day' => $endDay]);
        $request->request->add([$flid.'_end_year' => $endYear]);

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
        $recRequest['circa_' . $flid] = isset($jsonField->value->circa) ? $jsonField->value->circa : null;
        $recRequest['month_' . $flid] = isset($jsonField->value->month) ? $jsonField->value->month : null;
        $recRequest['day_' . $flid] = isset($jsonField->value->day) ? $jsonField->value->day : null;
        $recRequest['year_' . $flid] = isset($jsonField->value->year) ? $jsonField->value->year : null;
        $recRequest['era_' . $flid] = isset($jsonField->value->era) ? $jsonField->value->era : null;
        $recRequest[$flid] = '';

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
        $field = FieldController::getField($flid);

        // Boolean to decide if we should consider circa options.
        $circa = explode("[!Circa!]", $field->options)[1] == "Yes";

        // Boolean to decide if we should consider era.
        $era = explode("[!Era!]", $field->options)[1] == "On";

        return self::buildQuery($arg, $circa, $era, $flid);
    }

    /**
     * Builds the query for a date field.
     *
     * @param $arg string - The keyword to test
     * @param $circa bool - Should we search for date fields with circa turned on?
     * @param $era bool - Should we search for date fields with era turned on?
     * @param $flid int - Field ID
     * @return array - The query for the date field
     */
    private static function buildQuery($arg, $circa, $era, $flid) {
        //Checks to prevent false positives with default mysql values
        $intVal = intval($arg);
        if($intVal == 0)
            $intVal = 999999;

        $intMonth = intval(self::monthToNumber($arg));
        if($intMonth == 0)
            $intMonth = 999999;

        $query = DB::table("date_fields")
            ->select("rid")
            ->where("flid", "=", $flid);

        // This function acts as parenthesis around the or's of the date field requirements.
        $query = $query->where(function($sQuery) use ($arg, $circa, $era, $intVal, $intMonth) {
            $sQuery->orWhere("day", "=", $intVal)->orWhere("year", "=", $intVal);

            if(self::isMonth($arg))
                $sQuery = $sQuery->orWhere("month", "=", $intMonth);

            if($era && self::isValidEra($arg))
                $sQuery = $sQuery->orWhere("era", "=", strtoupper($arg));

            if($circa && self::isCirca($arg))
                $sQuery = $sQuery->orWhere("circa", "=", 1);
        });

        return $query->distinct()
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
        $begin_month = (isset($query[$flid."_begin_month"]) && $query[$flid."_begin_month"] != "") ? intval($query[$flid."_begin_month"]) : 1;
        $begin_day = (isset($query[$flid."_begin_day"]) && $query[$flid."_begin_day"] != "") ? intval($query[$flid."_begin_day"]) : 1;
        $begin_year = (isset($query[$flid."_begin_year"]) && $query[$flid."_begin_year"] != "") ? intval($query[$flid."_begin_year"]) : 1;
        $begin_era = isset($query[$flid."_begin_era"]) ? $query[$flid."_begin_era"] : "CE";

        $end_month = (isset($query[$flid."_end_month"]) && $query[$flid."_end_month"] != "") ? intval($query[$flid."_end_month"]) : 1;
        $end_day = (isset($query[$flid."_end_day"]) && $query[$flid."_end_day"] != "") ? intval($query[$flid."_end_day"]) : 1;
        $end_year = (isset($query[$flid."_end_year"]) && $query[$flid."_end_year"] != "") ? intval($query[$flid."_end_year"]) : 1;
        $end_era = isset($query[$flid."_end_era"]) ? $query[$flid."_end_era"] : "CE";

        $query = DB::table("date_fields")
            ->select("rid")
            ->where("flid", "=", $flid);

        if($begin_era == "BCE" && $end_era == "BCE") { // Date interval flipped, dates are decreasing.
            $begin = DateTime::createFromFormat("Y-m-d", $end_year."-".$end_month."-".$end_day); // End is beginning now.
            $end = DateTime::createFromFormat("Y-m-d", $begin_year."-".$begin_month."-".$begin_day); // Begin is end now.

            $query->where("era", "=", "BCE")
                ->whereBetween("date_object", [$begin, $end]);
        } else if($begin_era == "BCE" && $end_era == "CE") { // Have to use two interval and era clauses.
            $begin = DateTime::createFromFormat("Y-m-d", $begin_year."-".$begin_month."-".$begin_day);
            $era_bound = DateTime::createFromFormat("Y-m-d", "1-1-1"); // There is no year 0 on Gregorian calendar.
            $end = DateTime::createFromFormat("Y-m-d", $end_year."-".$end_month."-".$end_day);

            $query->where(function($query) use($begin, $era_bound, $end) {
                $query->where("era", "=", "BCE")
                    ->whereBetween("date_object", [$era_bound, $begin]);

                $query->orWhere(function($query) use($era_bound, $end) {
                    $query->where("era", "=", "CE")
                        ->whereBetween("date_object", [$era_bound, $end]);
                });
            });
        } else if($begin_era == "CE" && $end_era == "CE") { // Normal case, both are CE, the other choice of CE then BCE is invalid.
            $begin = DateTime::createFromFormat("Y-m-d", $begin_year."-".$begin_month."-".$begin_day);
            $end = DateTime::createFromFormat("Y-m-d", $end_year."-".$end_month."-".$end_day);

            $query->where("era", "=", "CE")
                ->whereBetween("date_object", [$begin, $end]);
        } else if($begin_era == "BP" && $end_era == "BP") {
            $query->where("era", "=", "BP")
                ->whereBetween("year", [$begin_year, $end_year]);
        } else if($begin_era == "KYA BP" && $end_era == "KYA BP") {
            $query->where("era", "=", "KYA BP")
                ->whereBetween("year", [$begin_year, $end_year]);
        } else {
            //CANT MIX BEYOND THIS. WE FAIL FOR NOW
            //- Can't mix BP with KYA BP
            //- Can't mix any BP with any CE
            //- Can't have CE before BCE
            return array();
        }

        return $query->distinct()
            ->pluck('rid')
            ->toArray();
    }

    ///////////////////////////////////////////////END ABSTRACT FUNCTIONS///////////////////////////////////////////////

    /**
     * Returns the mysql string required to sort a set of RIDs.
     *
     * @param $ridArray - String of record IDs
     * @param $flid - Field ID
     * @param $dir - Direction of sorting
     * @return string - The MySQL string
     */
    public function getRidValuesForGlobalSort($ridArray,$flids,$dir) {
        $prefix = config('database.connections.mysql.prefix');
        $flidArray = implode(',',$flids);
        return "SELECT `rid`, `date_object` AS `value` FROM ".$prefix."date_fields WHERE `flid` IN ($flidArray) AND `rid` IN ($ridArray) ORDER BY `date_object` $dir";
    }

    /**
     * Formatted display of a date field value.
     *
     * @return string - The formatted string
     */
    public function displayDate() {
        $dateString = '';
        $fieldPreviewMod = FieldController::getField($this->flid);

        if($this->circa==1 && FieldController::getFieldOption($fieldPreviewMod,'Circa')=='Yes')
            $dateString .= 'circa ';

        if($this->month==0 && $this->day==0)
            $dateString .= $this->year;
        else if($this->day==0 && $this->year==0)
            $dateString .= \DateTime::createFromFormat('m', $this->month)->format('F');
        else if($this->day==0)
            $dateString .= \DateTime::createFromFormat('m', $this->month)->format('F').', '.$this->year;
        else if($this->year==0)
            $dateString .= \DateTime::createFromFormat('m', $this->month)->format('F').' '.$this->day;
        else if(FieldController::getFieldOption($fieldPreviewMod,'Format')=='MMDDYYYY')
            $dateString .= $this->month.'-'.$this->day.'-'.$this->year;
        else if(FieldController::getFieldOption($fieldPreviewMod,'Format')=='DDMMYYYY')
            $dateString .= $this->day.'-'.$this->month.'-'.$this->year;
        else if(FieldController::getFieldOption($fieldPreviewMod,'Format')=='YYYYMMDD')
            $dateString .= $this->year.'-'.$this->month.'-'.$this->day;

        if(\App\Http\Controllers\FieldController::getFieldOption($fieldPreviewMod,'Era')=='Yes')
            $dateString .= ' '.$this->era;

        return $dateString;
    }

    /**
     * Overwrites model save to save the record data as a date object that search will use.
     *
     * @param  array $options - Record data to save
     * @return bool - Return value from save
     */
    public function save(array $options = array()) {
        $dT = new DateTime();
        if($this->year=='')
            $year = 0;
        else
            $year = $this->year;
        if($this->month=='')
            $month = 0;
        else
            $month = $this->month;
        if($this->day=='')
            $day = 0;
        else
            $day = $this->day;
        $date = $dT->setDate($year,$month,$day);
        $this->date_object = date_format($date, "Y-m-d");

        return parent::save($options);
    }

    /**
     * Determines if a string is a value month name.
     * Using the month to number function, if the string is turned to a number
     * we know it is determined to be a valid month name.
     * The original string should also not be a number itself. As searches for
     * the numbers 1 through 12 should not return dates based on some month Jan-Dec.
     *
     * @param $string string - The string to test
     * @return bool - Is string valid month
     */
    public static function isMonth($string) {
        $monthToNumber = self::monthToNumber($string);
        return is_numeric($monthToNumber) && $monthToNumber != $string;
    }

    /**
     * Converts a month to the number corresponding to the month.
     *
     * @param $month - The month to be converted
     * @return array - Processed collection of months
     */
    public static function monthToNumber($month) {
        foreach(self::MONTHS_IN_LANG as $monthRegex) {
            $month = preg_replace($monthRegex, self::MONTH_NUMBERS, $month);
        }

        return $month;
    }

    /**
     * Tests if a string is a valid era.
     *
     * @param $string - Era string
     * @return bool - True if valid
     */
    public static function isValidEra($string) {
        $string = strtoupper($string);
        $eras = array("CE", "BCE", "BP", "KYA BP");
        return in_array($string,$eras);
    }

    /**
     * Test if a string is equal to circa.
     *
     * @param $string - Circa string
     * @return bool - True if valid
     */
    public static function isCirca($string) {
        $string = strtoupper($string);
        return ($string == "CIRCA");
    }
}
