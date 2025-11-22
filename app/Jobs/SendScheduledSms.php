<?php

namespace App\Jobs;

use App\Models\SmsModel;
use App\Services\Sms\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendScheduledSms implements ShouldQueue
{
    use Queueable;
    public SmsModel $smsModel;

    public function __construct(SmsModel $smsModel)
    {
        $this->smsModel = $smsModel;
    }

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        if ($this->smsModel->status === 'sent') {
            return;
        }

        try {
            $response = $smsService->send(
                $this->smsModel->recipients,
                $this->smsModel->message,
                $this->smsModel->sender
            );

            $responseData = $response->json();
            $status = ($responseData['status'] ?? '') === 'OK' ? 'sent' : 'failed';

            $this->smsModel->update([
                'status' => $status,
                'sent_at' => now(),
                'provider_response' => $response->body(), // or json_encode($response->json())
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send scheduled SMS', [
                'sms_id' => $this->smsModel->id,
                'error' => $e->getMessage(),
            ]);

            $this->smsModel->update([
                'status' => 'failed',
            ]);

        }
    }


    public int $tries = 3;

    public int $backoff = 30;
}
