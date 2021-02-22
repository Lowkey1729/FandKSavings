<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\FandKMail;
use Validator;


class RegisterController extends BaseController
{
   /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */


     public function register(Request $request)
     {
     	$validator = Validator::make($request->all(),[

     		'name'=>'required',
     		'email'=> ['required','unique:users,email'],
     		'password' => 'required',
     		'confirm_password' => 'required|same:password'


     	]);


     	if($validator->fails())
     	{
     		return $this->sendError('Validation Error',$validator->errors());
     	}

     	$input = $request->all();
     	$input['password'] = bcrypt($input['password']);
     	$user = User::create($input);
     	$success['name'] = $user->name;
     	$success['token'] =  $user->createToken('MyApp')->accessToken;
        // send mail to user
        $details = [
        'title' => 'Mail from FandK Savings',
        'body' =>$user->name.', Welcome to FandK Savings.'
        ];
        \Mail::to($user->email)->send(new \App\Mail\FandKMail($details));
        
     	return $this->sendResponse($success,'User register successfully.');
     } 



    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */


    public function login(Request $request)
    {
    	if(Auth::attempt(['email' => $request->email,'password'=>$request->password ]))
    	{
    		$user = Auth::user();
    		$success['token'] = $user->createToken('MyApp')->accessToken;

    		return $this->sendResponse($success, 'User login successfully.');
    	}
    	else
    	{
    		return $this->sendError('Unauthorised',['error'=>'Unauthorised']);
    	}
    }
}
