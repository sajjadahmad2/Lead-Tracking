<?php

use App\Models\Contact;
use App\Models\ConnectedLocation;
use App\Models\CompanyLocation;
use App\Models\GhlAuth;
use App\Models\Setting;
use App\Models\CustomField;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Else_;


//This function gives the setting of the SuperAdmin
function supersetting($key, $default = '')
{
    $setting = DB::table('settings')->where(['user_id' => 1, 'key' => $key])->first();
    if ($setting) {
        return $setting->value;
    }
    return $default;
}
function getRefreshToken($userId,$auth=null,$company=false){
    return $auth ? $auth->crm_refresh_token : ($company ? get_default_settings_byUser($userId, 'crm_refresh_token') : get_loc_default_settings($comp_id, 'crm_refresh_token'));
}
function refreshToken($comp_id, $userId = null, $company = true,$auth=null) {
    $refresh_token = getRefreshToken($userId,$auth,$company);
    //dd($refresh_token,$userId);
    $is_refresh = ghl_oauth_call($refresh_token, 'refresh_token');
    if ($is_refresh && property_exists($is_refresh, 'access_token')) {
        $is_refresh = $company ? saveAgencySetting($userId, $is_refresh) : saveLocationSetting($is_refresh,$userId);
    }
    \Log::info('Token refresh '.json_encode($is_refresh));
    // if(!$company && $is_refresh && property_exists($is_refresh, 'access_token')){
    //     //$is_refresh=connect_location_apicall($comp_id,$userId);
    // }

    return $is_refresh;
}

function handleRefresh($comp_id, $userId, $company = true,$auth=null) {
    $refresh_token =getRefreshToken($userId,$auth,$company);
    if(empty($refresh_token)){
        return null;
    }
    $time = 30;
    $lock = Cache::lock('refresh_'.$comp_id.$userId, $time);
    return $lock->block($time, function() use ($refresh_token, $comp_id, $userId, $company,$auth) {
        if($company && $auth){
            $auth->refresh();
        }
        //get_default_settings_byUser($userId, 'crm_refresh_token')

        $current_refresh_token =  getRefreshToken($userId,$auth,$company);
        if ( !empty($current_refresh_token) && $refresh_token != $current_refresh_token) {
            if(!$auth){
                $auth = new ConnectedLocation();
                $auth->crm_access_token = '1212';
            }
           //\Log::info('Token already refresh '.json_encode($auth));
            return $auth;
        } else {
            return refreshToken($comp_id, $userId, $company,$auth);
        }
    });
}



//This Function saves the agencyTokens and the CompanyId
function save_my_settings($key, $value)
{
    $obj = Setting::where('key', $key)->first();

    if (!$obj) {
        $obj = new Setting();
        $obj->key = $key;
    }
    $obj->value = $value;
    $obj->user_id = 2;
    $obj->save();
}

//get setting of current user
function setting($key, $default = '')
{
    $setting = DB::table('settings')->where(['user_id' => login_id(), 'key' => $key])->first();
    if ($setting) {
        return $setting->value;
    }
    return $default;
}
//get the setting by user
function get_setting($id, $type)
{
    $res = Setting::where(['user_id' => $id,  'key' => $type])->first();
    if ($res) {
        return $res->value;
    } else {
        return null;
    }
}

//get the default setting of any key
function get_default_settings($key, $default = '')
{

               $setting = Setting::pluck('value', 'key');
            return $setting[$key] ?? $default;
}
function get_default_settings_byUser($userId=null,$key, $default = '')
{
    if(!is_null($userId)){
         $setting = GHLAuth::where('user_id',$userId)->first();
            return $setting->$key ?? $default;
    }
        return $default;

}
function get_loc_default_settings($location_id=null,$key, $default = '')
{
    if(!is_null($location_id)){
         $setting = ConnectedLocation::where('location_id',$location_id)->first();
            return $setting->$key ?? $default;
    }
        return $default;

}



//Create User using the Location id for the auto auth this will return the Whole User
function create_location_user($location_id)
{
    $user = User::where('location_id', $location_id)->first();
                if (!$user) {
                    // aapi call
                    $user = new User();
                    $user->first_name ='Test';
                    $user->last_name ='User';
                    $user->email = $location_id . '@gmail.com';
                    $user->password = bcrypt('shada2e3ewdacaeedd233edaf');
                    $user->location_id= $location_id;
                    $user->role = 2;
                    $user->save();
                }
                return $user;

}

//This Function give the user id of the Current logged in User
function login_id($id = "")
{
    if (!empty($id)) {
        return $id;
    }

    if (auth()->user()) {
        $id = auth()->user()->id;
    } elseif (session('uid')) {
        $id = session('uid');
    } elseif (Cache::has('user_ids321')) {
        $id = Cache::get('user_ids321');
    }

    return $id;
}

function superAdmin()
{
   return 1;
}
//This Function give the role of the Current logged in User
function is_role($user=null)
{
    if(!$user){
        if(auth()->user()){
            $user= auth()->user();
        }

    }
    if($user){
         if ($user->role == 0) {
            return 'admin';
        } elseif ($user->role == 1) {
            return 'company';
        } else {
            return 'user';
        }
    }
       return null;




}
function formSubmission(){
    $form_Id = "Kq80yLaqj0J4JLYBVcD7"; //HlYGceKpcoDe2MWZxjDx
    $custom_field_file = "hWLIgGrjHEspyy8pbjXL"; //cu9TvjMrwWJVXjRrS1vv
    $locationId="l1Rz4SuzYvlVt6ZxaFoT";
    $formData = array(
      'formId' => $form_Id,
      'location_id' => $locationId,
      'email' => 'inc_info@gmail.com',
      'sessionId' => 'c236f89d-a575-4ce9-94b8-17a73c5d8d53',
      'eventData' => array(
        'source' => 'direct',
        'referrer' => '',
        'keyword' => '',
        'adSource' => '',
        'url_params' => array(),
        'page' => array(
          'url' => $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
          'title' => ''
        ),
        'timestamp' => time(),
        'campaign' => '',
        'contactSessionIds' => null,
        'fbp' => '',
        'fbc' => '',
        'type' => 'page-visit',
        'parentId' => $form_Id,
        'pageVisitType' => 'form',
        'domain' => $_SERVER['HTTP_HOST'],
        'version' => 'v3',
        'parentName' => 'Do not Delete - HTS Saad',
        'fingerprint' => null,
        'gaClientId' => 'GA1.2.1779790463.1673615426',
        'fbEventId' => 'b515d2f4-cb1c-4ac6-b0d0-4095e2f18a95',
        'medium' => 'form',
        'mediumId' => $form_Id
      ),
      'sessionFingerprint' => '157acabf-8e5e-4a59-8b9e-cf00e9515455'
    );

    $file_path = $file->getPathname(); // Path to the temporary file
    $file_mime_type = $file->getMimeType(); // Mime type of the file
    $file_original_name = $file->getClientOriginalName();
    $data = array(
      $custom_field_file => new CURLFile($file_path, $file_mime_type, $file_original_name ),
      'formData' => json_encode($formData)
    );

    $request = curl_init();
    curl_setopt($request, CURLOPT_URL, 'https://services.leadconnectorhq.com/forms/submit');
    curl_setopt($request, CURLOPT_POST, true);
    curl_setopt($request, CURLOPT_POSTFIELDS, $data);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($request);
    curl_close($request);

    return json_decode($response, true);
}
function surveysubmission($contactid, $master_location, $userid, $current_location, $surveyId) {
    try {
        $contactid = $contactid; //POMqFToc91ZiPvo0AHpY
        $survey_id = $surveyId; //BgcaK94TrSytvXR0kmyi
        $locationId = $current_location; //vziY4BfTo6yssDoovkSU
        $getcontactsurvey = ghl_api_Call($userid, $master_location, 'surveys/submissions?locationId=' . $master_location . '&surveyId=' . $survey_id . '&q=' . $contactid, 'GET');

        if ($getcontactsurvey && property_exists($getcontactsurvey, 'submissions') && count($getcontactsurvey->submissions > 0)) {
            $getcontactsurvey = json_decode(json_encode($getcontactsurvey), true);
            $submission = $getcontactsurvey['submissions'][0]['others'];
            $skip = ["surveyId","contactId","fieldsOriSequance","ip","submissionId","createdAt","formId","location_id","contact_id","sessionId","eventData","source","referrer","adSource","page","url","title","timestamp","contactSessionIds","ids","fbp","fbc","type","parentId","pageVisitType","domain","version","parentName","fingerprint","documentURL","fbEventId","medium","mediumId",
            ];
            foreach ($skip as $key) {
                unset($submission[$key]);
            }
            $formData = [
                'formId' => $survey_id,
                'location_id' => $locationId,
                'sessionId' => 'a6f35cf1-1336-4f4d-8281-1f6e258cee0c',
                'eventData' => [
                    'source' => 'direct',
                    'referrer' => '',
                    'keyword' => '',
                    'adSource' => '',
                    'url_params' => [],
                    'page' => [
                        'url' => $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                        'title' => ''
                    ],
                    'timestamp' => time(),
                    'campaign' => '',
                    'contactSessionIds' => null,
                    'fbp' => '',
                    'fbc' => '',
                    'type' => 'page-visit',
                    'parentId' => $survey_id,
                    'pageVisitType' => 'survey',
                    'domain' => $_SERVER['HTTP_HOST'],
                    'version' => 'v3',
                    'parentName' => '',
                    'fingerprint' => null,
                    'fbEventId' => '56c3ffb5-ee95-45c3-aa19-7073006a00f3',
                    'medium' => 'survey',
                    'mediumId' => $survey_id
                ],
                'sessionFingerprint' => '157acabf-8e5e-4a59-8b9e-cf00e9515455'
            ];
            $formData = array_merge_recursive($submission, $formData);
            $formData['email'] = 'davistest@gmail.com';
            $data = array(
                'formData' => json_encode($formData)
            );
            $request = curl_init();
            curl_setopt($request, CURLOPT_URL, 'https://services.leadconnectorhq.com/surveys/submit');
            curl_setopt($request, CURLOPT_POST, true);
            curl_setopt($request, CURLOPT_POSTFIELDS, $data);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($request);
            if ($response === false) {
                throw new Exception(curl_error($request), curl_errno($request));
            }
            curl_close($request);
            return json_decode($response, true);
        } else {
            throw new Exception('No submissions found or invalid response structure.');
        }
    } catch (Exception $e) {
        // Handle the exception
        return ['status'=>false,'error' => $e->getMessage()];
    }
}


function getCRMScopes(){
    return'locations.write locations.readonly locations/customValues.readonly locations/customValues.write locations/customFields.readonly locations/customFields.write locations/tasks.readonly locations/tasks.write locations/tags.readonly locations/tags.write locations/templates.readonly medias.readonly medias.write oauth.write oauth.readonly opportunities.readonly opportunities.write snapshots.readonly surveys.readonly users.readonly snapshots.write users.write workflows.readonly forms.write forms.readonly contacts.write contacts.readonly companies.readonly calendars/events.write calendars/events.readonly calendars.write calendars.readonly businesses.write businesses.readonly calendars/groups.readonly calendars/groups.write calendars/resources.readonly calendars/resources.write campaigns.readonly conversations.readonly conversations.write conversations/message.readonly conversations/message.write conversations/reports.readonly courses.write courses.readonly invoices.readonly invoices.write invoices/schedule.readonly invoices/schedule.write invoices/template.readonly invoices/template.write links.readonly lc-email.readonly links.write funnels/redirect.readonly funnels/redirect.write payments/orders.readonly payments/orders.write payments/integration.readonly payments/integration.write payments/transactions.readonly payments/subscriptions.readonly products.readonly products.write products/prices.readonly products/prices.write saas/company.read saas/company.write saas/location.write saas/location.read  ';

}

function ConnectOauth_old($company_id, $is_company = false)
{
    $tokenx = false;
    $uid = $company_id->user_id;
    $token_c = false;
    $token = $company_id->token;
    if (!empty($token)) {
        $loc = $is_company ? $company_id->company_id :  $company_id->location_id;
        $type = $is_company ? 'Company' : 'Location';
        $client_id = get_setting($requestUserId, 'crm_client_id');
        $callbackurl = $callbackurl = route('authorization.gohighlevel.callback');
        $locurl = "https://services.leadconnectorhq.com/oauth/authorize?" . ($type == 'Company' ? 'company_id' : 'location_id') . "=" . $loc . "&response_type=code&userType=" . $type . "&redirect_uri=" . $callbackurl . "&client_id=" . $client_id . "&scope=" . getCRMScopes();

        $client = new \GuzzleHttp\Client(['http_errors' => false]);
        $headers = [
            'Authorization' => 'Bearer ' . $token
        ];
        $request = new \GuzzleHttp\Psr7\Request('POST',   $locurl, $headers);
        $res1 = $client->sendAsync($request)->wait();
        $red =  $res1->getBody()->getContents();
        // dd($red, $locurl);
        $red = json_decode($red);

        if ($red && property_exists($red, 'redirectUrl')) {
            $url = $red->redirectUrl;
            $parts = parse_url($url);
            parse_str($parts['query'], $query);
            $code = $query['code'] ?? "" ;
            $tokenx  =  ghl_token( $code, '', 'eee3');
        }
    }
    return $tokenx;
}

//This Function is used for the autoconnectivity of auth
function ConnectOauth($company ,$token,$method=''){

    $tokenx=false;
    $callbackurl = route('authorization.gohighlevel.callback');
    $locurl = "https://services.msgsndr.com/oauth/authorize?company_id=" . $company . "&response_type=code&userType=Location&redirect_uri=" . $callbackurl . "&client_id=" . superSetting('crm_client_id') . "&scope=calendars.readonly calendars/events.write calendars/groups.readonly calendars/groups.write campaigns.readonly conversations.readonly conversations.write conversations/message.readonly conversations/message.write contacts.readonly contacts.write forms.readonly forms.write links.write links.readonly locations.write locations.readonly locations/customValues.readonly locations/customValues.write locations/customFields.readonly locations/customFields.write locations/tasks.readonly locations/tasks.write locations/tags.readonly locations/tags.write locations/templates.readonly medias.readonly medias.write opportunities.readonly opportunities.write surveys.readonly users.readonly users.write workflows.readonly snapshots.readonly oauth.write oauth.readonly calendars/events.readonly calendars.write businesses.write businesses.readonly";

                    $client = new \GuzzleHttp\Client(['http_errors' => false]);
                    $headers = [
                        'Authorization' => 'Bearer ' . $token
                    ];
                    $request = new \GuzzleHttp\Psr7\Request('POST',   $locurl, $headers);
                    $res1 = $client->sendAsync($request)->wait();
                    $red =  $res1->getBody()->getContents();
                    $red = json_decode($red);
                    if ($red && property_exists($red, 'redirectUrl')) {
                        $url = $red->redirectUrl;
                        $parts = parse_url($url);
                        parse_str($parts['query'], $query);
                        $tokenx  = ghl_token($query['code']??"", '', 'eee3');
                    }
    return $tokenx;

}

//This Function is used  to get the Data Form the ENV
function get_fields($vars)
{
    $vars = $vars['__data'];
    unset($vars['__env']);
    unset($vars['app']);
    unset($vars['errors']);
    return $vars;
}

//This function check the location is connected with the server or not
function is_connected($user_id=null)
{
    if(is_null($user_id)){
         $user_id = login_id();
    }

    $user = GhlAuth::where('user_id', $user_id)->first();

    if ($user) {
        return $user;
    }
    return null;
}


//This function will provide the locations array that are in the database
function get_locations()
{

    $user = User::pluck('location_id', 'id')->toArray();
    if ($user) {
        return $user;
    }
    return [];
}


//This function will provide the userid array that are in the database
function get_ids()
{

    $user = User::pluck('id', 'id')->toArray();
    if ($user) {
        return $user;
    }
    return [];
}




//This function is used in the agency api call only
if (!function_exists('guzzleClient')) {
    function guzzleClient($headers = [])
    {
        return new \GuzzleHttp\Client(['http_errors' => false, 'headers' => $headers]);
    }
}


//This is the agency Api Call to connect agency
function agency_api_call($userId=null,$url = '', $method = 'get', $data = '', $headers = [], $json = false,$retries=1,$auth=null)
{
    $api_call_version =  getDefaultVersion();
    if(!$auth){
        $auth=is_connected($userId);
    }


    $token = $auth->crm_access_token ?? null;// get_default_settings_byUser($userId,'crm_access_token');
    if(empty($token)){
        return '';
    }
    $main_url = 'https://services.leadconnectorhq.com/';
    $loccompany_id = $auth->company_id??''; //get_default_settings_byUser($userId,'company_id');
    $headers['Version'] = $api_call_version;
    if (strtolower($method) == 'get') {

        if (strpos($url, 'locations/lookup') !== false) {
            $url = str_replace('locations/lookup', 'locations/search', $url);
        }
        if (strpos($url, 'companyId=') === false) {
            $url .= (strpos($url, '?') !== false) ? '&' : '?';
            $url .= 'companyId=' . $loccompany_id;
        }
    }

    $headers['Authorization'] = 'Bearer ' . $token;
    if ($json) {
        $headers['Content-Type'] = "application/json";
    }
    $url = str_replace($main_url,'',$url);
    $url1 = $main_url . $url;
    //dd($headers,$url1,$loccompany_id);
    $client = guzzleClient($headers);
    $options = ['verify' => false];
    if (!empty($data)) {
        $options[GuzzleHttp\RequestOptions::JSON] = json_decode($data);
    }
    $cd = '';
    try {
        $response = $client->request($method, $url1, $options);
        $cd = $response->getBody()->getContents();
        try {
            $headers = $response->getHeaders();
            $remaining_limit = $headers['x-ratelimit-daily-remaining'] ?? "";
            if (!empty($remaining_limit) && $remaining_limit == 0) {
                //future work
            }
        } catch (\Exception $e) {
        }

        $bd = json_decode($cd);
        // dd($bd);
        if (isset($bd->error) && strtolower($bd->error) == 'unauthorized' && $retries==1) {
            $is_refresh = handleRefresh($loccompany_id,$userId,true,$auth);
           //echo json_encode($is_refresh);
           //$tries
                if (checkTokenRefresh($is_refresh)) {
                    sleep(1);
                    return agency_api_call($userId,$url, $method, $data, [], $json,($retries+1),$is_refresh);
                }
        }
    } catch (\Exception $e) {
        echo $e->getMessage();
    }
    return $cd;
}
//Function to saveghlagency setting in ghl auth
function saveAgencySetting($userId='',$code,$comp=null){
        if(!$comp){
            $comp= GHLAuth::where('company_id',$code->companyId)->first();
        }


                if (!$comp) {
                    $comp = new GHLAuth();
                }
                $comp->company_id = $code->companyId;
                $comp->crm_access_token = $code->access_token;
                $comp->crm_refresh_token = $code->refresh_token;
                $comp->expire_at = $code->expires_in;
                $comp->crm_user_id = $code->userId;
                $comp->user_id = $userId;
                $comp->user_type = $code->userType;
                $comp->save();
                $user=User::where('id',$userId)->first();
                if($user){
                    $user->company_id=$code->companyId;
                    $user->save();
                }
                return $comp;
}

//Function to saveghllocation setting in Connected Locations
function saveLocationSetting($code,$userId=null,$loc=null){
            if(!$loc){
                   $loc= ConnectedLocation::where('location_id',$code->locationId)->first();
                }

                if (!$loc) {
                    $loc = new ConnectedLocation();
                }
                $loc->location_id = $code->locationId;
                //$loc->company_id = $code->locationId;
                $loc->crm_access_token = $code->access_token;
                $loc->crm_refresh_token = $code->refresh_token;
                $loc->expire_at = $code->expires_in;
                $loc->crm_user_id = $code->userId ?? '';
                $loc->user_id = $userId ?? login_id() ?? '';
                $loc->user_type = $code->userType;
                $loc->save();
                return $loc;
}
//This Function is used to get the ghl token
function ghl_token($req, $type = '',$userid=null,$forceCompany=false)
{
    $code = $req->code ?? $req;
    $code  =  ghl_oauth_call($code, $type);
    if ($code) {
        if (property_exists($code, 'access_token')) {
            $location=$code->locationId ?? '';
            $mesg = '';
            if(!$userid){
                    $userid = login_id();
                }
            $cmp  = $code->companyId ?? '';
            $userType=$code->userType??'Location';
            if($forceCompany && empty($type )&& $userType!='Company'){
                abort(redirect()->route('dashboard')->with('error', 'Please connect to CRM agency'.$mesg));
            }
            if(!empty($location) && ($code->userType??"")=='Location'){
                 saveLocationSetting($code,$userid);
                $mesg = 'Location - '.$location;
            }else{
                $mesg = 'Agency - '.$cmp;
                saveAgencySetting($userid,$code);
                \session()->forget('location_names');
                \session()->forget('location_ids');
                list($locationids,$locationnames)=getLocationIds('',true);
            }
            if (empty($type)) {
                $res=agency_api_call($userid,'companies/'.$cmp,'GET');
                $res=json_decode($res);

                if($res && property_exists($res,'company')){
                    $user=User::where('id',$userid)->first();
                    $user->agency_email=$res->company->email;
                    $user->agency_name=$res->company->name;
                    $user->white_label=$res->company->domain;
                    $user->logo_url=$res->company->logoUrl;
                    $user->save();
                }
                abort(redirect()->route('dashboard')->with('success', 'Successfully connected to CRM '.$mesg));
            }
            return true;
        } else {
            if (property_exists($code, 'error_description')) {
                if (empty($type)) {
                    abort(redirect()->route('dashboard')->with('error', $code->error_description));
                }
            }
        }
        return null;
    }
    if (empty($type)) {
        abort(redirect()->route('dashboard')->with('error', 'Server error'));
    }
    return null;
}

//This Function get the Ghl OAUth
function ghl_oauth_call($code = '', $method = '')
{
    //https://api.msgsndr.com

    $url = 'https://services.leadconnectorhq.com/oauth/token';
    $curl = curl_init();
    $data = [];
    $data['client_id'] = get_default_settings('crm_client_id');
    $data['client_secret'] = get_default_settings('crm_secret_id');
    $md = empty($method) ? 'code' : 'refresh_token';
    $data[$md] = $code;
    $data['grant_type'] = empty($method) ? 'authorization_code' : 'refresh_token';

    $postv = '';
    $x = 0;

    foreach ($data as $key => $value) {
        if ($x > 0) {
            $postv .= '&';
        }
        $postv .= $key . '=' . $value;
        $x++;
    }

    $curlfields = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postv,
    );

    curl_setopt_array($curl, $curlfields);

    $response = curl_exec($curl);


    curl_close($curl);
    $response = json_decode($response);
    return $response;
}

//This function gives the Default version of GHL used in the Connectivity
function getDefaultVersion()
{
    return '2021-07-28';
}
function checkTokenRefresh($is_refresh){

    try{
        if($is_refresh){
            $token = $is_refresh->crm_access_token??null;
            if(is_null($token)){
                return false;
            }
            return true;
        }
    }catch(\Exception $e){

    }
    return false;
}
//This function connect the location if we have agency connected
function connect_location_apicall($locationid,$userId=null,$auth=null,$tries=1)
{
    if(is_null($auth)){
        $auth = is_connected($userId);
    }


    $cmp_id = $auth ? $auth->company_id : get_default_settings_byUser($userId,'company_id');
    $crm_token = $auth ? $auth->crm_access_token : get_default_settings_byUser($userId,'crm_access_token');
    $data= [];
    $data['companyId'] = $cmp_id??'';
    $data['locationId'] = $locationid;
    $client = new \GuzzleHttp\Client(['http_errors' => false, 'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Version' => getDefaultVersion(),
        'Authorization'=>'Bearer '.$crm_token
    ]]);
    $response = $client->request('POST', 'https://services.leadconnectorhq.com/oauth/locationToken', [
        'form_params' => $data,
    ]);

    $resp = $response->getBody()->getContents();
    \Log::info($resp);
    $resp = json_decode($resp);
    //dd($resp);
    //$code  =  ghl_oauth_call( $resp->refresh_token, 'refresh');
    $token=null;
    if($resp){
        if( property_exists($resp, 'access_token') ){
            $con_loc=saveLocationSetting($resp,$userId);
            $resp = $con_loc;
        }
        else if(property_exists($resp,'message') && $tries==1){
            $msg=strtolower($resp->message);
            if($resp->message=='Invalid locationId or accessToken does not have access to following location'){
                saveLogs('NoAccess', $resp->message . ' Location ID : '.$locationid.' - CompanyID : '.$cmp_id);
            }else if(strpos($msg,'jwt')!==false || strpos($msg,'invalid token')!==false){

               $is_refresh =  handleRefresh($locationid,$userId,true,$auth);
               if(checkTokenRefresh($is_refresh)){
                   return connect_location_apicall($locationid,$userId,$auth,$tries+1);
               }

            }
        }
    }
    return $resp;
}

 function saveLogs($msg,$data){
        \DB::table('logs')->insert([
            'details' =>$msg,
            'response' => json_encode($data),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }

//This function is used for GHL  Api Calls
function ghl_api_call($userId= null,$locationid,$url = '', $method = 'get', $data = '', $headers = [], $json = false,$loc=null,$retries=1)
{
    $baseurl = 'https://rest.gohighlevel.com/v1/';
    $bearer = 'Bearer ';
    $token = '';
    $refresh_token = '';
    if(!$loc){
        $loc = ConnectedLocation::where('location_id',$locationid)->first();
        if(!$loc){
            $loc = connect_location_apicall($locationid,$userId);
        }
    }
    $token = $loc->crm_access_token ?? "";
    if(empty($token)){
        return '';
    }
    $refresh_token = $loc->crm_refresh_token ?? "";
    $baseurl = 'services.leadconnectorhq.com/';
    $url = str_replace([$baseurl, 'https://', 'http://'], '', $url);
    $baseurl = 'https://' . $baseurl;
    $location =$locationid;
    $headers['Version'] =getDefaultVersion();
    if ($method == 'get' || $method == 'GET') {
        $urlap = (strpos($url, '?') !== false) ? '&' : '?';
        if (strpos($url, 'location_id=') === false && strpos($url, 'locationId=') === false && strpos($url, 'locations/') === false && strpos($url, 'locations') === false) {
            $url .= $urlap;
            $url .= 'locationId=' . $location;
        }
    }

    // if (strpos($url, 'custom') !== false || strpos($url, 'locations/') !== false || strpos($url, 'locations') === false) {
    //     $url = 'locations/' . $location; // . '/' . $url;
    // }

    if ($token) {
        $headers['Authorization'] =  $bearer . $token;
    }
    $headers['Accept'] = "application/json";
    $headers['Content-Type'] = "application/x-www-form-urlencoded";
    if($json){
        $headers['Content-Type'] ='application/json';
    }
    $client = new \GuzzleHttp\Client(['http_errors' => false, 'headers' => $headers]);
    $options = [];
    if (!empty($data)) {
        if(!$json){
            $keycheck = 'form_data';
            $keycheck1 = 'form_multi';
            if (isset($data[$keycheck]) && is_array($data[$keycheck])) {
                $options['form_params'] = $data[$keycheck];
            } else if (isset($data[$keycheck1]) && is_array($data[$keycheck1])) {
                $options[RequestOptions::MULTIPART] = $data[$keycheck1];
            } else {
                $options['form_data'] = $data;
            }
        }else{
            if(is_string($data)){
                $data = json_decode($data);
            }
            $options[GuzzleHttp\RequestOptions::JSON] = $data;
        }
    }
    $url1 = $baseurl . $url;
    $bd  = null;
    // try {
        $response = $client->request($method, $url1, $options);
        $bd = $response->getBody()->getContents();
        $bd = json_decode($bd);
        // dd($bd);
        if (isset($bd->error) && strtolower($bd->error) == 'unauthorized' && $retries==1) {
            if (strpos(strtolower($bd->message),'authclass')===false) {
               $is_refresh = handleRefresh($locationid,$userId,false,$loc);
              // \Log::info(['data'=>$is_refresh]);
               if (checkTokenRefresh($is_refresh)) {

                sleep(1);
                return ghl_api_call($userId,$locationid,$url, $method, $data, $headers, $json,$retries+1,$is_refresh);
                }

            }

        }
    // } catch (\Exception $e) {
    //     // log the error here

    //     \Log::Warning('CRM_guzzle_connect_exception', [
    //         'url' => $url1,
    //         'message' => $e->getMessage()
    //     ]);
    // }
    return $bd;
}

function transform_name($key)
{

  $key = preg_replace('/\s+/', '', $key);
  $key = str_replace(['(', ')', '-', ' ', '+', '&', '@', '$', '{', '}', '*', '^', '#'], '', $key);
  $key = strtolower($key);
  return $key;
}

function checkValidation(){
    if(empty(get_default_settings('crm_client_id'))||empty(get_default_settings('crm_secret_id')) || empty(get_default_settings('cv_prefix'))){
        return true;
    }
    return false;
}


function getTableColumns1($table, $skip = [], $showcoltype = false)
{

    $columns = DB::getSchemaBuilder()->getColumnListing($table);
    if (!empty($skip)) {
        $columns = array_diff($columns, $skip);
    }

    $cols = [];


    foreach ($columns as $key => $column) {
        $cols[$column] = ucwords(str_replace('_', ' ', $column));
    }

    return $cols;
}
function getTableColumns($table, $skip = [], $showcoltype = false)
{
    $columns = DB::getSchemaBuilder()->getColumnListing($table);
    if (!empty($skip)) {
        $columns = array_diff($columns, $skip);
    }

    $cols = [];


    foreach ($columns as $key => $column) {
        $cols[$column] = ucwords(str_replace('_', ' ', $column));
    }

    return $cols;
}

function createField($field1, $type = 'text', $label = '', $placeholder = '', $required = false, $value = '', $col = 12, $options = [], $readonly=false)
{
    if ($type == 'select' && empty($options)) {
        $type = "text";
        $required = false;
    }
    $extra = "";

    $field = [
        'type' => $type,
        'name' => $field1,
        'label' => $label . $extra,
        'placeholder' => $placeholder,
        'required' => $type == 'file' ? false : $required,
        'value' => $value,
        'col' => $col,
        'readonly'=>$readonly
    ];

    if ($type == 'select' && !empty($options)) {
        $field['options'] = $options;
        $field['is_select2'] = true;
        $field['is_multiple'] = false;
    }

    return $field;
}
function getInitials($fullName)
{
    $parts = explode(" ", $fullName);
    $initials = '';

    foreach ($parts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    return $initials;
}
function get_percentage($total, $req, $cur)
{
    $result = [];
    if ($cur == $req) {
        $result['pass'] = true;
    } else {
        $result['pass'] = false;
    }
    $result['per'] = round(($cur / $req) * 100, 2);

    return $result;
}

function capitalizeFL($string)
{
    return ucwords($string);
}
function change_field_title($name = '')
{
    if ($name == 'Req Attendance') {
        return 'Required Attendance';
    } else if ($name == 'Is Auto') {
        return ' Auto Transfer';
    } else if ($name == 'Total Duration') {
        return ' Total Duration (Days)';
    } else if ($name == 'Tag Name') {
        return 'Class Name';
    } else if ($name == 'Std Contact Id' || $name == 'Attendance Contact Id') {
        return 'Student Name';
    } else if ($name == 'Tag Id' || $name == 'Attendance Tag Id') {
        return 'Class Name';
    } else if ($name == 'Is Finished') {
        return 'Class Completed';
    } else {
        return false;
    }
}
function getFieldType($type)
{
    $type = strtolower($type);

    if (strpos($type, 'email') !== false) {
        return 'email';
    } elseif (strpos($type, 'password') !== false) {
        return 'password';
    } elseif (strpos($type, 'location_name') !== false || strpos($type, 'priority') !== false || strpos($type, 'state') !== false || strpos($type, 'status') !== false || strpos($type, 'medicare') !== false || strpos($type, 'type') !== false) {
        return 'select';
    }
}
function get_ghl_customFields()
{
    $allcustomfield = [];
    if (is_connected() == false) {
        return $allcustomfield;
    }

    $customfields = ghl_api_call('customFields');
    // dd($customfields);
    if ($customfields && property_exists($customfields, 'customFields')) {
        foreach ($customfields->customFields as  $field) {

            if (in_array($field->dataType, ['TEXT', 'LARGE_TEXT','DATE'])) {
                if ($field->fieldKey) {
                    $field->fieldKey = str_replace(['{', '}'], '', $field->fieldKey);
                    $parts = explode('.', $field->fieldKey);
                    $allcustomfield[$parts[1]] = ucfirst(strtolower($field->name));
                }

            }
        }
    }
    return $allcustomfield;
}

function getLocationIds($id='',$save){
        $userId=login_id() ?? '';
        $locationnames=[];
        $locationIds=[];
        $locations= agency_api_call($userId,'locations/search?limit=1000&deleted=false');
        $locations=json_decode($locations);
        if($locations && property_exists($locations,'locations')){
            $locations=$locations->locations;
            foreach($locations  as $loc){
                if($save ){
                    $companyLoc=CompanyLocation::where('company_id',$userId)->where('location_id',$loc->id)->first();
                    if(!$companyLoc || empty($companyLoc) || is_null($companyLoc)){
                        $companyLoc= new CompanyLocation();
                    }
                    $companyLoc->company_id=$userId;
                    $companyLoc->location_id=$loc->id;
                    $companyLoc->location_name=$loc->name ?? 'Unknown';
                    $companyLoc->location_email=$loc->email;
                    $companyLoc->save();
                }
                $locationnames[$loc->name]=$loc->name;
                $locationIds[$loc->id]=$loc->name;
            }
        }

        \session(['location_names' => $locationnames]);
        \session(['location_ids' => $locationIds]);


    return  [$locationIds,$locationnames];
}

function getoptions($type, $key, $id)

{
    $type = strtolower($type);
    if (strpos($type, 'select') !== false && $key == 'priority') {
        $priority=[
            1=>1,
            2=>2,
            3=>3,
            4=>4,
            5=>5,
        ];
        return $priority;

    }elseif (strpos($type, 'select') !== false && $key == 'status') {
        $status=[
            'active'=>'Active',
            'pause'=>'Pause',
            'inactive'=>'inActive',
        ];
        return $status;

    }elseif (strpos($type, 'select') !== false && $key == 'type') {
        $type=[
            'new client'=>'New Client',
            'recurrent'=>'Recurrent',
        ];
        return $type;

    }elseif (strpos($type, 'select') !== false && $key == 'medicare') {
        $medicare=[
            'medicare'=>'Medicare',
            'aca plan'=>'ACA Plan',
            'aca regular'=>'ACA Regular',
            'aca plan and regular'=>'ACA Plan and Regular',
            'lander'=>'Lander',
        ];
        return $medicare;

    }else {
        return [];
    }
}
function get_date($date = null)
{
    if (is_null($date)) {
        $dateTime = new DateTime();
        return $dateTime->format('Y-m-d');
    } else {
        $dateTime = new DateTime($date);
        return $dateTime->format('Y-m-d');
    }
}
function check_attendance($allstudents)
{
    $students = $allstudents->map(function ($enroll) {
        $enroll['contacttags'] = $enroll['contacttags']->map(function ($tag) {
            $attendance = \AttendanceModel::where('attendance_contact_id', $tag["std_contact_id"])
                ->where("attendance_tag_id", $tag['tag_id'])
                ->where('attendance_date', get_date())
                ->first();

            $tag['attendance_status'] = $attendance ? true : false;
            return $tag;
        });

        $initialAttendanceSum = $enroll['contacttags']->pluck('initial_attendance')->sum();
        $enroll['attendance_count'] = $enroll['attendance_count'] + $initialAttendanceSum;

        return $enroll;
    });
    return $students;
}
function imageCheck($request)
{
    //if image, logo, photo, avatar, banner
    $key = 'profile_photo';
    if ($request->hasFile('image')) {
        $key = 'image';
    } elseif ($request->hasFile('logo')) {
        $key = 'logo';
    } elseif ($request->hasFile('profile_photo')) {
        $key = 'profile_photo';
    } elseif ($request->hasFile('avatar')) {
        $key = 'avatar';
    } elseif ($request->hasFile('banner')) {
        $key = 'banner';
    } else {
        return false;
    }
    return $key;
}
function checkIfHtml($string)
{
    if (strpos($string, '<') !== false && strpos($string, '>') !== false && strpos($string, '/') !== false) {
        return true;
    }
    return false;
}

function renderImage($image = '', $small = true, $url = null)
{
    $src = asset('logo.jpg');
    $class = 'img-fluid';
    $style = "height: 100px; width: 100px;";
    if (!empty($image)) {
        if (!$small) {
            $style = "height: 200px; width: 200px;";
        }
        if (!is_null($url)) {
            $src = $url;
        } else {
            $src = asset($image);
        }
    }

    return view('htmls.elements.image', compact('src', 'class', 'style'))->render();
}

function getFormFields($table, $skip = [], $user = '')
{

        $fields = getTableColumns($table, $skip);
        if (!empty($user) && is_array($user)) {
            $user = (object) $user;
        }
    $form = [];

    foreach ($fields as $key => $field) {

        $key1 = ucwords(str_replace('_', ' ', $key));
        $form[$key] = createField($key, getFieldType($key), $field, $field, true, $user->$key ?? '', $col = 6, getoptions(getFieldType($key), $key, $user->id ?? ''),getValidation($key));
    }

    return $form;
}

//this will return true and false
function getValidation($key){
    if(strpos($key, 'location_id') !== false || strpos($key, 'location_name') !== false || strpos($key, 'location_email') !== false || strpos($key, 'leads_dev') !== false || strpos($key, 'company_id') !== false ){
        return true;
    }else{
        return false;
    }
}
function get_table_data($table, $query = '')
{
    $data = DB::table($table)->$query;
    return $data;
}

function getActions($actions = [], $route = '')
{
    //to camel case
    $acs = [];
    foreach ($actions as $key => $action) {

        $acs[$key] = [
            'title' =>  ucwords(str_replace('_', ' ', $key)),
            'route' => $route . '.' . $key,
            'extraclass' => $key == 'delete' ? 'confirm-delete deleted' : '',
        ];
    }

    return $acs;
}


function save_settings($key, $value = '', $userid = null)
{
    if (is_null($userid)) {
        $user_id = login_id();
    } else {
        $user_id = $userid;
    }
    $setting = Setting::updateOrCreate(
        ['user_id' => $user_id, 'key' => $key],
        [
            'value' => $value,
            'user_id' => $user_id,
            'key' => $key
        ]
    );
    return $setting;
}
