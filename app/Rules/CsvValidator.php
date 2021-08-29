<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;


class CsvValidator
{
    private $csvData;
    private $rules;
    private $headingRow;
    private $headerLine;
    private $errors;
    private $headingKeys = [];
    private $headingRows;
    private $headingFailStat = 0;
    private $headingDiff;
    private $messages;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function open($csvPath, $rules, $encoding = 'UTF-8',$messages=array())
    {
        $this->csvData = [];
        $this->messages = $messages;
        $this->setRules($rules);
        $this->headingRows = $this->extractHeaderFromCsv($csvPath);
        
        if(count($rules) != count($this->headingRows) )
        {
            $this->headingFailStat = 1;
            // $this->errors[] = 'Header count not matching.';
            return $this;
        }
        $headdiff = array_diff($this->headingRows,array_keys($rules));
        if( $headdiff )
        {
            $this->headingFailStat = 2;
            $this->headingDiff = $headdiff;
            // $this->errors[] = 'Headers not matching.';
            return $this;
        }

        $csvData = $this->getCsvAsArray($csvPath);

        if (empty($csvData)) {
            throw new \Exception('No data found.');
        }

        $newCsvData = [];
        $ruleKeys = array_keys($this->rules);
        foreach ($csvData as $rowIndex => $csvValues) {
            foreach ($ruleKeys as $ruleKeyIndex) {
                $newCsvData[$rowIndex][$ruleKeyIndex] = $csvValues[$ruleKeyIndex];
            }
        }

        $this->csvData = $newCsvData;

        return $this;
    }

    private function extractHeaderFromCsv($filePath)
    {
        $rows = array_map('str_getcsv', file($filePath));
        return $rows[0]; 
    }

    public function sanitize($line)
    {
         
        $clean =trim($line); 
        return $clean;
    }

    public function getCsvAsArray($filePath, $keyField = null)
    {
        $csvdata = file($filePath,FILE_SKIP_EMPTY_LINES);
        $csvdata = array_filter(array_map("trim", $csvdata), "strlen");
        $rows = array_map("utf8_encode", $csvdata);
        $rows = array_map('str_getcsv', $rows);
        $rowKeys = array_shift($rows);
        // PHP 7.4 and later
       
        $formattedData = [];
        foreach ($rows as $row) 
        {
            $associatedRowData = array_combine($rowKeys, $row);
            $associatedRowData = array_map(array($this,'sanitize'), $associatedRowData);
            if (empty($keyField)) {
                $formattedData[] = $associatedRowData;
            } else {
                $formattedData[$associatedRowData[$keyField]] = $associatedRowData;
            }
        }
        return $formattedData;
    }
    
    public function headingErrors()
    {
        $errors = [];
        if($this->headingFailStat==1)
        {
            $errors[] = 'Header count not matching.';
        }
        elseif($this->headingFailStat==2)
        {
            if(is_array($this->headingDiff)){
                foreach($this->headingDiff as $column => $diff){
                    $errors[] = sprintf('Header column (%s at %s column) is incorrect in csv file',$diff,$this->ordinal($column+1)); 
                }
            }
        }
        return $errors;
    }
    public function ordinal($number) {
        $ends = array('th','st','nd','rd','th','th','th','th','th','th');
        if ((($number % 100) >= 11) && (($number%100) <= 13))
            return $number. 'th';
        else
            return $number. $ends[$number % 10];
    }
    public function checkFails()
    {
        $error_messages = array();
        $headingErrors = $this->headingErrors();
        
        if(!empty($headingErrors))
        {
            foreach($headingErrors as $head)
            {                
                $error_messages[] = $head;
            }
        }
        else if( $this->fails() )
        {
            $cssErrors = $this->getErrors();
            // ... return and error etc.
            $errors = array();
            if(is_array( $cssErrors)){
                foreach ($cssErrors as $rowIndex => $row) {
                    foreach ($row as $column => $messages) {
                        
                        foreach($messages as $message){
                            if(isset($errors[$column][$message])){
                                $errors[$column][$message]   .= ','.$rowIndex;
                            }else{
                                $errors[$column][$message]   = ' at row '.$rowIndex;
                            }
                        }
                    }
                }
            }

            if(is_array( $errors))
            {
                foreach($errors as $module){
                    foreach($module as $key => $err)
                    $error_messages[] = $key.$err;
                }
            }
        }
        return $error_messages;
    }

    public function fails()
    {
        $errors = [];
        foreach ($this->csvData as $rowIndex => $csvValues) {
          
            $validator = Validator::make($csvValues, $this->rules, $this->messages);
            if (!empty($this->headingRows)) {
                $validator->setAttributeNames($this->headingRows);
            }
            if ($validator->fails()) {
                $errors[$rowIndex] = $validator->messages()->toArray();
            }
        }
        $this->errors = $errors;

        return (!empty($this->errors));
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getData()
    {
        return $this->csvData;
    }

    public function setAttributeNames($attribute_names)
    {
        $this->headingRow = $attribute_names;
    }

    private function setRules($rules)
    {
        $this->rules = $rules;
        $this->headingKeys = array_keys($rules);
    }
    

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        //
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}
