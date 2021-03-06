@php $url = action('RevisionController@rollback'); @endphp
<div class="modal modal-js modal-mask reactivate-record-modal-js">
    <div class="content">
        <div class="header">
            <span class="title">Re-Activate Record?</span>
            <a href="#" class="modal-toggle modal-toggle-js">
                <i class="icon icon-cancel"></i>
            </a>
        </div>
        <div class="body">
            <p>
                Are you sure you want to reactivate this record and restore these fields back to the edits made on <span class="date-time"></span>? 
                Don't worry, you can always restore them back to their current state as well.
            </p>
            <div class="form-group mt-xxl">
                <a href="{{$url}}" class="btn reactivate-record-button-js">Re-Activate Record</a>
            </div>
        </div>
    </div>
</div>