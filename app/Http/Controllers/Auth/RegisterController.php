<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\UserRequest;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller {
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data) {
        return Validator::make($data, [
            'username' => 'required|max:255|unique:users', //Check to not contain 'a'
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:6',
            'language'=> 'required|alpha|max:2',
            'first_name'=> 'required',
            'last_name'=> 'required',
            'organization'=> 'required',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)  {
        return User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'regtoken' => $data['regtoken']
        ]);
    }

    /**
     * Validates a new user model.
     *
     * @return JsonResponse
     */
    public function validateUserFields(UserRequest $request) {
        return response()->json(["status"=>true, "message"=>"User Valid", 200]);
    }

    /**
     * Generates a registration token for a new user
     *
     * @return string - The token
     */
    public static function makeRegToken() {
        $valid = 'abcdefghijklmnopqrstuvwxyz';
        $valid .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $valid .= '0123456789';

        $token = '';
        for($i = 0; $i < 31; $i++) {
            $token .= $valid[( rand() % 62 )];
        }

        return $token;
    }
}
