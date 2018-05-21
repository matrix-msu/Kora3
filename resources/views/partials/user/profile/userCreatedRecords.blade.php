<div class="record card all  {{ $index == 0 ? 'active' : '' }}" id="{{$record->id}}">
    <div class="header  {{ $index == 0 ? 'active' : '' }}">
        <div class="left pl-m">
            <a class="title underline-middle-hover" href="{{ action("RecordController@show",
                ["pid" => $record->pid, "fid" => $record->fid, "rid" => $record->rid]) }}">
                <span class="name">{{$record->kid}}</span>
                <i class="icon icon-arrow-right"></i>
            </a>
        </div>

        <div class="card-toggle-wrap">
            <a href="#" class="card-toggle card-toggle-js">
                <i class="icon icon-chevron  {{ $index == 0 ? 'active' : '' }}"></i>
            </a>
        </div>
    </div>

    <div class="content  {{ $index == 0 ? 'active' : '' }}">
        <div class="description">
            @foreach(\App\Http\Controllers\PageController::getFormLayout($record->fid) as $index=>$page)
                <section class="record-page {{ ($index == 0 ? '' : 'mt-xxxl') }}">
                    <div class="record-page-title">{{$page["title"]}}</div>
                    <div class="record-page-spacer mt-xs"></div>
                    @if($page["fields"]->count() > 0)
                        @foreach($page["fields"] as $field)
                            @if($field->viewresults)
                                <div class="field-title mt-xl">{{$field->name}}: </div>

                                <section class="field-data">
                                    <?php $typedField = $field->getTypedFieldFromRID($record->rid); ?>
                                    @if(!is_null($typedField))
                                        @include($typedField::FIELD_DISPLAY_VIEW, ['field' => $field, 'typedField' => $typedField])
                                    @else
                                        <span class="record-no-data">No Data Inputted</span>
                                    @endif
                                </section>
                            @endif
                        @endforeach
                    @else
                        <div class="field-title no-field mt-xl">No fields added to this page</div>
                    @endif
                </section>
            @endforeach
        </div>

        <div class="footer">
            <a class="quick-action trash-container left danger delete-record-js tooltip" rid="{{$record->rid}}" href="#" tooltip="Delete Field">
                <i class="icon icon-trash"></i>
            </a>

            <a class="quick-action underline-middle-hover" href="{{action('RevisionController@show',
                        ['pid' => $record->pid, 'fid' => $record->fid, 'rid' => $record->rid])}}">
                <i class="icon icon-clock-little"></i>
                <span>View Revisions</span>
            </a>

            <a class="quick-action underline-middle-hover" href="{{action('RecordController@cloneRecord', [
                        'pid' => $record->pid, 'fid' => $record->fid, 'rid' => $record->rid])}}">
                <i class="icon icon-duplicate-little"></i>
                <span>Duplicate Records</span>
            </a>

            <a class="quick-action underline-middle-hover" href="{{ action('RecordController@edit',
                        ['pid' => $record->pid, 'fid' => $record->fid, 'rid' => $record->rid]) }}">
                <i class="icon icon-edit-little"></i>
                <span>Edit</span>
            </a>

            <a class="quick-action underline-middle-hover" href="{{ action("RecordController@show",
                ["pid" => $record->pid, "fid" => $record->fid, "rid" => $record->rid]) }}">
                <span>View Record</span>
                <i class="icon icon-arrow-right"></i>
            </a>
        </div>
    </div>
</div>