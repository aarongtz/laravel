<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use GuzzleHttp\{
	Client,
};

use Exception;

class SendRequests implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    //Just try to run this job 3 times, if after 3 tries, it doesn't succeed then it'll be moved to the failed jobs:
    
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
		$client = new Client();	
		
		
		$number_requests = 100;
		for($i = 0 ; $i < $number_requests ; $i++ ){
			$result = $client->request('POST', 'https://atomic.incfile.com/fakepost');
			
			/*
					
				IMPORTANT:
			
				If something happens, an exception will be thrown by Guzzle, like for example a 404 HTTP error,
				for this example I can throw an exception manually if the status code is different from 200
				and if my limit of attempts is exceeded, this will be moved to the failed jobs
			*/
			
			$status_code = $result->getStatusCode();
			
			if($status_code != 200){
				throw new Exception('an error occured');
			}
			
		}
		
    }
    
    
    //this can come in handy in this sending request situation, maybe the server that is trying to reach is under maintenance, so I can give an hour interval to try again
    public function retryAfter()
	{
	    return now()->addMinutes(60);
	}
	
	//If I an Exception happens, this method will be called and maybe we could notify someone that jobs are failing
	
	public function failed(Exception $exception)
    {
       //send notification
    }
   
    
    //To retry all failed jobs, we will run the php artisan queue:retry command, sending "all" as parameter:
    //Artisan::call('queue:retry all');
}
