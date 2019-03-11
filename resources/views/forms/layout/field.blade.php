<div class="field card {{ $index == 0 ? 'active' : '' }}" id="{{$field->flid}}" 
  delete-url="{{action('FieldController@destroy', ['pid' => $field->pid, 'fid' => $field->fid, 'flid' => $field->flid])}}"
  move-url="{{action('PageController@moveField', ['pid' => $field->pid, 'fid' => $field->fid, 'flid' => $field->flid])}}"
  sequence="{{$field->sequence}}"
  >
  <div class="header {{ $index == 0 ? 'active' : '' }}">
    <div class="left {{ $onFormPage ? null : 'pl-m' }}">
      @if(\Auth::user()->canEditForms(\App\Http\Controllers\ProjectController::getProject($field->pid)) && $onFormPage)
        <div class="move-actions">
          <a class="action move-action-js up-js" href="">
            <i class="icon icon-arrow-up"></i>
          </a>

          <a class="action move-action-js down-js" href="">
            <i class="icon icon-arrow-down"></i>
          </a>
        </div>
      @endif

      @if($field->type=='Associator' and sizeof(\App\Http\Controllers\AssociationController::getAvailableAssociations($field->fid))==0)
        {{-- TODO: Change this to indicate action needs to be taken --}}
        <a class="title underline-middle-hover" href="{{ action('FieldController@show', ['pid' => $form->pid, 'fid' => $form->fid, 'flid' => $field->flid]) }}">
          <span class="name">{{$field->name}}</span>
          <i class="icon icon-arrow-right"></i>
        </a>
      @elseif(\Auth::user()->canEditFields($form))
        <a class="title underline-middle-hover" href="{{ action('FieldController@show',['pid' => $form->pid, 'fid' => $form->fid, 'flid' => $field->flid]) }}">
          <span class="name">{{$field->name}}</span>
          <i class="icon icon-arrow-right"></i>
        </a>
      @else
        <a class="title inactive underline-middle-hover" href="#">
          <span class="name">{{$field->name}}</span>
        </a>
      @endif
    </div>

    <div class="card-toggle-wrap">
      <a href="#" class="card-toggle field-toggle-js">
        <span class="chevron-text">{{$field->type}}</span>
        <i class="icon icon-chevron {{ $index == 0 ? 'active' : '' }}"></i>
      </a>
    </div>
  </div>

  <div class="content content-js {{ $index == 0 ? 'active' : '' }}">
    <div class="id">
      <span class="attribute">Unique Field ID: </span>
      <span>{{$field->slug}}</span>
    </div>

    <div class="description">
      {{$field->desc}}
    </div>

    <div class="allowed-actions" update-flag-url="{{ action('FieldController@updateFlag', ['pid' => $field->pid, 'fid' => $field->fid, 'flid' => $field->flid]) }}">
      <div class="form-group action">
        <div class="action-column">
          <div class="check-box-half check-box-rectangle">
            <input type="checkbox"
              @if ($field->required)
                checked
              @endif
              class="check-box-input preset-input-js"
              name="required"
              />
            <span class="check"></span>
            <span class="placeholder">Required</span>
          </div>
          <div class="check-box-half check-box-rectangle">
            <input type="checkbox"
              @if ($field->viewable)
                checked
              @endif
              class="check-box-input preset-input-js"
              name="viewable"
            />
            <span class="check"></span>
            <span class="placeholder">Viewable</span>
          </div>
        </div>
      </div>

      <div class="form-group action">
        <div class="action-column">
         <div class="check-box-half check-box-rectangle">
            <input type="checkbox"
              @if ($field->searchable)
                checked
              @endif
              class="check-box-input preset-input-js"
              name="searchable"
              />
            <span class="check"></span>
            <span class="placeholder">Searchable</span>
          </div>
          <div class="check-box-half check-box-rectangle">
            <input type="checkbox"
              @if ($field->viewresults)
                checked
              @endif
              class="check-box-input preset-input-js"
              name="viewresults"
            />
            <span class="check"></span>
            <span class="placeholder">Viewable in Results</span>
          </div> 
        </div>
      </div>

      <div class="form-group action">
        <div class="action-column">
         <div class="check-box-half check-box-rectangle">
            <input type="checkbox"
              @if ($field->extsearch)
                checked
              @endif
              class="check-box-input preset-input-js"
              name="extsearch"
              />
            <span class="check"></span>
            <span class="placeholder">Externally Searchable</span>
          </div>
          <div class="check-box-half check-box-rectangle">
            <input type="checkbox"
              @if ($field->extview)
                checked
              @endif
              class="check-box-input preset-input-js"
              name="extview"
            />
            <span class="check"></span>
            <span class="placeholder">Externally Viewable</span>
          </div> 
        </div>
      </div>

      <div class="form-group action">
        <div class="action-column">
          <div class="check-box-half check-box-rectangle">
            <input type="checkbox"
              @if ($field->advsearch)
                checked
              @endif
              class="check-box-input preset-input-js"
              name="advsearch"
            />
            <span class="check"></span>
            <span class="placeholder">Advanced Searchable</span>
          </div> 
        </div>
      </div>
    </div>

    <div class="footer">
      @if(\Auth::user()->canDeleteFields($form))
        <a class="quick-action delete-field delete-field-js left tooltip" href="#" tooltip="Delete Field">
          <i class="icon icon-trash"></i>
        </a>
      @endif

      @if(\Auth::user()->canEditFields($form))
        <a class="quick-action underline-middle-hover" href="{{ action('FieldController@show',['pid' => $form->pid, 'fid' => $form->fid, 'flid' => $field->flid]) }}">
          <span>View Field Options</span>
          <i class="icon icon-arrow-right"></i>
        </a>
      @endif
    </div>
  </div>
</div>
