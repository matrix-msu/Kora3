@extends('app')

@section('leftNavLinks')
    @include('partials.menu.project', ['pid' => $form->pid])
    @include('partials.menu.form', ['pid' => $form->pid, 'fid' => $form->fid])
@stop

@section('aside-content')
  @include('partials.sideMenu.form', ['pid' => $form->pid, 'fid' => $form->fid, 'openDrawer' => true])
@stop

@section('content')
    <span><h1>{{ $form->name }}</h1></span>

    <div><b>{{trans('records_index.name')}}:</b> {{ $form->slug }}</div>
    <div><b>{{trans('records_index.desc')}}:</b> {{ $form->description }}</div>

    @if(\Auth::user()->canIngestRecords($form))
        <a href="{{ action('RecordController@index',['pid' => $form->pid, 'fid' => $form->fid]) }}">[{{trans('records_index.records')}}]</a>
        <a href="{{ action('RecordController@create',['pid' => $form->pid, 'fid' => $form->fid]) }}">[{{trans('records_index.new')}}]</a>
        <a href="{{ action('RecordController@importRecordsView',['pid' => $form->pid, 'fid' => $form->fid]) }}">[{{trans('forms_show.import')}}]</a>
    @endif
    @if(\Auth::user()->canModifyRecords($form))
        <a href="{{ action('RecordController@showMassAssignmentView',['pid' => $form->pid, 'fid' => $form->fid]) }}">[{{trans('records_index.mass')}}]</a>
    @endif

    <hr/>

    @include('search.bar', ['pid' => $form->pid, 'fid' => $form->fid])

    @if (\Auth::user()->admin || \Auth::user()->isFormAdmin($form))
        <hr/>

        <h4> {{trans('records_index.panel')}}</h4>
        <form action="{{action('FormGroupController@index', ['pid'=>$form->pid, 'fid'=>$form->fid])}}" style="display: inline">
            <button type="submit" class="btn btn-default">{{trans('records_index.groups')}}</button>
        </form>
        <form action="{{action('AssociationController@index', ['fid'=>$form->fid, 'pid'=>$form->pid])}}" style="display: inline">
            <button type="submit" class="btn btn-default">{{trans('records_index.assoc')}}</button>
        </form>
        <form action="{{action('RevisionController@index', ['pid'=>$form->pid, 'fid'=>$form->fid])}}" style="display: inline">
            <button type="submit" class="btn btn-default">{{trans('records_index.revisions')}}</button>
        </form>
        <form action="{{action('RecordPresetController@index', ['pid'=>$form->pid, 'fid'=>$form->fid])}}" style="display: inline">
            <button type="submit" class="btn btn-default">{{trans('records_index.presets')}}</button>
        </form>
        <div>
            <button class="btn btn-danger" onclick="deleteAll()">{{trans('records_index.delete')}}</button>
            <button class="btn btn-danger" onclick="cleanUp()">{{trans('records_index.cleanup')}}</button>
            <span><b>{{trans('records_index.size')}}:</b> {{$filesize}}</span>
        </div>
    @endif

    <hr/>

    {{--<div style="text-align: left">{!! $records->render() !!}</div>--}}

    <h2>{{trans('records_index.records')}}</h2>
    <div>{{trans('records_index.total')}}: {{$rid_paginator->total()}}</div>
    @if(\Auth::user()->admin || \Auth::user()->isFormAdmin($form))

        @if ($rid_paginator->total() > 0)
        <form action="{{ action('FormSearchController@deleteSubset', ['pid' => $form->pid, 'fid' => $form->fid]) }}">
            <button type="submit" class="btn btn-danger">{{ trans('search.deleteSubset') }}</button>
        </form>
        @endif

        <div>
            {{trans('records_index.exportRec')}}:
            <a href="{{ action('ExportController@exportRecords',['pid' => $form->pid, 'fid' => $form->fid, 'type'=>'xml']) }}">[XML]</a>
            <a href="{{ action('ExportController@exportRecords',['pid' => $form->pid, 'fid' => $form->fid, 'type'=>'json']) }}">[JSON]</a>
            @if(file_exists(storage_path('app/files/p'.$form->pid.'/f'.$form->fid.'/')))
                <a href="{{ action('ExportController@exportRecordFiles',['pid' => $form->pid, 'fid' => $form->fid]) }}">[{{trans('records_index.exportFiles')}}]</a>
            @endif
        </div> <br>
    @endif

    <div id="slideme">

        @include('pagination.records', ['object' => $rid_paginator])

        @foreach($records as $record)
            <div class="panel panel-default">
                <div>
                    <b>{{trans('records_index.record')}}:</b> <a href="{{ action('RecordController@show',['pid' => $form->pid, 'fid' => $form->fid, 'rid' => $record->rid]) }}">{{ $record->kid }}</a>
                </div>
                @foreach($form->fields as $field)
                    @if($field->viewresults)
                    <div>
                        <span><b>{{ $field->name }}:</b> </span>
                    <span>
                        @if($field->type=='Text')
                            @foreach($record->textfields as $tf)
                                @if($tf->flid == $field->flid)
                                    @if(\App\Http\Controllers\FieldController::getFieldOption($field,'MultiLine')==1)
                                        <br>
                                        <?php echo nl2br($tf->text) ?>
                                    @else
                                        {{ $tf->text }}
                                    @endif
                                @endif
                            @endforeach
                        @elseif($field->type=='Rich Text')
                            @foreach($record->richtextfields as $rtf)
                                @if($rtf->flid == $field->flid)
                                    <?php echo $rtf->rawtext ?>
                                @endif
                            @endforeach
                        @elseif($field->type=='Number')
                            @foreach($record->numberfields as $nf)
                                @if($nf->flid == $field->flid)
                                    <?php
                                    echo $nf->number + 0;
                                    if($nf->number!='')
                                        echo ' '.\App\Http\Controllers\FieldController::getFieldOption($field,'Unit');
                                    ?>
                                @endif
                            @endforeach
                        @elseif($field->type=='List')
                            @foreach($record->listfields as $lf)
                                @if($lf->flid == $field->flid)
                                    {{  $lf->option }}
                                @endif
                            @endforeach
                        @elseif($field->type=='Multi-Select List')
                            @foreach($record->multiselectlistfields as $mslf)
                                @if($mslf->flid == $field->flid)
                                    @foreach(explode('[!]',$mslf->options) as $opt)
                                        <div>{{ $opt }}</div>
                                    @endforeach
                                @endif
                            @endforeach
                        @elseif($field->type=='Generated List')
                            @foreach($record->generatedlistfields as $glf)
                                @if($glf->flid == $field->flid)
                                    @foreach(explode('[!]',$glf->options) as $opt)
                                        <div>{{ $opt }}</div>
                                    @endforeach
                                @endif
                            @endforeach
                        @elseif($field->type=='Combo List')
                            @foreach($record->combolistfields as $clf)
                                @if($clf->flid == $field->flid)
                                    <?php
                                    $cmbName1 = \App\ComboListField::getComboFieldName($field,'one');
                                    $cmbName2 = \App\ComboListField::getComboFieldName($field,'two');

                                    $oneType = \App\ComboListField::getComboFieldType($field,'one');
                                    $twoType = \App\ComboListField::getComboFieldType($field,'two');

                                    $valArray = \App\ComboListField::dataToOldFormat($clf->data()->get());
                                    ?>
                                    <div style="overflow: auto">
                                        <div>
                                            <span style="float:left;width:50%;margin-bottom:10px"><b>{{$cmbName1}}</b></span>
                                            <span style="float:left;width:50%;margin-bottom:10px"><b>{{$cmbName2}}</b></span>
                                        </div>
                                        @for($i=0;$i<sizeof($valArray);$i++)
                                            <div>
                                                @if($oneType=='Text' | $oneType=='List')
                                                    <?php $value1 = explode('[!f1!]',$valArray[$i])[1]; ?>
                                                    <span style="float:left;width:50%;margin-bottom:10px">{{$value1}}</span>
                                                @elseif($oneType=='Number')
                                                    <?php
                                                    $value1 = explode('[!f1!]',$valArray[$i])[1];
                                                    $unit = \App\ComboListField::getComboFieldOption($field,'Unit','one');
                                                    if($unit!=null && $unit!=''){
                                                        $value1 .= ' '.$unit;
                                                    }
                                                    ?>
                                                    <span style="float:left;width:50%;margin-bottom:10px">{{$value1}}</span>
                                                @elseif($oneType=='Multi-Select List' | $oneType=='Generated List')
                                                    <?php
                                                    $value1 = explode('[!f1!]',$valArray[$i])[1];
                                                    $value1Array = explode('[!]',$value1);
                                                    ?>

                                                    <span style="float:left;width:50%;margin-bottom:10px">
                                                        @foreach($value1Array as $val)
                                                            <div>{{$val}}</div>
                                                        @endforeach
                                                    </span>
                                                @endif

                                                @if($twoType=='Text' | $twoType=='List')
                                                    <?php $value2 = explode('[!f2!]',$valArray[$i])[1]; ?>
                                                    <span style="float:left;width:50%;margin-bottom:10px">{{$value2}}</span>
                                                @elseif($twoType=='Number')
                                                    <?php
                                                    $value2 = explode('[!f2!]',$valArray[$i])[1];
                                                    $unit = \App\ComboListField::getComboFieldOption($field,'Unit','two');
                                                    if($unit!=null && $unit!=''){
                                                        $value2 .= ' '.$unit;
                                                    }
                                                    ?>
                                                    <span style="float:left;width:50%;margin-bottom:10px">{{$value2}}</span>
                                                @elseif($twoType=='Multi-Select List' | $twoType=='Generated List')
                                                    <?php
                                                    $value2 = explode('[!f2!]',$valArray[$i])[1];
                                                    $value2Array = explode('[!]',$value2);
                                                    ?>

                                                    <span style="float:left;width:50%;margin-bottom:10px">
                                                        @foreach($value2Array as $val)
                                                            <div>{{$val}}</div>
                                                        @endforeach
                                                    </span>
                                                @endif
                                            </div>
                                        @endfor
                                    </div>
                                @endif
                            @endforeach
                        @elseif($field->type=='Date')
                            @foreach($record->datefields as $df)
                                @if($df->flid == $field->flid)
                                    @if($df->circa==1 && \App\Http\Controllers\FieldController::getFieldOption($field,'Circa')=='Yes')
                                        {{'circa '}}
                                    @endif
                                    @if($df->month==0 && $df->day==0)
                                        {{$df->year}}
                                    @elseif($df->day==0)
                                        {{ $df->month.' '.$df->year }}
                                    @elseif(\App\Http\Controllers\FieldController::getFieldOption($field,'Format')=='MMDDYYYY')
                                        {{$df->month.'-'.$df->day.'-'.$df->year}}
                                    @elseif(\App\Http\Controllers\FieldController::getFieldOption($field,'Format')=='DDMMYYYY')
                                        {{$df->day.'-'.$df->month.'-'.$df->year}}
                                    @elseif(\App\Http\Controllers\FieldController::getFieldOption($field,'Format')=='YYYYMMDD')
                                        {{$df->year.'-'.$df->month.'-'.$df->day}}
                                    @endif
                                    @if(\App\Http\Controllers\FieldController::getFieldOption($field,'Era')=='Yes')
                                        {{' '.$df->era}}
                                    @endif
                                @endif
                            @endforeach
                        @elseif($field->type=='Schedule')
                            @if(\App\Http\Controllers\FieldController::getFieldOption($field,'Calendar')=='No')
                                @foreach($record->schedulefields as $sf)
                                    @if($sf->flid == $field->flid)
                                        @foreach(App\ScheduleField::eventsToOldFormat($sf->events()->get()) as $event)
                                            <div>{{ $event }}</div>
                                        @endforeach
                                    @endif
                                @endforeach
                            @else
                                @foreach($record->schedulefields as $sf)
                                    @if($sf->flid == $field->flid)
                                        <div id='calendar{{$field->flid.'_'.$record->rid}}'></div>
                                        <script>
                                            $('#calendar{{$field->flid.'_'.$record->rid}}').fullCalendar({
                                                header: {
                                                    left: 'prev,next today',
                                                    center: 'title',
                                                    right: 'month,agendaWeek,agendaDay'
                                                },
                                                events: [
                                                        @foreach(App\ScheduleField::eventsToOldFormat($sf->events()->get()) as $event)
                                                    {
                                                        <?php
                                                                $nameTime = explode(': ',$event);
                                                                $times = explode(' - ',$nameTime[1]);
                                                                $allDay = true;
                                                                if(strpos($nameTime[1],'PM') | strpos($nameTime[1],'AM')){
                                                                    $allDay = false;
                                                                }
                                                                ?>
                                                        title: '{{ $nameTime[0] }}',
                                                        start: '{{ $times[0] }}',
                                                        end: '{{ $times[1] }}',
                                                        @if($allDay)
                                                        allDay: true
                                                        @else
                                                        allDay: false
                                                        @endif
                                                    },
                                                    @endforeach
                                                ]
                                            });
                                        </script>
                                    @endif
                                @endforeach
                            @endif
                        @elseif($field->type=='Geolocator')
                            @if(\App\Http\Controllers\FieldController::getFieldOption($field,'Map')=='No')
                                @foreach($record->geolocatorfields as $gf)
                                    @if($gf->flid == $field->flid)
                                        @foreach(App\GeolocatorField::locationsToOldFormat($gf->locations()->get()) as $opt)
                                            @if(\App\Http\Controllers\FieldController::getFieldOption($field,'DataView')=='LatLon')
                                                <div>{{ explode('[Desc]',$opt)[1].': '.explode('[LatLon]',$opt)[1] }}</div>
                                            @elseif(\App\Http\Controllers\FieldController::getFieldOption($field,'DataView')=='UTM')
                                                <div>{{ explode('[Desc]',$opt)[1].': '.explode('[UTM]',$opt)[1] }}</div>
                                            @elseif(\App\Http\Controllers\FieldController::getFieldOption($field,'DataView')=='Textual')
                                                <div>{{ explode('[Desc]',$opt)[1].': '.explode('[Address]',$opt)[1] }}</div>
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach
                            @else
                                @foreach($record->geolocatorfields as $gf)
                                    @if($gf->flid == $field->flid)
                                        <div id="map{{$field->flid.'_'.$record->rid}}" style="height:270px;"></div>
                                        <?php $locs = array(); ?>
                                        @foreach(App\GeolocatorField::locationsToOldFormat($gf->locations()->get()) as $location)
                                            <?php
                                            $loc = array();
                                            $desc = explode('[Desc]',$location)[1];
                                            $x = explode(',', explode('[LatLon]',$location)[1])[0];
                                            $y = explode(',', explode('[LatLon]',$location)[1])[1];

                                            $loc['desc'] = $desc;
                                            $loc['x'] = $x;
                                            $loc['y'] = $y;

                                            array_push($locs,$loc);
                                            ?>
                                        @endforeach
                                        <script>
                                            var map{{$field->flid.'_'.$record->rid}} = L.map('map{{$field->flid.'_'.$record->rid}}').setView([{{$locs[0]['x']}}, {{$locs[0]['y']}}], 13);
                                            L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png?{foo}', {foo: 'bar'}).addTo(map{{$field->flid.'_'.$record->rid}});
                                                    @foreach($locs as $loc)
                                            var marker = L.marker([{{$loc['x']}}, {{$loc['y']}}]).addTo(map{{$field->flid.'_'.$record->rid}});
                                            marker.bindPopup("{{$loc['desc']}}");
                                            @endforeach
                                        </script>
                                    @endif
                                @endforeach
                            @endif
                        @elseif($field->type=='Documents')
                            @foreach($record->documentsfields as $df)
                                @if($df->flid == $field->flid)
                                    @foreach(explode('[!]',$df->documents) as $opt)
                                        @if($opt != '')
                                            <?php
                                            $name = explode('[Name]',$opt)[1];
                                            $link = action('FieldAjaxController@getFileDownload',['flid' => $field->flid, 'rid' => $record->rid, 'filename' => $name]);
                                            ?>
                                            <div><a href="{{$link}}">{{$name}}</a></div>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                        @elseif($field->type=='Gallery')
                            @foreach($record->galleryfields as $gf)
                                @if($gf->flid == $field->flid)
                                    <div class="gal{{$field->flid.'_'.$record->rid}}">
                                        @foreach(explode('[!]',$gf->images) as $img)
                                            @if($img != '')
                                                <?php
                                                $name = explode('[Name]',$img)[1];
                                                $link = action('FieldAjaxController@getImgDisplay',['flid' => $field->flid, 'rid' => $record->rid, 'filename' => $name, 'type' => 'medium']);
                                                ?>
                                                <div><img class="img-responsive" src="{{$link}}" alt="{{$name}}"></div>
                                            @endif
                                        @endforeach
                                    </div>
                                    <script>
                                        $('.gal{{$field->flid.'_'.$record->rid}}').slick({
                                            dots: true,
                                            infinite: true,
                                            speed: 500,
                                            fade: true,
                                            cssEase: 'linear'
                                        });
                                    </script>
                                @endif
                            @endforeach
                        @elseif($field->type=='Playlist')
                            @foreach($record->playlistfields as $pf)
                                @if($pf->flid == $field->flid)
                                    <div id="jp_container_{{$field->flid.'_'.$record->rid}}" class="jp-video jp-video-270p" role="application" aria-label="media player">
                                        <div class="jp-type-playlist">
                                            <div id="jquery_jplayer_{{$field->flid.'_'.$record->rid}}" class="jp-jplayer"></div>
                                            <div class="jp-gui">
                                                <div class="jp-video-play">
                                                    <button class="jp-video-play-icon" role="button" tabindex="0">play</button>
                                                </div>
                                                <div class="jp-interface">
                                                    <div class="jp-progress">
                                                        <div class="jp-seek-bar">
                                                            <div class="jp-play-bar"></div>
                                                        </div>
                                                    </div>
                                                    <div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
                                                    <div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
                                                    <div class="jp-details">
                                                        <div class="jp-title" aria-label="title">&nbsp;</div>
                                                    </div>
                                                    <div class="jp-controls-holder">
                                                        <div class="jp-volume-controls">
                                                            <button class="jp-mute" role="button" tabindex="0">mute</button>
                                                            <button class="jp-volume-max" role="button" tabindex="0">max volume</button>
                                                            <div class="jp-volume-bar">
                                                                <div class="jp-volume-bar-value"></div>
                                                            </div>
                                                        </div>
                                                        <div class="jp-controls">
                                                            <button class="jp-previous" role="button" tabindex="0">previous</button>
                                                            <button class="jp-play" role="button" tabindex="0">play</button>
                                                            <button class="jp-stop" role="button" tabindex="0">stop</button>
                                                            <button class="jp-next" role="button" tabindex="0">next</button>
                                                        </div>
                                                        <div class="jp-toggles">
                                                            <button class="jp-full-screen" role="button" tabindex="0">full screen</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="jp-playlist">
                                                <ul>
                                                    <!-- The method Playlist.displayPlaylist() uses this unordered list -->
                                                    <li></li>
                                                </ul>
                                            </div>
                                            <div class="jp-no-solution">
                                                <span>Update Required</span>
                                                To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
                                            </div>
                                        </div>
                                    </div>
                                    <script>
                                        var cssSelector = { jPlayer: "#jquery_jplayer_{{$field->flid.'_'.$record->rid}}", cssSelectorAncestor: "#jp_container_{{$field->flid.'_'.$record->rid}}" };
                                        var playlist = [
                                                @foreach(explode('[!]',$pf->audio) as $key => $aud)
                                                @if($aud != '')
                                                <?php
                                                $name = explode('[Name]',$aud)[1];
                                                $link = url('app/files/p'.$form->pid.'/f'.$form->fid.'/r'.$record->rid.'/fl'.$field->flid.'/'.$name);
                                                ?>
                                            {
                                                title: "{{$name}}",
                                                @if(explode('[Type]',$aud)[1]=="audio/mpeg")
                                                mp3: "{{$link}}"
                                                @elseif(explode('[Type]',$aud)[1]=="audio/ogg")
                                                oga: "{{$link}}"
                                                @elseif(explode('[Type]',$aud)[1]=="audio/x-wav")
                                                wav: "{{$link}}"
                                                @endif
                                            },
                                            @endif
                                            @endforeach
                                        ];
                                        var options = {
                                            swfPath: "{{public_path('jplayer/jquery.jplayer.swf')}}",
                                            supplied: "mp3, oga, wav"
                                        };
                                        var myPlaylist = new jPlayerPlaylist(cssSelector, playlist, options);
                                    </script>
                                @endif
                            @endforeach
                        @elseif($field->type=='Video')
                            @foreach($record->videofields as $vf)
                                @if($vf->flid == $field->flid)
                                    <div id="jp_container_{{$field->flid.'_'.$record->rid}}" class="jp-video jp-video-270p" role="application" aria-label="media player">
                                        <div class="jp-type-playlist">
                                            <div id="jquery_jplayer_{{$field->flid.'_'.$record->rid}}" class="jp-jplayer"></div>
                                            <div class="jp-gui">
                                                <div class="jp-video-play">
                                                    <button class="jp-video-play-icon" role="button" tabindex="0">play</button>
                                                </div>
                                                <div class="jp-interface">
                                                    <div class="jp-progress">
                                                        <div class="jp-seek-bar">
                                                            <div class="jp-play-bar"></div>
                                                        </div>
                                                    </div>
                                                    <div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
                                                    <div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
                                                    <div class="jp-details">
                                                        <div class="jp-title" aria-label="title">&nbsp;</div>
                                                    </div>
                                                    <div class="jp-controls-holder">
                                                        <div class="jp-volume-controls">
                                                            <button class="jp-mute" role="button" tabindex="0">mute</button>
                                                            <button class="jp-volume-max" role="button" tabindex="0">max volume</button>
                                                            <div class="jp-volume-bar">
                                                                <div class="jp-volume-bar-value"></div>
                                                            </div>
                                                        </div>
                                                        <div class="jp-controls">
                                                            <button class="jp-previous" role="button" tabindex="0">previous</button>
                                                            <button class="jp-play" role="button" tabindex="0">play</button>
                                                            <button class="jp-stop" role="button" tabindex="0">stop</button>
                                                            <button class="jp-next" role="button" tabindex="0">next</button>
                                                        </div>
                                                        <div class="jp-toggles">
                                                            <button class="jp-full-screen" role="button" tabindex="0">full screen</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="jp-playlist">
                                                <ul>
                                                    <!-- The method Playlist.displayPlaylist() uses this unordered list -->
                                                    <li></li>
                                                </ul>
                                            </div>
                                            <div class="jp-no-solution">
                                                <span>Update Required</span>
                                                To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
                                            </div>
                                        </div>
                                    </div>
                                    <script>
                                        var cssSelector = { jPlayer: "#jquery_jplayer_{{$field->flid.'_'.$record->rid}}", cssSelectorAncestor: "#jp_container_{{$field->flid.'_'.$record->rid}}" };
                                        var playlist = [
                                                @foreach(explode('[!]',$vf->video) as $key => $vid)
                                                @if($vid != '')
                                                <?php
                                                $name = explode('[Name]',$vid)[1];
                                                $link = url('app/files/p'.$form->pid.'/f'.$form->fid.'/r'.$record->rid.'/fl'.$field->flid.'/'.$name);
                                                ?>
                                            {
                                                title: "{{$name}}",
                                                @if(explode('[Type]',$vid)[1]=="video/mp4")
                                                m4v: "{{$link}}"
                                                @elseif(explode('[Type]',$vid)[1]=="video/ogg")
                                                ogv: "{{$link}}"
                                                @endif
                                            },
                                            @endif
                                            @endforeach
                                        ];
                                        var options = {
                                            swfPath: "{{public_path('jplayer/jquery.jplayer.swf')}}",
                                            supplied: "m4v, ogv"
                                        };
                                        var myPlaylist = new jPlayerPlaylist(cssSelector, playlist, options);
                                    </script>
                                @endif
                            @endforeach
                        @elseif($field->type=='3D-Model')
                            @foreach($record->modelfields as $mf)
                                @if($mf->flid == $field->flid)
                                    @foreach(explode('[!]',$mf->model) as $opt)
                                        @if($opt != '')
                                            <?php
                                            $name = explode('[Name]',$opt)[1];
                                            $parts = explode('.', $name);
                                            $type = array_pop($parts);
                                            if(in_array($type, array('stl','obj')))
                                                $model_link = action('FieldAjaxController@getFileDownload',['flid' => $field->flid, 'rid' => $record->rid, 'filename' => $name]);
                                            ?>
                                        @endif
                                    @endforeach
                                    <div style="width:800px; margin:auto; position:relative;">
				                        <canvas id="cv{{$field->flid.'_'.$record->rid}}" style="border: 1px solid;" width="325" height="200">
				                            It seems you are using an outdated browser that does not support canvas :-(
				                        </canvas><br>
										<button id="cvfs{{$field->flid.'_'.$record->rid}}" type="button">FULLSCREEN</button>
				                    </div>

                                    <script type="text/javascript">
				                        var viewer = new JSC3D.Viewer(document.getElementById('cv{{$field->flid.'_'.$record->rid}}'));
                                        viewer.setParameter('SceneUrl', '{{$model_link}}');
                                        viewer.setParameter('InitRotationX', 0);
                                        viewer.setParameter('InitRotationY', 0);
                                        viewer.setParameter('InitRotationZ', 0);
                                        viewer.setParameter('ModelColor', '{{\App\Http\Controllers\FieldController::getFieldOption($field,'ModelColor')}}');
                                        viewer.setParameter('BackgroundColor1', '{{\App\Http\Controllers\FieldController::getFieldOption($field,'BackColorOne')}}');
                                        viewer.setParameter('BackgroundColor2', '{{\App\Http\Controllers\FieldController::getFieldOption($field,'BackColorTwo')}}');
                                        viewer.setParameter('RenderMode', 'texturesmooth');
                                        viewer.setParameter('MipMapping', 'on');
                                        viewer.setParameter('Renderer',         'webgl');
                                        viewer.init();
                                        viewer.update();

                                        var canvas = document.getElementById('cvfs{{$field->flid.'_'.$record->rid}}');

                                        function fullscreen() {
                                            var el = document.getElementById('cv{{$field->flid.'_'.$record->rid}}');

                                            el.width  = window.innerWidth;
                                            el.height = window.innerHeight;

                                            if(el.webkitRequestFullScreen)
                                                el.webkitRequestFullScreen();
                                            else
                                                el.mozRequestFullScreen();
                                        }

                                        function exitFullscreen() {
                                            if(!document.fullscreenElement && !document.webkitIsFullScreen && !document.mozFullScreen && !document.msFullscreenElement) {
                                                var el = document.getElementById('cv{{$field->flid.'_'.$record->rid}}');

                                                el.width  = 325;
                                                el.height = 200;
                                            }
                                        }

                                        canvas.addEventListener("click",fullscreen);
                                        document.addEventListener('fullscreenchange', exitFullscreen);
                                        document.addEventListener('webkitfullscreenchange', exitFullscreen);
                                        document.addEventListener('mozfullscreenchange', exitFullscreen);
                                        document.addEventListener('MSFullscreenChange', exitFullscreen);
				                    </script>
                                @endif
                            @endforeach
                        @elseif($field->type=='Associator')
                            @foreach($record->associatorfields as $af)
                                @if($af->flid == $field->flid)
                                    @foreach($af->records()->get() as $opt)
                                        <div>{!! $af->getPreviewValues($opt->record) !!}</div>
                                    @endforeach
                                @endif
                            @endforeach
                        @endif
                    </span>
                    </div>
                    @endif
                @endforeach
            </div>
        @endforeach

        @include('pagination.records', ['object' => $rid_paginator])

        <div class="form-group search-button-container mt-xxxl">
            <a class="btn half-sub-btn to-top">Try Another Search</a>
        </div>
    </div>

    <div style="display:none; margin-top: 1em;" id="progress" class="progress">
        <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;">
            {{trans('update_index.loading')}}
        </div>
    </div>
@stop

@section('footer')
    <script>
        /**
         * Delete all the records of a certain form.
         * Makes sure the user is REALLY sure they want to do this.
         */
        function deleteAll() {
            var encode = $('<div/>').html("{{ trans('records_index.areyousure') }}").text();
            var resp1 = confirm(encode);
            if(resp1) {
                var enc1 = $('<div/>').html("{{ trans('records_index.reallysure') }}").text();
                var enc2 = $('<div/>').html("{{ trans('records_index.reallysureplaceholder') }}").text();
                var resp2 = prompt(enc1 + '!', enc2 + '.');
                // User must literally type "DELETE" into a prompt. (Credit to Blizzard Entertainment)
                if(resp2 === 'DELETE') {

                    $("#slideme").slideToggle(2000, function() {
                        $('#progress').slideToggle(400);
                    });

                    $.ajax({
                        url: '{{ action('RecordController@deleteAllRecords', ['pid' => $form->pid, 'fid' => $form->fid]) }}',
                        type: 'DELETE',
                        data: {
                            "_token": "{{ csrf_token() }}"
                        }, success: function (response) {
                            location.reload();
                        }
                    });
                }
            }
        }

        function cleanUp() {
            var encode = $('<div/>').html('{{ trans('records_index.deletefiles') }}' + '.').text();
            var resp1 = confirm(encode);
            if (resp1) {
                $.ajax({
                    url: '{{ action('RecordController@cleanUp', ['pid' => $form->pid, 'fid' => $form->fid]) }}',
                    type: 'POST',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    }, success: function () {
                        location.reload();
                    }
                });
            }
        }
    </script>
@stop
