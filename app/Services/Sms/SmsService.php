<?php

namespace App\Services\Sms;

use App\Http\Resources\SmsResource;
use App\Jobs\SendScheduledSms;
use App\Models\SmsModel;
use App\Services\Common\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private $responseService;

    function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    public function sendSms($request)
    {
        try {

            $sendAt = $request->send_at ? \Carbon\Carbon::parse($request->send_at) : null;

            $batch = SmsModel::create([
                'recipients' => $request->recipients,
                'sender'     => $request->sender,
                'message'    => $request->message,
                'send_at'    => $sendAt,
                'status'     => $sendAt == null ? 'pending' : 'queued',
            ]);

            // 2️⃣ Dispatch the job
            if ($sendAt) {
                SendScheduledSms::dispatch($batch)->delay($sendAt);
            } else {
                SendScheduledSms::dispatch($batch);
            }

            return $this->responseService->success('', 'SMS submitted successfully');

        } catch (\Throwable $th) {
            //throw $th;
            return $this->responseService->error("", $th->getMessage());
        }
    }

    public function getSmsHistory(Request $request)
    {
        try {

            $query = SmsModel::latest();

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $history = $query->paginate($request->get('per_page', 15));

            $data = [
                'data' => SmsResource::collection($history),
                'pagination' => [
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total(),
                    'from' => $history->firstItem(),
                    'to' => $history->lastItem()
                ]
            ];

            return $this->responseService->success($data, "Sms fetched");

        } catch (\Throwable $th) {
            //throw $th;
            return $this->responseService->error("", $th->getMessage());
        }
    }

    public function send(array $phones, $message, $sender)
    {
        $to = implode(',', $phones);

        try {
            return Http::timeout(30)->get(config('services.sms.base_url'), [
                'username' => config('services.sms.username'),
                'password' => config('services.sms.password'),
                'message' => $message,
                'sender' => $sender,
                'mobiles' => $to,
            ]);
        } catch (\Exception $e) {
            Log::error('SMS API connection failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getSmsHistoryDetails($id)
    {
        try {

            $history = SmsModel::findOrFail($id);

            return $this->responseService->success(new SmsResource($history), "Sms fetched");

        } catch (\Throwable $th) {
            //throw $th;
            return $this->responseService->error("", $th->getMessage());
        }
    }
}
