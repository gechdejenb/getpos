<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TelegramAuthController extends BaseController
{
    public function requestAuth(Request $request)
    {
        $user = $request->user('api');
        $expires = Carbon::now()->addMinutes(30)->timestamp;
        $state = base64_encode(json_encode([
            'user_id' => $user->id,
            'exp' => $expires,
        ]));
        $sig = hash_hmac('sha256', $state, config('app.key'));

        $appUrl = rtrim(env('APP_URL', ''), '/');
        $authUrl = $appUrl . '/api/telegram/auth?state=' . urlencode($state) . '&sig=' . $sig;

        $botUsername = config('services.telegram.bot_username');
        if (!$botUsername) {
            return response()->json(['message' => 'Telegram bot username not configured'], 500);
        }

        return response()->json([
            'authUrl' => $authUrl,
            'botUsername' => $botUsername,
        ]);
    }

    public function auth(Request $request)
    {
        $state = $request->query('state');
        $sig = $request->query('sig');

        if (!$state || !$sig || hash_hmac('sha256', $state, config('app.key')) !== $sig) {
            return response('Invalid state', 400);
        }

        $decoded = json_decode(base64_decode($state), true);
        if (!$decoded || !isset($decoded['user_id'], $decoded['exp'])) {
            return response('Invalid state data', 400);
        }

        if (time() > (int) $decoded['exp']) {
            return response('State expired', 400);
        }

        $hash = $request->query('hash');
        if (!$hash) {
            return response('Missing hash', 400);
        }

        $data = $request->query();
        unset($data['hash'], $data['state'], $data['sig']);

        $dataCheck = [];
        foreach ($data as $key => $value) {
            $dataCheck[] = $key . '=' . $value;
        }
        sort($dataCheck);
        $dataCheckString = implode("\n", $dataCheck);

        $botToken = config('services.telegram.bot_token');
        if (!$botToken) {
            return response('Missing bot token', 500);
        }

        $secretKey = hash('sha256', $botToken, true);
        $computedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($computedHash, $hash)) {
            return response('Invalid Telegram hash', 400);
        }

        $user = User::find($decoded['user_id']);
        if (!$user) {
            return response('User not found', 404);
        }

        $user->telegram_id = $request->query('id');
        $user->telegram_username = $request->query('username');
        $user->telegram_first_name = $request->query('first_name');
        $user->telegram_last_name = $request->query('last_name');
        $user->telegram_photo_url = $request->query('photo_url');
        $authDate = $request->query('auth_date');
        $user->telegram_auth_date = $authDate ? Carbon::createFromTimestamp($authDate) : Carbon::now();
        $user->save();

        return response('Telegram account linked successfully', 200);
    }
}
