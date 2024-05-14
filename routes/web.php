<?php

use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Queue;

use App\Models\CompanyLocation;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require __DIR__ . '/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/', 'DashboardController@dashboard')->name('dashboard');

    Route::get('/profile', 'DashboardController@profile')->name('profile');
    Route::post('/profile-save', 'DashboardController@general')->name('profile.save');
    Route::post('/password-save', 'DashboardController@changePassword')->name('password.save');
    Route::post('/email-change', 'DashboardController@changeEmail')->name('email.save');

    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/list', 'UserController@list')->name('list');
        Route::get('/add', 'UserController@add')->name('add');
        Route::get('/edit/{id?}', 'UserController@edit')->name('edit');
        Route::post('/save/{id?}', 'UserController@save')->name('save');
        Route::get('/delete/{id?}', 'UserController@delete')->name('delete');
        Route::get('/status/{id?}', 'UserController@status')->name('status');
    });
    Route::prefix('locationsetting')->name('locationsetting.')->group(function () {
        Route::get('/list', 'LocationSettingController@list')->name('list');
        Route::get('/add', 'LocationSettingController@add')->name('add');
        Route::get('/edit/{id?}', 'LocationSettingController@edit')->name('edit');
        Route::post('/save/{id?}', 'LocationSettingController@save')->name('save');
        Route::get('/delete/{id?}', 'LocationSettingController@delete')->name('delete');
        Route::get('/status/{id?}', 'LocationSettingController@status')->name('status');
    });
    Route::prefix('companylocation')->name('companylocation.')->group(function () {
        Route::get('/list', 'CompanyLocationController@list')->name('list');
        Route::get('/add', 'CompanyLocationController@add')->name('add');
        Route::get('/edit/{id?}', 'CompanyLocationController@edit')->name('edit');
        Route::post('/save/{id?}', 'CompanyLocationController@save')->name('save');
        Route::get('/delete/{id?}', 'CompanyLocationController@delete')->name('delete');
        Route::get('/status/{id?}', 'CompanyLocationController@status')->name('status');
    });
    Route::get('/statstics', 'LocationSettingController@statstics')->name('statstics');
    Route::prefix('location')->name('location.')->group(function () {
        Route::get('list', 'SettingController@cvUpdatorV2')->name('list');
    });
        Route::prefix('settings')->name('setting.')->group(function () {
        Route::get('/index', [SettingController::class, 'index'])->name('index');
        Route::get('/support', [SettingController::class, 'support'])->name('support');
        Route::post('/save', [SettingController::class, 'save'])->name('save');
    });
});
    Route::prefix('authorization')->name('authorization.')->group(function () {
        Route::get('/crm/oauth/callback', [SettingController::class, 'goHighLevelCallback'])->name('gohighlevel.callback');
    });


    Route::get('check/auth', 'DashboardController@authCheck')->name('auth.check');
    Route::get('checking/auth', 'DashboardController@authChecking')->name('auth.checking');
    Route::get('/reset_password/{email}/{token}', [NewPasswordController::class, 'create'])->name('reset.password.form');
    Route::post('/reset-password/form', [NewPasswordController::class, 'store']);
    Route::get('/cache', function () {
        \Artisan::call('optimize:clear');
    });

    Route::get('/fetch', function () {

        $retryDelay=3;
        @ini_set('max_execution_time', 10000);
        @set_time_limit(3000);
        $companylocations=CompanyLocation::where('company_id',9)->where('today',NULL)->get();
        foreach($companylocations as $cl){
            $apiUrl = "contacts/?limit=100";
            set_time_limit(0);
            ini_set('max_execution_time', 18000000);
            $counter=0;
            $allContacts=[];
        do {
            $counter++;
            $nextReq = false;
            $delay = 1;
            sleep($delay);
            $contacts = ghl_api_call(9,$cl->location_id, $apiUrl); // Make the API call
            Log::info("Job Created ".  json_encode($contacts));
            if ($contacts) {
                if (property_exists($contacts, 'contacts') && count($contacts->contacts) > 0) {
                    $allContacts = array_merge($allContacts, $contacts->contacts);
                    if (property_exists($contacts, 'meta') && property_exists($contacts->meta, 'nextPageUrl') && property_exists($contacts->meta, 'nextPage') && !is_null($contacts->meta->nextPage) && !empty($contacts->meta->nextPageUrl)) {
                        $apiUrl = $contacts->meta->nextPageUrl;
                        $nextReq = true;
                    }

                }

            }
        } while ($nextReq);
        $today=0;
        $yesterday=0;
        $last7days=0;
        $currentDate = new DateTime();
        foreach($allContacts as $contact){
            if(property_exists($contact,'dateAdded')){
                $dateAdded = new DateTime($contact->dateAdded);
                $interval = $currentDate->diff($dateAdded);
                $daysDiff = $interval->days;
                if ($daysDiff === 0) {
                    $today++;
                }elseif ($daysDiff === 1) {
                    $yesterday++;
                }elseif ($daysDiff <= 7) {
                    $last7days++;
                }else{
                    break;
                }
            }

        }
        $cl->today=$today;
        $cl->yesterday=$yesterday;
        $cl->last_7days=$last7days;
        $cl->save();

        }


    });
    Route::get('/loginwith/{email?}', function ($id) {
    $user = \App\Models\User::findOrFail($id);
    // clear_setting();
    if ($user) {
        if (in_array(Auth::user()->role, [0, 1])) {
            $key = 'super_admin';
            if ($user->role == 1) {
                session()->put('super_admin', Auth::user());
            }
            Auth::loginUsingId($user->id);
        }
    }

    return redirect()->intended('/');
})->name('user.loginwith');

Route::get('/backtoadmin', function () {
    // clear_setting();
    if (session('super_admin') && !empty(session('super_admin')) && request()->has('admin')) {
        Auth::login(session('super_admin'));
        session()->forget('super_admin', '');
        session()->forget('company_admin', '');
    }

    if (session('company_admin') && !empty(session('company_admin')) && request()->has('company')) {
        Auth::login(session('company_admin'));
        session()->forget('company_admin', '');
    }

    return redirect()->intended('/');
})->name('backtoadmin');

