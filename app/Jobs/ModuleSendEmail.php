<?php

namespace App\Jobs;

use Mail;
use App\Mail\ModuleStatusEmail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ModuleSendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $maildata;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($maildata)
    {
        $this->maildata = $maildata;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $MODULE_MAIL_STATUS_MAIL = env("MODULE_MAIL_STATUS_MAIL", "testmail@gmail.com"); 
        $email = new ModuleStatusEmail($this->maildata);
        Mail::to($MODULE_MAIL_STATUS_MAIL)->send($email);
        //   echo 'This send';
    }
}
