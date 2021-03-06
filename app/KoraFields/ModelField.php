<?php namespace App\KoraFields;

use Illuminate\Http\Request;

class ModelField extends FileTypeField {

    /*
    |--------------------------------------------------------------------------
    | Model Field
    |--------------------------------------------------------------------------
    |
    | This model represents the 3d-model field in kora
    |
    */

    /**
     * @var string - Views for the typed field options
     */
    const FIELD_OPTIONS_VIEW = "partials.fields.options.3dmodel";
    const FIELD_ADV_OPTIONS_VIEW = "partials.fields.advanced.3dmodel";
    const FIELD_ADV_INPUT_VIEW = null;
    const FIELD_INPUT_VIEW = "partials.records.input.3dmodel";
    const FIELD_DISPLAY_VIEW = "partials.records.display.3dmodel";

    /**
     * @var array - Supported file types in this field
     */
    const SUPPORTED_TYPES = ['obj','stl','application/octet-stream','image/jpeg','image/png'];

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
     * @param  Request $request
     * @return array - The default options
     */
    public function getDefaultOptions($type = null) {
        return ['FieldSize' => '', 'MaxFiles' => '', 'FileTypes' => self::SUPPORTED_TYPES,
            'ModelColor' => '#ddd', 'BackColorOne' => '#2E4F5E', 'BackColorTwo' => '#152730'];
    }

    /**
     * Update the options for a field.
     *
     * NOTE:: Overwrite to support color field options
     *
     * @param  array $field - Field to update options
     * @param  Request $request
     * @param  int $flid - The field internal name
     * @return array - The updated field array
     */
    public function updateOptions($field, Request $request, $flid = null, $prefix = 'records_') {
        $field = parent::updateOptions($field, $request, $flid);
        $field['options']['ModelColor'] = $request->color;
        $field['options']['BackColorOne'] = $request->backone;
        $field['options']['BackColorTwo'] = $request->backtwo;

        return $field;

    }

    ///////////////////////////////////////////////END ABSTRACT FUNCTIONS///////////////////////////////////////////////

    /**
     * Returns default mime list, if file types not saved in field options.
     *
     * @param  int $flid - File field that record file will be loaded to
     * @return array - The list
     */
    public function getDefaultMIMEList() {
        return self::SUPPORTED_TYPES;
    }
}
