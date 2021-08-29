<?php

namespace App\Http\Controllers\ApiV1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Contracts\Queue\ShouldQueue;

use App\Models\File;
use Illuminate\Support\Facades\DB;

use App\Jobs\ProcessCsvQueue;


use Validator;

 
class ModuleUploadController extends Controller implements ShouldQueue
{
    public function csv_upload(Request $request)
    {
       
        $validator = Validator::make($request->all(),[ 
              'file' => 'required|mimes:txt,csv|max:2048',
        ]);   

        if($validator->fails()){          
            return response()->json(['error'=>$validator->errors()], 401);                        
        }  

        $extension = strtolower( $request->file->getClientOriginalExtension() );
        if ($extension !== 'csv'){
            $errors['file'] = 'This is not a .csv file!';
            return response()->json(['error'=>$errors], 401);                        
        }
        
        if ($file = $request->file('file')) {
            // $path = $file->store('public/files');
            $name = uniqid(time()).'_'.$request->file('file')->getClientOriginalName();
            // $path = $request->file('file')->store('public/files');
            
            $path = $request->file->move(public_path('files'), $name);

            //store your file into directory and db
            $save = new File();
            $save->name = $name;
            $save->path = public_path('files');
            $ret = $save->save();
            $file_info = DB::table('files')->find($save->id);

            ProcessCsvQueue::dispatch($file_info);
            
            return response()->json([
                "success" => true,
                "message" => "CSV File successfully uploaded : File ID ".$save->id
            ]);
  
        }
 
  
    }
}
