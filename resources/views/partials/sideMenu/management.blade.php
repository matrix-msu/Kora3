<div class="drawer-element drawer-element-js">
    <a href="#" class="drawer-toggle drawer-toggle-js">
        <i class="icon icon-gear"></i>
        <span>Management</span>
        <i class="icon icon-chevron"></i>
    </a>

    <ul class="drawer-content drawer-content-js">
        <li class="content-link head">
            <a href="{{ action('AdminController@users') }}">
                <span>User Management</span>
            </a>
        </li>

        <li class="content-link">
            <a href="{{ action('TokenController@index') }}">Token Management</a>
        </li>

        <li class="content-link">
            <a href="{{ action('InstallController@editEnvConfigs') }}">Kora Configuration File</a>
        </li>

        <li class="content-link">
            <a href="#">Update Kora Installation</a>
        </li>

        @if (null !== \Auth::user() && \Auth::user()->id == 1)
          <li class="content-link">
              <a href="{{ action('BackupController@index') }}">Backups Management</a>
          </li>

          <li class="content-link">
              <a href="{{ action('ExodusController@index') }}">Kora 2 Exodus</a>
          </li>
        @endif
    </ul>
</div>
