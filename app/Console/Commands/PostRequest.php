<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use GuzzleHttp\{
	Client,
	Pool,
	HandlerStack,
	
	Psr7\Request,
	Psr7\Response,
	
	Handler\CurlMultiHandler,
	
	Exception\RequestException
};

use Psr\Http\Message\ResponseInterface;

use Illuminate\Support\Facades\Log;

class PostRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a simple POST request';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
	    //PROCESS FOR QUESTION 4:
        //Single request with error handling:
        
        //Create handler with curl
        $curl = new CurlMultiHandler;
		$handler = HandlerStack::create($curl);
		
		//create client and set custom handler
		$client = new Client(['handler' => $handler]);
		
		$promise = $client->requestAsync('POST', 'https://atomic.incfile.com/fakepost');
		$promise->then(
		    function (ResponseInterface $result) {
			    //Handle success here
			    
		        Log::info("Correct request");
		    },
		    function (RequestException $exception) {
		        //Handle error here
		        
		        $error_status_code = "There was an error with status code: " . $exception->getResponse()->getStatusCode();
				$error_description = ", the error was: \n\n" . $exception->getMessage();
				
				$error_complete_msg = $error_status_code . $error_description;
		        
		        Log::error($error_complete_msg);
		    }
		);
		
		//this will ensure the reliable delivery of the request
		while ($promise->getState() === 'pending') {
		    $curl->tick();
		    
		    //do stuff
		}
		
		//This next line loses kind of a little bit the point of "asynchrousness" for only one request: $promise->wait();
		
		
		
		//------------------------------//
		
		
		//PROCESS FOR QUESTION 5:
		//Multiple requests with error handling:
		
		//This is the number of requests, here you would put the 100k requests
		$total = 50;
		$client = new Client();	
		
		//Segment the number of requests in order to not overload the server
		$concurrency = 10;
		
		$pool = new Pool($client, $this->requestGenerator($total), [
		    'concurrency' => $concurrency,
		    'fulfilled' => function (Response $response, $index) {
			    //Handle success here
			    
		        Log::info("Correct request");
		    },
		    'rejected' => function (RequestException $exception, $index) {
			    //Handle error here
			    
				$error_index = "There was an error in the request number: " . ($index + 1) . ", ";
				$error_status_code = "with status code: " . $exception->getResponse()->getStatusCode();
				$error_description = ", the error was: \n\n" . $exception->getMessage();
				$error_separator = "\n ------------- \n";
				
				$error_complete_msg = $error_index . $error_status_code . $error_description . $error_separator;
			    
				Log::error($error_complete_msg);
				
		    },
		]);
		
		// Start transfers and create promise
		$promise = $pool->promise();
		
		// Force ALL requests to complete.
		$promise->wait();
		
		
    }
    
    //This function just generates the needed requests
    private function requestGenerator($total){
	    $uri = 'https://atomic.incfile.com/fakepost';
	    for ($i = 0; $i < $total; $i++) {
	        yield new Request('POST', $uri);
	    }
    }
    
}
