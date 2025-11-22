<?php

namespace App\Http\Controllers;

use App\Services\Common\ResponseService;
use App\Services\Sms\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmsController extends Controller
{
    private $smsService;
    private $responseService;

    function __construct(SmsService $smsService, ResponseService $responseService)
    {
        $this->smsService = $smsService;
        $this->responseService = $responseService;
    }

    public function sendSms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipients'   => 'required|array|min:1',
            'recipients.*' => 'required|string',
            'message'      => 'required|string',
            'send_at'      => ['nullable', 'date_format:Y-m-d H:i:s', function ($attribute, $value, $fail) {
                if ($value && Carbon::parse($value)->isPast()) {
                    $fail('The ' . $attribute . ' must be a future date and time.');
                }
            }],
            'sender' => 'required|string|max:11'
        ]);

        if ($validator->fails()) {
            return $this->responseService->failedValidation($validator);
        }

        return $this->smsService->sendSms($request);
    }

    public function getSmsHistory(Request $request)
    {
        return $this->smsService->getSmsHistory($request);
    }

    public function getSmsHistoryDetails($id)
    {
        return $this->smsService->getSmsHistoryDetails($id);
    }
}
