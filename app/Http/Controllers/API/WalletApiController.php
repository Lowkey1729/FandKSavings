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


class WalletApiController extends BaseController
{
    
    public function __construct()
    {
        $this->middleware('auth:api');
    }




	 /**
     * Credit  Y's wallet from X's wallet
     *
     * @return \Illuminate\Http\Response
     */




    public function credit_Y_wallet(Request $request)
    {

    	/**
	     * Get the request from POST
	     *  
	     */


    	$to_account_number = $request->account_number;
    	$amount = $request->amount;


    	$to_wallet = Wallet::where('wallet_account_number',$to_account_number)->first();
    	$from_wallet = Wallet::where('user_id',Auth::user()->id)->first();
    	$from_balance = $from_wallet->balance;
    	$from_account_number = $from_wallet->wallet_account_number;

    	if($from_wallet->balance < $amount)
    	{
    		return $this->sendError('failed',['error'=>'Insufficient fund to proceed transaction']);
    	}

    	if($from_wallet->wallet_account_number == $to_account_number)
    	{
    		return $this->sendError('failed',['error'=>'Forbidden to transact with your account']);
    	}
    	
    	if(empty($to_wallet))
    	{
    		return $this->sendError('failed',['error'=>'Wallet not found']);
    	}

	    /**
	     * Deduct from the the X's wallet crediting Y's wallet
	     *  
	     */

    	$from_wallet->balance = $from_wallet->balance-$amount;
    	$from_wallet->details_of_last_transaction = "Funded ".$to_wallet->user->name."'s wallet";
    	$from_wallet->save();

		/**
	     * Credit Y's wallet from X's wallet
	     *  
	     */
    	
    	$to_wallet->balance = $to_wallet->balance + $amount;
    	$to_wallet->details_of_last_transaction = $from_wallet->user->name." Funded your wallet.";
    	$to_wallet->save();


    	return  $this->sendResponse('success', 'Wallet funded successfully.');




    }

    
}



