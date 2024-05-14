<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use DateTime;
use App\Models\CompanyLocation;
class CVUpdatorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $tries = 1;
    public $data = [];
    public $companyLocation;
    public $timeout = 10000;
    public $failOnTimeout = false;
    public function __construct($data)
    {
        //
        $this->companyLocation = $data['cl'];
        $this->data=$data;    }

    public function handle()
    {
        Log::info("Job Created ".  json_encode($this->data));
        $allContacts = [];
        $counter = 0;
        $nextReq = true;
        $apiUrl = "contacts/?limit=100";
        do {
            $counter++;
            $delay = 1;
            sleep($delay);
            $contactsResponse = $this->fetchContactsFromAPI($apiUrl);

            if (property_exists($contactsResponse,'contacts')) {
                $allContacts = array_merge($allContacts, $contactsResponse->contacts);
                // Check if there's a next page
                if (property_exists($contactsResponse, 'meta') && property_exists($contactsResponse->meta, 'nextPageUrl') && property_exists($contactsResponse->meta, 'nextPage') && !is_null($contactsResponse->meta->nextPage) && !empty($contactsResponse->meta->nextPageUrl)) {
                    $apiUrl = $contactsResponse->meta->nextPageUrl;
                    $nextReq = true;
                }else {
                    Log::error('Failed to fetch contacts for company location ' . $this->companyLocation->id . '. Response: ' . json_encode($contactsResponse));
                    $nextReq = false;
                }
            } else {
                Log::error('Failed to fetch contacts for company location ' . $this->companyLocation->id . '. Response: ' .json_encode($contactsResponse));
                $nextReq = false;
            }
        } while ($nextReq);

        // Process contacts and save to the database
        $this->processAndSaveContacts($allContacts);
    }

    protected function fetchContactsFromAPI($apiUrl)
    {

        $contacts = ghl_api_call(9,$this->companyLocation->location_id,$apiUrl);

        return $contacts;
    }

    protected function updateTotalLeads($totalLeads)
    {
        $this->companyLocation->leads_dev = $totalLeads;
        $this->companyLocation->leads_dem = 0;
        $this->companyLocation->type = 'recurrent';
        $this->companyLocation->status = 'active';
        $this->companyLocation->meicare = 'medicare';
        $this->companyLocation->company_id = login_id(); // Assuming login_id() retrieves the company ID
        $this->companyLocation->save();
    }

    protected function processAndSaveContacts($contacts)
    {
        $today = 0;
        $yesterday = 0;
        $last7days = 0;
        $currentDate = new DateTime();

        foreach ($contacts as $contact) {
            $dateAdded = new DateTime($contact->dataAdded); // Assuming 'dataAdded' is the date field in your contact data
            $interval = $currentDate->diff($dateAdded);
            $daysDiff = $interval->days;

            if ($daysDiff === 0) {
                $today++;
            } elseif ($daysDiff === 1) {
                $yesterday++;
            } elseif ($daysDiff <= 7) {
                $last7days++;
            } else {
                break;
            }
        }

        $this->companyLocation->today = $today;
        $this->companyLocation->yesterday = $yesterday;
        $this->companyLocation->last_7days = $last7days;
        $this->companyLocation->save();
    }
}
