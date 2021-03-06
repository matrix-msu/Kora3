@extends('app', ['page_title' => (Auth::user()->id == $user->id ? 'My Profile' : $user->username), 'page_class' => 'user-profile'])

@section('aside-content')
  @include('partials.sideMenu.dashboard', ['openDashboardDrawer' => false, 'openProjectDrawer' => false])
@stop

@section('header')
    @include('partials.user.profile.head')
@stop

@section('body')
    @include('partials.revisions.modals.restoreFieldsModal')
    @include('partials.revisions.modals.reactivateRecordModal')
    <section class="center page-section page-section-js active" id="recordHistory">
        <div class="section-filters mt-xxxl">
            <a href="#rm" class="filter-link select-content-section-js underline-middle underline-middle-hover {{$sec == 'rm' ? 'active' : ''}}">Recently Modified</a>
            <a href="#mcr" class="filter-link select-content-section-js underline-middle underline-middle-hover {{$sec == 'mcr' ? 'active' : ''}}">@if (Auth::user()->id == $user->id) My @else {{$user->username}}'s @endif Created Records</a>
        </div>
        <div class="content-section content-section-js {{$sec == 'rm' ? 'active' : ''}}" id="rm">
          <div class="content-sections-scroll">
            @if (count($userRevisions) > 0)
                <div class="my-xl">
                    <p>@if (Auth::user()->id == $user->id) You have @else {{$user->preferences['first_name']}} has @endif recently modified the following {{$rmCount}} records...</p>
                </div>

                @include('partials.user.profile.filters')

                @foreach ($userRevisions as $index=>$revision)
                    @include('partials.user.profile.userRevision')
                @endforeach

                @include('partials.user.profile.pagination', ['totalCount' => $rmCount])
            @else
                <div class="my-xl">
                    <p>@if (Auth::user()->id == $user->id) You have @else {{$user->preferences['first_name']}} has @endif recently modified {{$rmCount}} records...</p>
                </div>
            @endif
          </div>
        </div>

        <div class="content-section content-section-js {{$sec == 'mcr' ? 'active' : ''}}" id="mcr">
          <div class="content-sections-scroll">
            @if(count($userCreatedRecords) > 0)
                <div class="my-xl">
                    <p>@if (Auth::user()->id == $user->id) You have @else {{$user->preferences['first_name']}} has @endif created the following {{$mcrCount}} records...</p>
                </div>

                @include('partials.user.profile.filters')

                @foreach ($userCreatedRecords as $index=>$kid)
                    @include('partials.user.profile.userCreatedRecords')
                @endforeach

                @include('partials.user.profile.pagination', ['totalCount' => $mcrCount])
            @else
                <div class="my-xl">
                    <p>@if (Auth::user()->id == $user->id) You have @else {{$user->preferences['first_name']}} has @endif created {{$mcrCount}} records...</p>
                </div>
            @endif
          </div>
        </div>
    </section>
@stop


@section('javascripts')
    @include('partials.user.javascripts')

    <script type="text/javascript">
        Kora.User.Profile();
    </script>
@stop