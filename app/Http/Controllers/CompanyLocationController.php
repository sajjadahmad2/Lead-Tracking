<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CompanyLocation;
use Illuminate\Http\Request;

class CompanyLocationController extends Controller
{
    protected $route = 'companylocation';
    protected $title = 'Leads Tracker';
    protected $view = 'main';
    protected $model = CompanyLocation::class;
    protected $findfield = 'id';
    protected $table = 'company_locations';
    protected $skip = ['id', 'created_at', 'updated_at','company_id'];

    protected $actions = [

        'edit' => true,

    ];
    protected $tobechanged = [


    ];

    protected $validation = [
        'location_id' => 'required',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function list(Request $request)
    {

        $userId=login_id();
        $table_fields = getTableColumns($this->table, $this->skip);
        $locationCount = CompanyLocation::where('company_id', $userId)->count();
        $totalLeads = CompanyLocation::where('company_id', $userId)->sum('leads_dem');
        $contactsCount = CompanyLocation::where('company_id', $userId)->sum('leads_dev');
        $table_fields = array_merge($table_fields, ['lead_rem' => 'Lead Remain','action' => 'Action']);
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
                ->addColumn('lead_rem', function ($row) {
                $rel = $row->lead_dem - $row->lead_dev;
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
        $moreskip = array_merge($this->skip, ['today','yesterday','last_7days']);
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
        $moreskip = array_merge($this->skip, ['today','yesterday','last_7days']);
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
        $reqq['company_id'] = login_id();
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
