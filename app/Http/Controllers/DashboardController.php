<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\User;
use App\Models\LocationSetting;
use App\Models\CompanyLocation;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard()
    {
        // return redirect()->route('setting.index',get_defined_vars());
        $user  = Auth::user();//User::where('id', login_id())->first();
        if(is_role() == 'company'){

            return redirect()->route('companylocation.list');
        }

        return view('dashboard', get_defined_vars());
    }

    public function profile()
    {
        $user = Auth::user();
        return view('profile.userprofile', get_defined_vars());
    }
    public function general(Request $req)
    {
        $user = Auth::user();
        $req->validate([
            'fname' => 'required',
            'lname' => 'required',
        ]);

        $user->first_name = $req->fname;
        $user->last_name = $req->lname;

        if ($req->image) {
            $user->image = uploadFile($req->image, 'uploads/profile', $req->first_name . '-' . $req->last_name . '-' . time());
        }

        $user->save();
        return redirect()->back()->with('success', 'Profile updated successfully');
    }

    public function changePassword(Request $req)
    {
        $user = Auth::user();

        $check = Validator::make($req->all(), [
            'current_password' => 'required|password',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password'
        ]);

        if ($check->fails()) {
            return redirect()->back()->with('error', $check->errors()->first());
        }

        $user->password = bcrypt($req->password);
        $user->save();

        return redirect()->back()->with('success', 'Password updated Successfully!');
    }

    public function changeEmail(Request $request)
    {
        $check =  Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'password' => 'required|password'
        ]);
        if ($check->fails()) {
            return redirect()->back()->with('error', $check->errors()->first());
        }

        $user = Auth::user();
        $user->email = $request->email;
        $user->save();

        return redirect()->back()->with('success', 'Email updated Successfully!');
    }
    public function authCheck()
    {

        return view('settings.auth-check');
    }

    public function authChecking(Request $req)
    {
        if ($req->ajax()) {
                if (($req->has('company_id') || $req->has('location_id')) && $req->has('token')) {
                    $id = $req->has('location_id') ? $req->location_id : $req->company_id;
                    $user = User::where('company_id', $id)->orWhere('location_id', $id)->first();
                    if (!$user) {
                    $user = new User();
                    $user->first_name = 'Test';
                    $user->last_name = 'User';
                    $user->email =$id . '@gmail.com';
                    $user->password = bcrypt('shada2e3ewdacaeedd233edaf');
                    $req->has('location_id') ?  $user->location_id : $user->company_id =  $id;
                    $user->ghl_api_key = $req->token;
                    $user->role = $req->has('location_id') ? 1 : 0;
                    $user->save();
                }
                request()->merge(['user_id' => $user->id]);
                 session([
                'location_id' => $req->has('location_id') ? $user->location_id : null,
                'uid' => $user->id,
                'user_id' => $user->id,
                'user_loc' => $req->has('location_id') ? $user->location_id : null,
                ]);
                Auth::login($user);
                $res = new \stdClass;
                $res->user_id = $user->id;
                $res->location_id = $req->has('location_id') ? $user->location_id : null;
                $res->company_id = $req->has('company_id') ? $user->company_id : null;
                $res->is_crm = false;
                $requestUserId =$user->id;
                $requestToken = $req->token;
                $res->token = $user->ghl_api_key;
                $token = get_setting($requestUserId, 'crm_refresh_token');
                $res->crm_connected = false;

                if ($token) {
                    request()->code = $token;
                    $res->crm_connected = ghl_token(request(), '1', 'eee');
                    if (!$res->crm_connected) {
                        $res = ConnectOauth($res,$req->has('company_id') ? true: false);
                    }
                } else {
                    $res->crm_connected = ConnectOauth($res,$req->has('company_id') ? true: false);
                }

                $res->is_crm = $res->crm_connected;


                return response()->json($res);
            }

            return;
        }
        return;
    }
}
