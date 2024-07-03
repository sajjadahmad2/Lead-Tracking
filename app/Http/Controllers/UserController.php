<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CrmUser;
use App\Models\CompanyLocation;
use App\Models\UserLocation;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $route = 'user';
    protected $title = 'User';
    protected $view = 'main';
    protected $model = User::class;
    protected $findfield = 'id';
    protected $table = 'users';
    protected $skip = ['id','logo_url','remember_token', 'created_at','timezone','updated_at', 'email_verified_at', 'role', 'added_by', 'image', 'location_id','company_id','ghl_api_key','master_account','master_survey','agency_email','agency_name','white_label',];

    protected $actions = [

        'edit' => true,
        'delete' => true,
        'status' => true,
        'loginwith' => true,

    ];
    protected $tobechanged = [
        'role' => [
            1 => 'Admin',
            2 => 'User',
        ],
        'status' => [
            1 => 'Active',
            0 => 'Inactive',
        ],

    ];

    protected $validation = [
        'first_name' => 'required',
        'last_name' => 'required',
        'email' => 'required|email|unique:',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function list(Request $request)
    {

        $this->skip = array_merge($this->skip, ['password']);
        $table_fields = getTableColumns($this->table, $this->skip);

        $table_fields = array_merge($table_fields, ['action' => 'Action']);
        $table_data = $this->model::where('role', '!=', 0)->get();
        foreach ($table_data as $key => $value) {
            foreach ($this->tobechanged as $key1 => $value1) {
                $table_data[$key][$key1] = $value1[$value[$key1]];
            }
        }

        if ($request->ajax()) {
            $table_data = $this->model::where('role', '!=', 0)->get();

            return \DataTables::of($table_data)
                ->addIndexColumn()
                ->editColumn('action', function ($row) {
                    $dropdown = false;
                    $id = $row->id;
                    $actions = getActions($this->actions, $this->route);
                    $actionhtml = view('htmls.action', get_defined_vars())->render();
                    return $actionhtml;
                })
                ->editColumn('role', function ($row) {
                    $rel = $this->tobechanged['role'][$row->role];
                    return $rel;
                })
                ->editColumn('crm_user_id', function ($row) {
                    $rel = CrmUser::where('id',$row->crm_user_id)->first();
                    if($rel){
                        return $rel->name;
                    }else{
                        return '';
                    }

                })
                ->editColumn('status', function ($row) {
                $rel = $this->tobechanged['status'][$row->status];
                return $rel;
                })
                ->rawColumns(['action'])
                ->toJson();
        }
        $page_route = $this->route;
        $page_title = $this->title;

        return view($this->view . '.list', get_defined_vars());
    }

    public function add()
    {
        $moreskip = array_merge($this->skip, ['status','crm_user_id']);
        $this->skip = $moreskip;
        $form_fields = getFormFields($this->table, $this->skip);
//\session()->has('location_ids') && is_array(\session('location_ids')) && count(\session('location_ids')) > 0? \session('location_ids'): getLocationIds('', false)[0],
        $form_fields['User_id'] =[
            'type' => 'select',
            'name' => 'crm_user_id',
            'label' => 'CRM Users',
           'options' => CrmUser::pluck('name', 'id')->toArray(),
            'value' => \Auth::user()->crm_user_id,
            'required' => true,
            'is_select2'=>true,
            'is_multiple'=>false,
            'read_only'=>false,
            'col' => 6,
            'extra' => ''
        ];
        $card_info = [
            'col' => 6,
            'extraclass' => '',
        ];
        $page_route = $this->route;
        $page_title = $this->title;

        return view($this->view . '.operation', get_defined_vars());
    }

    public function edit($id)
    {
        $user = $this->model::find($id);
        $moreskip = array_merge($this->skip, ['password', 'status','crm_user_id']);
        $this->skip = $moreskip;
        $form_fields = getFormFields($this->table, $this->skip, $user);
        $form_fields['User_id'] =[
            'type' => 'select',
            'name' => 'crm_user_id',
            'label' => 'CRM Users',
           'options' => CrmUser::pluck('name', 'id')->toArray(),
            'value' => $user->crm_user_id,
            'required' => true,
            'is_select2'=>true,
            'is_multiple'=>false,
            'read_only'=>false,
            'col' => 6,
            'extra' => ''
        ];
        $card_info = [
            'col' => 6,
            'extraclass' => '',
        ];
        $page_route = $this->route;
        $page_title = $this->title;


        return view($this->view . '.operation', get_defined_vars());
    }

    public function save(Request $req, $id = null)
    {

        // $req->validate($this->validation);
        if (is_null($id) || empty($id)) {
            $req->validate([
                'password' => 'required',
            ]);

            $req->merge([
                'password' => bcrypt($req->password),
            ]);
        }
        $admin=User::where('role',1)->first();

        $req->merge([
            'role' => is_role() == 'company' ? 2 : 1,
            'added_by' => auth()->id(),
            'company_id'=>$admin->company_id,
        ]);

        $user = $this->model::updateOrCreate(['id' => $id], $req->all());
        return redirect()->route($this->route . '.list')->with('success', $this->title . ' saved successfully');
    }

    public function delete($id)
    {
        $user = $this->model::find($id);
        $user->delete();
        return redirect()->route($this->route . '.list')->with('success', $this->title . '  deleted successfully');
    }

    public function status($id)
    {
        $user = $this->model::find($id);
        $user->status = !$user->status;
        $user->save();
        return redirect()->route($this->route . '.list')->with('success', $this->title . 'status changed successfully');
    }
    public function syncCRMData($id= NULL){
        if(!is_null($id)){
          $user=User::where('id',$id)->first();
          if($user){
              $crmUsers=agency_api_call($user->id,'users/search');
              $crmUsers=json_decode($crmUsers);
              if($crmUsers && property_exists($crmUsers,'users')){
                if(is_array($crmUsers->users) && count($crmUsers->users) > 0){
                 getLocationIds('',true);
                 foreach($crmUsers->users as $u){
                    $checkuser=CrmUser::where('user_id',$u->id)->first();
                    if(!$checkuser){
                        $checkuser = new CrmUser;
                    }
                    $checkuser->user_id=$u->id;
                    $checkuser->name=$u->name;
                    $checkuser->email=$u->email;
                    $checkuser->role=$u->roles->role;
                    $checkuser->company_id=$user->id;
                    $checkuser->save();
                    foreach($u->roles->locationIds as $locid){
                        $location=CompanyLocation::where('company_id', $user->id)->where('location_id',$locid)->first();
                        if($location){
                            $userlocation=UserLocation::where('user_id',$checkuser->id)->where('company_id', $user->id)->where('location_id',$locid)->first();
                            if(!$userlocation){
                                $userlocation= new UserLocation;
                            }
                            $userlocation->user_id=$checkuser->id;
                            $userlocation->company_id=$user->id;
                            $userlocation->location_id=$location->id;
                            $userlocation->save();
                        }
                    }



                 }
                 return response()->json(['status'=>'success','Data'=>"Updated SuccessFully"]);

                }
                return response()->json(['status'=>'error','Data'=>"User not found"]);
              }
              return response()->json(['status'=>'error','Data'=>"User not found"]);

          }else{
              return response()->json(['status'=>'error','Data'=>"User not found"]);
          }
        }
      }
}
