@extends('fields.show')

@section('fieldOptions')
    <div class="form-group">
        {!! Form::label('','Default Associations') !!}
    </div>

    <div class="associator-section">
        <div class="form-group mt-xl">
            {!! Form::label('search','Search Associations') !!}
            <input type="text" class="text-input assoc-search-records-js" placeholder="Enter search term or KID to find associated records (populated below)">

            <p class="sub-text mt-sm">
                Enter a search term or KID and hit enter to search. Results will then be populated in the "Association Results" field below.
            </p>
        </div>

        <div class="form-group mt-xl">
            {!! Form::label('search','Association Results') !!}
            {!! Form::select('search[]', [], null, ['class' => 'multi-select assoc-select-records-js', 'multiple',
                "data-placeholder" => "Select a record association to add to defaults"]) !!}

            <p class="sub-text mt-sm">
                Once records are populated, they will appear in this fields dropdown. Selecting records will then add them to the "Default Associations" field below.
            </p>
        </div>

        <div class="form-group mt-xl">
            {!! Form::label('default','Select Default Associations') !!}
            {!! Form::select('default[]', \App\AssociatorField::getAssociatorList($field), \App\AssociatorField::getAssociatorList($field),
                ['class' => 'multi-select assoc-default-records-js', 'multiple', "data-placeholder" => "Search below to add associated records"]) !!}

            <p class="sub-text mt-sm">
                To add associated records, Start a search for records in the "Search Associations" field above.
            </p>
        </div>
    </div>

    <div class="form-group mt-xxxl">{!! Form::label('','Association Search Configuration') !!}</div>

	<?php $associations = \App\Http\Controllers\AssociationController::getAvailableAssociations($field->fid); ?>
    <div class="associator-section {{count($associations) == 0 ? 'search-config-empty-state' : ''}}">
        @foreach($associations as $a)
            <?php
                $f = \App\Http\Controllers\FormController::getForm($a->dataForm);
                $formFieldsData = \App\Field::where('fid','=',$f->fid)->get()->all();
                $formFields = array();
                foreach($formFieldsData as $fl) {
                    $formFields[$fl->flid] = $fl->name;
                }

                //get layout info for this form
                if(array_key_exists($f->fid,$opt_layout)){
                    $f_check = $opt_layout[$f->fid]['search'];
                    $f_flids = $opt_layout[$f->fid]['flids'];
                }else{
                    $f_check = false;
                    $f_flids = null;
                }

                $selectArray = ['class' => 'single-select', "data-placeholder" => "Select field preview value", 'disabled'];
                if($f_check)
                    $selectArray = ['class' => 'single-select', "data-placeholder" => "Select field preview value"];
            ?>

            <div class="form-group mt-xl">
                <div class="check-box-half">
                    <input type="checkbox" value="1" id="active" class="check-box-input association-check-js" name="checkbox_{{$f->fid}}"
                    @if($f_check)
                        checked
                    @endif
                    />
                    <span class="check"></span>
                    <span class="placeholder">Search through {{$f->name}}?</span>
                </div>
            </div>

            <div class="form-group mt-m
            @if(!$f_check)
            hidden
            @endif
            ">
                {!! Form::label('preview_'.$f->fid, 'Preview Value') !!}
                {{-- {!! Form::select('preview_'.$f->fid, $formFields, $f_flids, $selectArray) !!} --}}
                {!! Form::select('preview_'.$f->fid, $formFields, $f_flids, ['class' => 'multi-select assoc-preview-js', 'multiple', "data-placeholder" => "Select field preview value"]) !!}
            </div>
        @endforeach
		@if(count($associations) == 0) No Forms Associated @endif
    </div>

    <input name="flids" type="hidden" value="">

    <div class="form-group mt-sm">
        <p class="sub-text">
            If no forms are available, have a Form Admin request permission to forms by using the Association Permissions page.
        </p>
    </div>

@stop

@section('fieldOptionsJS')
    assocSearchURI = "{{ action('AssociatorSearchController@assocSearch',['pid' => $field->pid,'fid'=>$field->fid, 'flid'=>$field->flid]) }}";
    csfrToken = "{{ csrf_token() }}";

    Kora.Fields.Options('Associator');
@stop