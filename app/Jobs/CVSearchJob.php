<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CVSearchJob implements ShouldQueue
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
        
         $dispatchNow=$this->data['dispatchSync']??false;
        $userId = $this->data['userId']??null;
         $userLoc = $this->data['userLoc']??null;
         $loc_found=$this->data['loc_found']??null;
         $empty_fields=$this->data['emptyCV']??'#';
         //$saveLogs=$this->data['saveLog']??'#';   
         $updatedFormField=$this->data['updatedFormField']??null;
         $count=0;
         // \Log::info($loc_found.' Location found ');
         if($loc_found){
                        $url='locations/'.$loc_found.'/customValues';
                        $cv=\ghl_api_call($userId,$loc_found,$url);
                        $cvUpdated = [];
                        // \Log::info(json_encode($cv));
                        if($cv){
                            foreach ($cv->customValues ??[] as $item) {
                            $name = \transform_name($item->name);
                            $key = \transform_name($item->fieldKey??'');
                            $key = str_replace(['_','/'],'',$key);
                            if($key!='' && $key!=$name){
                                $cvUpdated[$key] = $item->id;
                            }
                            $cvUpdated[$name] = $item->id;
                            }
                         //   \Log::info($cvUpdated);
                          //   \Log::info($updatedFormField);
                            foreach ($updatedFormField as $k => $v) {
                                $murl=$url;
                                try {
                                    $obj = new \stdClass();
                                    $obj->name = $v['title'];
                                    $vt = $v['value'] ??'';
                                    if(empty($vt)){
                                        continue;
                                    }
                                    if (is_array($vt)) {
                                        $vt = implode(', ', $vt);
                                    }
                                    $obj->value = (string)$vt;
                                    $method = 'POST';
                                    if (in_array($k, array_keys($cvUpdated))) {
                                        $murl .= '/' . $cvUpdated[$k];
                                        $method = 'PUT';
    
                                        // if (empty($obj->value)) {
                                            
                                        //     if($empty_fields=='DELETE'){
                                        //         $method = 'DELETE';
                                        //         $obj = '';
                                        //     }
                                        //     else{
                                        //         $obj->value = $empty_fields;
                                        //     }
                                        // }
                                    } 
                                    if (is_object($obj)) {
                                        $obj = json_encode($obj);
                                    }
                                    
                                    if($obj!=''){
                                        $job=new \App\Jobs\CVPushJob(
                                         [
                                              'dispatchSync'=>$dispatchNow,
                                             'userId'=>$userId,
                                             'userLoc'=>$userLoc,
                                              'loc_found'=>$loc_found,
                                              'url'=>$murl,
                                              'method'=>$method,
                                              'obj'=>$obj
                                             ]
                                        );
                                        dispatch($job);
                                    }
                                   // \Log::info('calling');
                                   
    
                                    
                                    
                                } catch (\Throwable $th) {
                                    \Log::info($th->getMessage());
                                }
                                if ($count % 20 == 0) {
                                    sleep(2);
                                }
                                $count++;
                            }
                        }else{
                            return 'Error While Fetching the location details  Please check the Connectivity';
                        }
                       
                        

                        
            }
         
    }
}
