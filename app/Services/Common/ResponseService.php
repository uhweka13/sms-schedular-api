<?php

namespace App\Services\Common;

class ResponseService
{
    public function success($data, $message = 'Success')
    {
        return response()->json(['data' => $data, 'message' => $message, 'code'=>200], 200);
    }

    public function error($data, $message = 'Error', $status = 400)
    {
        return response()->json(['error' => $data, 'message' => $message, 'code'=>$status], $status);
    }

    public function failedValidation($validator)
    {
        $errors = $validator->errors();
        $errorsList = [];
        foreach ($errors->all() as $message) {
            array_push($errorsList, $message);
        }

        $mainError=implode(",",$errorsList);

        return response()->json(['status'=>false, 'code'=>400, 'message'=>$mainError, 'error'=>"Validation error"], 400);
    }
}
