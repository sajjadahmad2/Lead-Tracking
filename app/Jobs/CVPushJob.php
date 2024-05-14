<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CVPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $tries = 1;
       public $data = [];
    public $timeout = 10000;
    public $failOnTimeout = false;
   public function __construct($data)
    {
       
        
        $this->data=$data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $userId = $this->data['userId']??null;
         $cvUpdated = $this->data['cvUpdated']??null;
         $loc_found=$this->data['loc_found']??null;
          $url=$this->data['url']??null;
           $method=$this->data['method']??null;
            $obj=$this->data['obj']??null;
         if($loc_found && !empty($loc_found)){
             
             
             try {
                               
                                
                     \Log::info($obj);
                              $resp =   \ghl_api_call($userId,$loc_found,$url, $method, $obj,[],true);
                             \Log::info(json_encode($resp));
                            } catch (\Throwable $th) {
                               
                            }
                           
             
             //ghl_api_call($userId,$loc_found,$url, $method, $obj,[],false);
         }
         
         
         
         
        //
    }
}
