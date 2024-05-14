<?php

use App\Http\Controllers\SettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/cache', function () {
    \Artisan::call('optimize:clear');
});
Route::post('/tracking/webhook', [SettingController::class, 'handleWebhook'])->name('trackingwebhook');

// Route::post('/location/webhook', [SettingController::class, 'locationWebhookHandle'])->name('locationwebhook');

// Route::get('/connect', function () {
//   connect_location_apicall('cZqNGFl4DZhbRvswXxCN','EUZqxwkHc9SgbrThgzWb');
// });


Route::get('/refresh/{cmp}/{uid}/{status}', function ($cmp,$uid,$status) {
//       $code  =  ghl_oauth_call( $id, 'refresh');
//   echo json_encode($code);
    $code=handleRefresh($cmp,$uid,$status);
    echo json_encode($code);


});
