<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'recipients'=>$this->recipients,
            'message'=>$this->message,
            'send_at'=>$this->send_at,
            'status'=>$this->status,
            'provider_response'=>$this->provider_response,
            'sender'=>$this->sender,
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at
        ];
    }
}
