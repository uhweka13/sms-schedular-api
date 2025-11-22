<?php

namespace Tests\Feature;

use App\Jobs\SendScheduledSms;
use App\Models\SmsModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendSmsTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_sends_sms_immediately_when_send_at_is_null(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $payload = [
            'recipients' => ['08134930676', '08161614106'],
            'sender'     => 'MyApp',
            'message'    => 'Test message',
            'send_at'    => null,
        ];

        $response = $this->postJson('/api/v1/sms/send', $payload);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'SMS submitted successfully',
            ]);

        $this->assertDatabaseHas('sms_models', [
            'sender'  => 'MyApp',
            'message' => 'Test message',
            'status'  => 'pending', // because send_at == null
        ]);

        $sms = SmsModel::first();

        Queue::assertPushed(SendScheduledSms::class);
    }

    public function test_sends_sms_immediately_when_send_at_is_provided(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $sendAt = Carbon::now()->addMinutes(10);

        $payload = [
            'recipients' => ['08134930676', '08161614106'],
            'sender'     => 'MyApp',
            'message'    => 'Test message',
            'send_at'    => $sendAt->toDateTimeString(),
        ];

        $response = $this->postJson('/api/v1/sms/send', $payload);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'SMS submitted successfully',
            ]);

        $this->assertDatabaseHas('sms_models', [
            'sender'  => 'MyApp',
            'message' => 'Test message',
            'status'  => 'queued', // because send_at == null
        ]);

        $sms = SmsModel::first();


        Queue::assertPushed(SendScheduledSms::class);
    }
}
