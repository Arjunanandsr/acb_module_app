<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Rules\CsvValidator;

use App\Jobs\ModuleSendEmail;

use Illuminate\Support\Facades\DB;


class ProcessCsvQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fileinfo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fileinfo)
    {
        $this->fileinfo = $fileinfo;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $maildata['error_messages'] = array();
                
        // print_r($this->fileinfo);
        $rules = [
            'Module_code' => 'required|numeric',
            'Module_name' => 'required|alpha|max:255',
            'Module_term' => 'required|alpha|max:255'
        ];
        
        try {
            $messages = array(
                'required' => ':attribute is missing',
                'alpha' => ':attribute must only contain letters',
                'regex' => ':attribute contains special characters'
            );

            // processing up
            DB::table('files')->where('id', $this->fileinfo->id)->update(['stat' => 2]); 

            $fileName = $this->fileinfo->path.'/'.$this->fileinfo->name;
            $csvValidator = (new CsvValidator)->open($fileName, $rules,'UTF-8', $messages);
            $error_messages = $csvValidator->checkFails();
            if ($error_messages) 
            {
                // failed up
                DB::table('files')->where('id', $this->fileinfo->id)->update(['stat' => 3]); 
                // print_r($error_messages);

                // send mail 
                $maildata['subject'] = 'Module CSV file upload File ID : '.$this->fileinfo->id;
                $maildata['body'] = 'The following errros occured';
                $maildata['error_messages'] = $error_messages;

            }else{
                // Success - Get data
                $csvData = $csvValidator->getData();
                // insert in to modules table 
                foreach ($csvData as $rowIndex => $row) 
                {
                    $moduleData[$rowIndex] = $row;
                    $moduleData[$rowIndex]['file_id'] = $this->fileinfo->id;
                }
            
                DB::table('modules')->insert($moduleData);
                // processed success up
                DB::table('files')->where('id', $this->fileinfo->id)->update(['stat' => 1]); 
                // echo "Record inserted successfully.<br/>";

                $succMsg =  "Record inserted successfully.<br/>".$this->fileinfo->id;
                
                // send mail 
                $maildata['subject'] = 'Module CSV file upload File ID : '.$this->fileinfo->id;
                $maildata['body'] = 'The following file successfully uploaded in to database';
                
            }

            $emailJob = new ModuleSendEmail( $maildata );
            dispatch($emailJob);
            
        } catch (\Exception $e) {
            dd($e);
         // ... return and error etc.
        }
    }
}
