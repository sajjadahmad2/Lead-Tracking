<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LocationSetting;
use Illuminate\Http\Request;

class LocationSettingController extends Controller
{
    protected $route = 'locationsetting';
    protected $title = 'Location Setting';
    protected $view = 'main';
    protected $model = LocationSetting::class;
    protected $findfield = 'id';
    protected $table = 'location_settings';
    protected $skip = ['id', 'created_at', 'updated_at', 'location_id','company_id'];

    protected $actions = [

        'edit' => true,
        'delete' => true,
        'status' => true,

    ];
    protected $tobechanged = [
        'status' => [
            1 => 'Active',
            0 => 'Inactive',
        ],

    ];

    protected $validation = [
        'location_name' => 'required',
        'state' => 'required',
        'leads' => 'required',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function list(Request $request)
    {

        $table_fields = getTableColumns($this->table, $this->skip);
        $table_fields = array_merge($table_fields, ['action' => 'Action']);
        $table_data = $this->model::get();
        foreach ($table_data as $key => $value) {
            foreach ($this->tobechanged as $key1 => $value1) {
                $table_data[$key][$key1] = $value1[$value[$key1]];
            }
        }
        if ($request->ajax()) {
            $table_data = $this->model::get();
            return \DataTables::of($table_data)
                ->addIndexColumn()
                ->editColumn('action', function ($row) {
                    $dropdown = false;
                    $id = $row->id;
                    $actions = getActions($this->actions, $this->route);
                    $actionhtml = view('htmls.action', get_defined_vars())->render();
                    return $actionhtml;
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
        $moreskip = array_merge($this->skip, ['status']);
        $this->skip = $moreskip;
        $form_fields = getFormFields($this->table, $this->skip);
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
        $moreskip = array_merge($this->skip, ['status']);
        $this->skip = $moreskip;
        $form_fields = getFormFields($this->table, $this->skip, $user);

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
        $req->validate($this->validation);
        $reqq = $req->except('_token');
        if(\session()->has('location_ids')){
           $locationIds= \session('location_ids') ;
        }else{
            list($locationIds,$locationnames)=getLocationIds($id);
        }

        foreach($locationIds as $key => $value){
            if($value === $req->location_name ){
                $reqq['location_id'] =  $key;
                break;
            }
        }

        $reqq['company_id'] = login_id() ?? 1;
        $reqq['state'] = \Str::lower($req->state);
        $req->merge($reqq);
        $req = $reqq;

        $user = $this->model::updateOrCreate(['id' => $id], $req);
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

    public function statstics(){
        return view('statstics');
    }
}
