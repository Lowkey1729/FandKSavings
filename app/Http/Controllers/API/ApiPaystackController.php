<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use App\Models\Wallet;
use Validator;
use Carbon\Carbon;
use Auth;

class ApiPaystackController extends BaseController
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }
    /**
     * pay via paystack
     *
     * @return \Illuminate\Http\Response
     */

    public function pay_via_paystack(Request $request)
    {
       $user = User::find(Auth::user()->id);
        
        $validator = Validator::make($request->all(),[

            'price'=>'required',
            'email'=> 'required',

        ]);

        if($validator->fails())
        {
            return $this->sendError('Validation Error',$validator->errors());
        }
            $url = "https://api.paystack.co/transaction/initialize";
             $fields = [
                'email' => $request->email,
                'amount' => $request->price.'00',
                'currency' => 'NGN'
              ];
              $fields_string = http_build_query($fields);
              //open connection
              $ch = curl_init();
              //set the url, number of POST vars, POST data
              curl_setopt($ch,CURLOPT_URL, $url);
              curl_setopt($ch,CURLOPT_POST, true);
              curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
              curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer sk_test_0513668cc2fe8ea5e39bd3f183cdd3b656713bb3",
                "Cache-Control: no-cache",
              ));
              
              //So that curl_exec returns the contents of the cURL; rather than echoing it
              curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 
              //execute post
               $result = curl_exec($ch);
              
               $result = json_decode($result, true);
               $reference = $result['data']['reference'];
               $user->transaction_reference = $reference = $result['data']['reference'];
               $user->save();
               return Redirect::to($result['data']['authorization_url']); 
    }

    /**
     * on callback request
     *
     * @return \Illuminate\Http\Response
     */

    public function callback()
    {
                 $reference = Auth::user()->transaction_reference;
                     $curl = curl_init();
  
                  curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.paystack.co/transaction/verify/".$reference,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => array(
                      "Authorization: Bearer sk_test_0513668cc2fe8ea5e39bd3f183cdd3b656713bb3",
                      "Cache-Control: no-cache",
                    ),
                  ));
                  
                  $response = curl_exec($curl);
                  $result = json_decode($response,true);
                  $err = curl_error($curl);
                  curl_close($curl);
                  
                  if ($result['status'] == true && $result['data']['status']!=="abandoned") 
                  {
                    
                      $amount = substr($result['data']['amount'],0, -2) ;
                       return $this->success($amount);
                  }
                  else
                  {
                    return $this->sendError('failed',['error'=>'Transaction failed.']);
                  } 
                  
    }

    public function success($amount)
    {
      // check if user already got a wallet id
            $wallet = Wallet::where('user_id',Auth::user()->id)->first();
            if(!empty($wallet))
            {
              $wallet->balance = $amount+ $wallet->balance;
              $wallet->user_id = Auth::user()->id;
              $wallet->last_transcation = Carbon::now();
              $wallet->details_of_last_transaction = "Funded wallet"; 
              $wallet->save();
            }
            else{
              // generate wallet account number
              $account_number = rand(11111111,9999999);
              //check if account number exists
              $accounts = new Wallet();
              foreach($accounts as $account)
              {
                if(!empty($account->wallet_account_number)&&$account->wallet_account_number == $account_number)
                {
                  $account_number = rand(11111111,9999999);
                }
              }

              $wallet = new Wallet(); 
              $wallet->balance = $amount;
              $wallet->user_id = Auth::user()->id;
              $wallet->last_transcation = Carbon::now();
              $wallet->wallet_account_number = $account_number;
              $wallet->details_of_last_transaction = "Funded wallet"; 
              $wallet->save();
            }
            
            $success = Wallet::where('user_id',Auth::user()->id)->first();
            return $this->sendResponse($success,'Wallet funded successfully.');
    }





   
}
