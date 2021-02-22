<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\API\ApiPaystackController;
use App\Http\Controllers\API\WalletApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/user', function(Request $request)
{
	return auth()->guard('api')->user();
});

Route::middleware('auth:api')->group( function () {


    Route::post('fund_wallet',[ApiPaystackController::class,'pay_via_paystack']);

	Route::get('callback',[ApiPaystackController::class,'callback']);

	Route::post('credit_user_wallet',[WalletApiController::class, 'credit_Y_wallet']);

});

Route::group(['middleware'=>['cors','json.response']], function () {


    Route::post('register',[RegisterController::class,'register'])->name('register.api');

	Route::post('login',[RegisterController::class, 'login']);
	
});
