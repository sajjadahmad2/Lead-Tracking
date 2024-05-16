<?php

namespace App\Http\Controllers;
use stdClass;
use Illuminate\Http\Request;
use App\Models\CompanyLocation;
use App\Models\User;
use App\Models\Contact;
use App\Models\LocationSetting;
class SettingController extends Controller
{
    public function index()
    {
            $admin_form_fields  = [
                'client_id' => [
                    'type' => 'text',
                    'name' => 'crm_client_id',
                    'label' => 'CRM Client Id',
                    'value' => setting('crm_client_id'),
                    'placeholder' => 'Client Client Id',
                    'required' => false,
                    'col' => 6,
                    'extra' => ''
                ],

                'client_secret' => [
                    'type' => 'text',
                    'name' => 'crm_client_secret',
                    'label' => 'CRM Client Secret',
                    'value' => setting('crm_secret_id'),
                    'placeholder' => 'CRM Client Secret',
                    'required' => false,
                    'col' => 6,
                    'extra' => ''
                ],
                // 'company_logo' => [
                //     'type' => 'file',
                //     'name' => 'company_logo',
                //     'label' => 'Company Logo',
                //     'value' => setting('company_logo'),
                //     'placeholder' => 'Company Logo',
                //     'required' => true,
                //     'col' => 3,
                //     'extra' => ''
                // ],

            ];
            $company_form_fields  = [
                'master_account' => [
                    'type' => 'select',
                    'name' => 'master_account',
                    'label' => 'Master Account',
                    'options' => \session()->has('location_ids') ? \session('location_ids') : getLocationIds()[0],
                    'value' => \Auth::user()->master_account, // Set the default selected value here
                    'required' => true,
                    'is_select2'=>true,
                    'is_multiple'=>false,
                    'col' => 6,
                    'extra' => ''
                ],
                'master_survey' => [
                    'type' => 'text',
                    'name' => 'master_survey',
                    'label' => 'Master Survey',
                    'value' => \Auth::user()->master_survey,// Set the default selected value here
                    'placeholder' => 'Master_survey_id',
                    'required' => true,
                    'col' => 6,
                    'extra' => ''
                ],
            ];
        return view('settings.setting', get_defined_vars());
    }

    public function save(Request $request)
    {
        if(is_role() == 'company'){
            $user=User::where('id',login_id())->first();
            if($user){
                $user->master_account= $request->master_account;
                $user->save();
            }

        }else{
            foreach ($request->except('_token') as $key => $value) {
                try{
                    if($request->hasFile($key)){
                    $value = uploadFile($request->file($key), 'uploads/logos', $key.'_'.time());
                    }
                }catch(\Exception $e){

                }
                save_settings($key, $value);
            }
        }

        return redirect()->back()->with('success', 'Settings saved successfully');
    }

    //goHighLevel oAuth 2.0 callback
    public function goHighLevelCallback(Request $request)
    {

        return ghl_token($request->code ?? '','',null,true);
    }

    public function handleContactUpdate(Request $request)
    {
        try{
            $this->saveLogs('ContactUpdate', $request->all());
            if(strpos($request->type,'ContactCreate')){
                $masterid=9;
                $user=User::where('id',$masterid)->first();
                if(!$user || empty($user) || is_null($user)){
                    return "User with this Master Account not Found";
                }
                if ($user->status==0 || !$user->status) {
                    return 'Account In active';
                }
                $companylocations=CompanyLocation::where('location_id',$request->locationId)->first();
                if($companylocations){
                    $companylocations->leads_dev=$companylocations->leads_dev + 1;
                    $companylocations->today=$companylocations->today +1;
                    $companylocation->save();
                }
                return 'lead received';
            }
            if(strpos($request->type,'locationCreate')){
              $checklocation=CompanyLocation::where('location_id',$request->id)->first();
              if(!$checklocation){
                $checklocation= new CompantLocation;
                $checklocation->location_id =$request->id;
                $checklocation->location_name =$request->name;
                $checklocation->location_email =$request->email;
                $checklocation->status ='active';
                $checklocation->type ='new client';
                $checklocation->medicare ='medicare';
                $checklocation->leads_dem =0;
                $checklocation->leads_del =0;
                $checklocation->save();
                $apiUrl = "contacts/?limit=100";
            set_time_limit(0);
            ini_set('max_execution_time', 18000000);
            $cl=$checklocation;
            $counter=0;
            $allContacts=[];
        do {
            $counter++;
            $nextReq = false;
            $delay = 1;
            sleep($delay);
            $contacts = ghl_api_call($masterid,$cl->location_id, $apiUrl); // Make the API call
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
            }

        }
        catch (\Exception $e){
            $this->saveLogs('Lead Not dispursed due to error', $e->getMessage());

        }


    }

    public function cvUpdatorV2(Request $request,$companyid= null)
    {
        try {
            $userId=null;


            if(is_null($companyid)){
                 return "Please Review the Company ID in the URl";
            }




            if(checkValidation()){
                return "Something went wrong please check the  CRM Client Id, Client Secret, CV prefixes";
            }




            $userLoc = $request->email ?? "";
            if (empty($userLoc)) {
                return 'Email required';
            }


            // $locations= \agency_api_call($userId,'locations/search?limit=100&deleted=false');

            // dd($locations);
            $prefix = get_default_settings('cv_prefix');
            $other_fields = $request->customData??[];
            $prefix2 = $other_fields['cv_prefix']??'';
            unset($other_fields['cv_prefix']);
            $empty_fields= $other_fields['cv_empty']??'#';
            unset($other_fields['cv_empty']);
            if(!empty($prefix2)){
                $prefix=$prefix2;
            }
            $matches = $request->all();
            $updatedFormField = [];
            foreach ($matches as $key => $value) {
                if (strpos($key, $prefix) !== false) {
                    $key = str_replace($prefix, '', $key);
                    try {
                        if ($key[0] == ' ') {
                            $key = substr($key, 1);
                        }
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                    $updatedFormField[transform_name($key)] =  ['title' => $key, 'value' => $value ?? ""];
                }
            }



            $dipatchNow=$other_fields['dispatchSync512412']??false;
            $subaccountid='Subaccount ID';
            $loc_found=$matches[$subaccountid]??$request->input($subaccountid,null) ?? null;
             $url='locations/'.$loc_found.'/customValues';

            foreach($other_fields as $fk=>$fv){
                if($fk=='location_id' || $fk=='subaccount_id'){
                    $loc_found = !empty($fv) ? $fv : null;
                    continue;
                }
                $updatedFormField[transform_name($fk)] =  ['title' => $fk, 'value' => $fv ?? ""];
            }
            if(count($updatedFormField)==0){
                 return 'No custom fields found - either add cv- to title of custom field or send custom value as a custom data';
            }

            $user=User::where('company_id',$companyid)->first();
            if(!$user){
                return "Please Review the Company ID in the URl";
            }
             if ($user->status==0 || !$user->status) {
                return 'Account In active';
            }
            $userId=$user->id;

            //dd($updatedFormField);
            // $locations= \agency_api_call($userId,'locations/search?limit=100&deleted=false');
            //         $locations=json_decode($locations);
            //         dd($locations);
            //$this->saveLogs('Data we get ', $matches);
           // $this->saveLogs('Data we made ', $updatedFormField);
             //return 'request received';
              $this->saveLogs('CVUpdator', $request->all());
             $job=new \App\Jobs\CVUpdatorJob([
                'userId'=>$userId,
                 'userLoc'=>$userLoc,
                 'emptyCV'=>$empty_fields,
                  'dispatchSync'=>$dipatchNow,
                 'companyid'=>$companyid,
                  'loc_found'=>$loc_found,
                  'updatedFormField'=>$updatedFormField
                ]);
             dispatch($job);

                return 'request received';
            // if(!$loc_found){
            //     $findLoc=CompanyLocation::where(['company_id'=>$userId,'email'=> $userLoc])->first();
            //     if(!empty($findloc)){
            //         $loc_found=$findLoc->location_id;
            //     }else{
            //         $locations= agency_api_call($userId,'locations/search?limit=100&deleted=false');
            //         $locations=json_decode($locations);
            //         if($locations && property_exists($locations,'locations')){
            //             $locations=$locations->locations;
            //             foreach($locations  as $loc){
            //                 $bemail='';
            //                 $email =$loc->email??"";
            //                 if(property_exists($loc,'business')){
            //                     $bemail =$loc->business->email??"";
            //                 }
            //                 if($email == $userLoc || $bemail == $userLoc){

            //                     $loc_found=$loc->id;
            //                     break;
            //                 }


            //             }
            //         }
            //     }
            // }



        } catch (\Exception $th) {
            echo $th->getMessage();
        }



    }
        public function saveLogs($msg,$data){
        \DB::table('logs')->insert([
            'details' =>$msg,
            'response' => json_encode($data),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }

    public function locationWebhookHandle(Request $request){
        try {
            @ini_set('max_execution_time', 120);
            @set_time_limit(120);

            $type = $request->type ??'';
            if ($type == 'LocationCreate' || $type == 'LocationUpdate1') {
            $compid = User::where('company_id', $request->companyId)->value('id');
            if (!empty($compid)) {
                 saveLogs("Agency Found",$request->all());
            }else{
                return response()->json(['status' => 'failed', 'message' => "Data not saved due to Agency error"]);
            }
             $findloc = CompanyLocation::firstOrNew(['location_id' => $request->id, 'company_id' => $compid])->fill([
                'location_id' => $request->id,
                'company_id' => $compid,
                'loc_name' => $request->name??'',
                'email' => $request->email??'',
            ])->save();

                return response()->json(['status'=>'success','message'=>"Location Data Saved"]);
            }

        } catch (\Throwable $th) {
            saveLogs("Webhook Not Processed",$th->getMessage() . ' - ' . $th->getLine());
        }
        return 'Received';
    }
}
