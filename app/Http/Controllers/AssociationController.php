<?php namespace App\Http\Controllers;

use App\Association;
use App\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AssociationController extends Controller {

    /*
    |--------------------------------------------------------------------------
    | Association Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles management of form associations for use in
    | associator fields
    |
    */

    /**
     * Constructs controller and makes sure user is authenticated.
     */
	public function __construct() {
		$this->middleware('auth');
		$this->middleware('active');
	}

    /**
     * Gets the view for the manage associations page, including any existing permissions.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @return View
     */
	public function index($pid, $fid) {
        if(!FormController::validProjForm($pid, $fid))
            return redirect('projects/'.$pid)->with('k3_global_error', 'form_invalid');

        $form = FormController::getForm($fid);
        $project = $form->project()->first();

        if(!(\Auth::user()->isFormAdmin($form)))
            return redirect('projects/'.$pid)->with('k3_global_error', 'not_form_admin');

        //Associations to this form
        $assocs = self::getAllowedAssociations($fid);
        //Create an array of fids of those associations
        $associatedForms = array();
        foreach($assocs as $a) {
            array_push($associatedForms, FormController::getForm($a->assocForm));
        }
        $associatable_forms = Form::all();
        $available_associations = self::getAvailableAssociations($fid);
        $requestable_associations = self::getRequestableAssociations($fid);

        $notification = array(
          'message' => '',
          'description' => '',
          'warning' => false,
          'static' => true /* the only notification to appear on this page will be static */
        );

		return view('association.index', compact('form', 'assocs', 'associatedForms', 'project', 'available_associations', 'requestable_associations', 'associatable_forms', 'notification'));
	}

    /**
     * Creates a new association permission.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     * @return JsonResponse
     */
	public function create($pid, $fid, Request $request) {
        if(!FormController::validProjForm($pid, $fid))
            return response()->json(['k3_global_error' => 'form_invalid']);

		$assocFormID = $request->assocfid;

		$assoc = new Association();
		$assoc->dataForm = $fid;
		$assoc->assocForm = $assocFormID;
        $assoc->save();

        $form = Form::where('fid', '=', $assocFormID)->get()->first();
        
        return response()->json(
            [
                'k3_global_success' => 'assoc_created'
            ]
        );
	}

    /**
     * Delete an existing association permission you've given.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     * @return JsonResponse
     */
	public function destroy($pid, $fid, Request $request) {
        if(!FormController::validProjForm($pid, $fid))
            return response()->json(['k3_global_error' => 'form_invalid']);

		$assocFormID = $request->assocfid;

		$assoc = Association::where('dataForm','=',$fid)->where('assocForm','=',$assocFormID)->first();

        $assoc->delete();

        $form = Form::where('fid', '=', $assocFormID)->first();

        return response()->json(
            [
                'k3_global_success' => 'assoc_destroyed',
                'assocfid' => $assocFormID,
                'name' => $form->name
            ]
        );
	}

    /**
     * Delete an existing association permission you've received.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     * @return JsonResponse
     */
    public function destroyReverse($pid, $fid, Request $request) {
        if(!FormController::validProjForm($pid, $fid))
            return response()->json(['k3_global_error' => 'form_invalid']);

        $dataFormID = $request->assocfid;

        $assoc = Association::where('assocForm','=',$fid)->where('dataForm','=',$dataFormID)->first();

        $assoc->delete();

        $form = Form::where('fid', '=', $dataFormID)->first();

        return response()->json(
            [
                'k3_global_success' => 'assoc_destroyed',
                'assocfid' => $dataFormID,
                'name' => $form->name
            ]
        );
    }

    /**
     * Gets all forms that a given form has given permission to.
     *
     * @param  int $fid - Form ID of form granting permission
     * @return Collection - The forms that this form has given permission
     */
    static function getAllowedAssociations($fid) {
		return Association::where('dataForm','=',$fid)->get()->all();
	}

    /**
     * Gets all forms that a given form can associate to.
     *
     * @param  int $fid - Form ID
     * @return Collection - The forms this form has access to
     */
    static function getAvailableAssociations($fid) {
		return Association::where('assocForm','=',$fid)->get()->all();
	}

    /**
     * Gets a list of forms that a given form doesn't have access to yet.
     *
     * @param  int $fid - Form ID
     * @return array - Forms that can be requested for access
     */
    public static function getRequestableAssociations($fid) {
        //get all forms
        $forms = Form::all();
        //get forms we already have permission to search
        $available = self::getAvailableAssociations($fid);
        //store things here
        $requestable = array();

        foreach($forms as $form) {
            //if it's not the current form continue
            if($form->fid==$fid)
                continue;
            //if it's in the available associations already, no worries, continue
            $noworries = false;
            foreach($available as $avail) {
                if($avail->dataForm==$form->fid)
                    $noworries = true;
            }
            if($noworries)
                continue;
            //if we get here, add to array
            array_push($requestable,$form);
        }

        return $requestable;
    }

    /**
     * Makes the request for permission to associate a form. Emails all admins of the requested form.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     * @return JsonResponse
     */
    public function requestAccess($pid, $fid, Request $request) {
        if(!FormController::validProjForm($pid, $fid))
            return response()->json(['k3_global_error' => 'form_invalid']);

        $myForm = FormController::getForm($fid);
        $myProj = ProjectController::getProject($myForm->pid);
        $theirForm = FormController::getForm($request->rfid);
        $theirProj = ProjectController::getProject($theirForm->pid);

        //form admins only
        if(!(\Auth::user()->isFormAdmin($myForm)))
            return response()->json(['k3_global_error' => 'not_form_admin']);

        $group = $theirForm->adminGroup()->first();
        $users = $group->users()->get();

        foreach($users as $user) {
            try {
                Mail::send('emails.request.assoc', compact('myForm', 'myProj', 'theirForm', 'theirProj'), function ($message) use ($user) {
                    $message->from(config('mail.from.address'));
                    $message->to($user->email);
                    $message->subject('Kora Form Association Request');
                });
            } catch(\Swift_TransportException $e) {
                //Log for now
                Log::info('Request access email failed');
            }
        }

        ////////REDIRECT BACK TO INDEX WITH SUCCESS MESSAGE

        //Associations to this form
        $assocs = self::getAllowedAssociations($fid);
        //Create an array of fids of those associations
        $associatedForms = array();
        foreach($assocs as $a) {
            array_push($associatedForms,$a->assocForm);
        }

        return response()->json(['k3_global_success' => 'assoc_access_requested']);
    }
}
