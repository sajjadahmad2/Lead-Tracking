<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CompanyLocation;
use DateTime;
use DateTimeZone;
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
        $isrole=is_role();
        $userId=login_id();
        $user=User::where('id',$userId)->first();
        $crmuserid=$user->crm_user_id;
        $locationId=$user->location_id;
        $company=company();
        $company_id=$company->id;
        if($isrole == 'admin'){
            // $locationCount = CompanyLocation::where('company_id', $company_id)->where('location_id',$locationId)->count();
            $locationCount = CompanyLocation::where('company_id', $company_id)->count();
            $totalLeads = CompanyLocation::where('company_id', $company_id)->sum('leads_dem');
            $contactsCount = CompanyLocation::where('company_id', $company_id)->sum('leads_dev');
            $table_data = $this->model::where('company_id', $company_id)->get();
        }elseif($isrole == 'company'){
            $totalLeads = $this->model::whereHas('crmLocation', function ($query) use ($crmuserid) {
                $query->where('user_id', $crmuserid);
            })->sum('leads_dem');
            $contactsCount = $this->model::whereHas('crmLocation', function ($query) use ($crmuserid) {
                $query->where('user_id', $crmuserid);
            })
            ->sum('leads_dev');
            $companyLocations =$this->model::whereHas('crmLocation', function ($query) use ($crmuserid) {
                $query->where('user_id', $crmuserid);
            })->get();
            $table_data = $this->model::where('company_id', $company_id)->where('location_id',$locationId)->get();

            //$this->updateData($table_data,$company_id,$company);
        }
        $table_fields = getTableColumns($this->table, $this->skip);
        $table_fields['leads_dem'] = change_field_title($table_fields['leads_dem'] ?? '');
        $table_fields['leads_dev'] = change_field_title($table_fields['leads_dev'] ?? '');
        $table_fields = array_merge($table_fields, ['lead_rem' => 'Lead Remain','action' => 'Action']);

        foreach ($table_data as $key => $value) {
            foreach ($this->tobechanged as $key1 => $value1) {
                $table_data[$key][$key1] = $value1[$value[$key1]];
            }
        }
        if ($request->ajax()) {
            if($isrole=='company'){
                $table_data = $this->model::whereHas('crmLocation', function ($query) use ($crmuserid) {
                    $query->where('user_id', $crmuserid);
                })->get();
                //$this->updateData($table_data,$company_id,$company);
                //$table_data = $this->model::where('company_id', $company_id)->where('location_id',$locationId)->get();

            }else{
                $table_data = $this->model::get();
            }

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
    public function updateData($checklocation,$masterid,$user){
        try{
                        $apiUrl = "contacts/?limit=100";
                        set_time_limit(0);
                        ini_set('max_execution_time', 18000000);
                        $cl=$checklocation[0];

                        $counter=0;
                        $allContacts=[];
                        do {
                                $counter++;
                                $nextReq = false;
                                $delay = 1;
                                sleep($delay);
                                $contacts = ghl_api_call($masterid,$cl->location_id, $apiUrl);
                                if ($contacts) {
                                    if (property_exists($contacts, 'contacts') && count($contacts->contacts) > 0) {

                                        $allContacts = array_merge($allContacts, $contacts->contacts);
                                        if (property_exists($contacts, 'meta') && property_exists($contacts->meta, 'nextPageUrl') && property_exists($contacts->meta, 'nextPage') && !is_null($contacts->meta->nextPage) && !empty($contacts->meta->nextPageUrl)) {
                                            $apiUrl = $contacts->meta->nextPageUrl;
                                            $nextReq = true;
                                        }else{
                                           if (property_exists($contacts, 'meta')){
                                                $cl->leads_dev=$contacts->meta->total;
                                                $cl->save();
                                            }
                                        }

                                    }else{
                                        continue;
                                    }
                                }
                            } while ($nextReq);
                        $today = 0;
                        $yesterday = 0;
                        $last7days = 0;
                        $adminTimeZone = new DateTimeZone($user->timezone);
                        $currentDate = new DateTime('now', $adminTimeZone);
                        foreach ($allContacts as $contact) {
                            if (property_exists($contact, 'dateAdded')) {
                                $dateAdded = new DateTime($contact->dateAdded);
                                $dateAdded->setTimezone($adminTimeZone);
                                $interval = $currentDate->diff($dateAdded);
                                $daysDiff = $interval->days;
                                if ($dateAdded->format('Y-m-d') === $currentDate->format('Y-m-d')) {
                                    $today++;
                                } elseif ($dateAdded->format('Y-m-d') === $currentDate->modify('-1 day')->format('Y-m-d')) {
                                    $yesterday++;
                                    $currentDate->modify('+1 day');
                                } elseif ($daysDiff <= 7) {
                                    $last7days++;
                                }
                            }
                        }

                        $cl->today = $today;
                        $cl->yesterday = $yesterday;
                        $cl->last_7days = $last7days;
                        $cl->save();
                        return true;

        }catch(\Exception $e){
            saveLogs('Lead not updatedd in listing due to error', json_encode($e->getMessage()));

        }
    }
    public function syncData($id= NULL){
      if(!is_null($id)){
        $user=User::where('id',$id)->first();
        if($user){
            $crmuserid=$user->crm_user_id;
            $locationId=$user->location_id;
            $company=company();
            $company_id=$company->id;
            $locations=CompanyLocation::whereHas('crmLocation', function ($query) use ($crmuserid) {
                $query->where('user_id', $crmuserid);
            })->get();
            foreach($locations as $location){
                $this->updateData($location,$company_id,$company);
            }

            return response()->json(['status'=>'success','Data'=>"Updated SuccessFully"]);
        }else{
            return response()->json(['status'=>'error','Data'=>"User not found"]);
        }
      }
    }
    public function statstics(){
        return view('statstics');
    }
}
