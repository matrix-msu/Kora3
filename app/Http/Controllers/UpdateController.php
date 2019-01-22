<?php namespace App\Http\Controllers;

use App\Version;
use App\Script;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class UpdateController extends Controller { //TODO::CASTLE

    /*
    |--------------------------------------------------------------------------
    | Update Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles version management of Kora3
    |
    */

    /**
     * @var string - The URL for checking for new versions of Kora3
     */
    const UPDATE_PAGE = 'http://matrix-msu.github.io/Kora3/';

    /**
     * Constructs controller and makes sure user is authenticated and is a system admin.
     */
    public function __construct() {
        $this->middleware('auth');
        $this->middleware('active');
        $this->middleware('admin');
    }

    /**
     * Get the view for the update page.
     *
     * @return View
     */
    public function index() {
        //Determine if the user installed Kora 3 using Git (.git directory exists)
        $git = is_dir( base_path('.git'));

        //Determine if an update is needed (this is determined independent of how Kora was acquired).
        $update = self::checkVersion();
        $ready = self::hasPulled();
        $info = self::processUpdate();

        return view('update.index', compact('git', 'update', 'ready', 'info'));
    }

    /**
     * Checks Github to see if there is a new version.
     *
     * @return bool - Is out of date
     */
    public function checkVersion() {
        //Version of this Kora 3
        $thisVersion = DB::table('versions')->orderBy('created_at', 'desc')->first()->version;

        //Current version of Kora 3
        $currentVersion = self::processUpdate()['version'];

        return version_compare($currentVersion, $thisVersion, ">");
    }

    /**
     * Reads the github page and grabs array of info.
     *
     * @return array - The version info
     */
    public static function processUpdate() {
        $html = file_get_contents(self::UPDATE_PAGE);

        $html = explode('<body>',$html)[1];
        $html = explode('</body>',$html)[0];

        return json_decode($html, true);
    }

    /**
     * Runs an update script to update Kora3.
     *
     * @return Redirect
     */
    public function runScripts() {
        // Allow the script to run for 20 minutes.
        ignore_user_abort(true);
        set_time_limit(1200);

        // Run scripts that have not yet been run.
        foreach(Script::all() as $script) {
            if(!$script->hasRun) {
                $includeString = base_path('scripts/' . $script->filename);
                include $includeString;
                $script->hasRun = true;
                $script->save();
            }
        }
        self::refresh();
        self::storeVersion();

        // Inform the user they have successfully updated.
        ignore_user_abort(false);
        return redirect('update')->with('k3_global_success', 'k3_updated');
    }

    /**
     * Clears the view cache after an update to make sure new features show up in the browser.
     */
    private function refresh() {
        // Clear cached views.
        $viewsPath = storage_path('framework/views');
        $views = array_diff(scandir($viewsPath), array('..', '.', '.gitignore'));

        foreach($views as $view)
            unlink($viewsPath . DIRECTORY_SEPARATOR . $view);

        // Clear Laravel's caches.
        Artisan::call('clear-compiled');
    }

    /**
     * Stores the newly updated version into the local DB.
     */
    private function storeVersion() {
        $v = Version::all()->first();
        $v->version = self::processUpdate()['version'];
        $v->save();
    }

    /**
     * Makes sure that any update scripts in the system have run.
     *
     * @return bool - Are executed
     */
    private function hasPulled() {
        //Add missing files first
        $scriptNames = array_diff(scandir(base_path("scripts")), array('..', '.', 'base_script.php'));
        foreach($scriptNames as $scriptName) {
            if(is_null(Script::where('filename', '=', $scriptName)->first())) {
                $script = new Script();
                $script->hasRun = false;
                $script->filename = $scriptName;
                $script->save();
            }
        }

        //Then check if any havent run
        foreach(Script::all() as $script) {
            if(!$script->hasRun)   // We have found a script that has not run, hence the user has executed a git pull successfully.
                return true;
        }
        // No scripts were found that were not already run, hence the user has not executed a git pull successfully.
        return false;
    }
}
