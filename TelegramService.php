<?php

namespace App\utils;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    private function apiUrl($method)
    {
        $token = config('services.telegram.bot_token');
        return "https://api.telegram.org/bot{$token}/{$method}";
    }

    public function sendMessage(array $payload)
    {
        return Http::post($this->apiUrl('sendMessage'), $payload)->json();
    }

    public function answerCallbackQuery(array $payload)
    {
        return Http::post($this->apiUrl('answerCallbackQuery'), $payload)->json();
    }
}
