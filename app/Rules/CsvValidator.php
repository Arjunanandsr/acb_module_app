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

        if(array_diff(array_keys($rules), $this->headingRows) )
        {
            $this->headingFailStat = 2;
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
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $line); // attempt to translate similar characters
        $clean = preg_replace('/[^\w]/', '', $clean); // drop anything but ASCII
        return $clean;
    }

    public function getCsvAsArray($filePath, $keyField = null)
    {
        $csvdata = file($filePath);
        $rows = array_map("utf8_encode", $csvdata);
        $rows = array_map('str_getcsv', $rows);
        $rowKeys = array_shift($rows);
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
            $errors[] = 'Header not matching.';
        }
        return $errors;
    }

    public function checkFails()
    {
        $error_messages = array();
        $headingErrors = $this->headingErrors();

        if($headingErrors)
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
            if (!empty($this->headingRow)) {
                $validator->setAttributeNames($this->headingRow);
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
