<form class=navbar-form" role="search">
<div class="form-group">

    <div class="input-group">
        <span class="input-group-addon">{{trans('partials_changeprofile.user')}}</span>
        <input name="username" type="text" class="form-control" disabled value="{{Auth::user()->username}}">
    </div>
    <br>
    <div class="input-group">
        <span class="input-group-addon">{{trans('partials_changeprofile.email')}}</span>
        <input name="email" type="text" class="form-control" disabled value="{{Auth::user()->email}}">
    </div>
    <br>
    <div class="input-group">
        <span class="input-group-addon">{{trans('partials_changeprofile.name')}}</span>
        <input id="realname" name="realname" type="text" class="form-control" placeholder="{{Auth::user()->name}}">
        <span class="input-group-btn">
          <button onclick="updateRealName()" class="btn btn-default" type="button">{{trans('partials_changeprofile.update')}}</button>
        </span>
    </div>
    <br>
    <div class="input-group">
        <span class="input-group-addon">{{trans('partials_changeprofile.org')}}</span>
        <input id="organization" name="organization" type="text" class="form-control" placeholder="{{Auth::user()->organization}}">
        <span class="input-group-btn">
          <button onclick="updateOrganization()" class="btn btn-default" type="button">{{trans('partials_changeprofile.update')}}</button>
        </span>
    </div>
    <br>
    <div class="input-group">
        <span class="input-group-addon">{{trans('partials_changeprofile.lan')}}</span>
        <input type="text" class="form-control" disabled value="{{Auth::user()->language}}">
        <div class="input-group-btn">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{trans('partials_changeprofile.select')}} <span class="caret"></span></button>
            <ul class="dropdown-menu dropdown-menu-right">
                @foreach($languages_available as $lang)
                    <li><a onclick="updateLanguage('{{$lang[0]}}')" href="#en">{{$lang[1]}}</a> </li>
                @endforeach
            </ul>

        </div><!-- /btn-group -->
    </div><!-- /input-group -->

</div>

</form>

