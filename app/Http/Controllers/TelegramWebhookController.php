<?php

namespace App\Http\Controllers;

use App\Models\RestockRequest;
use App\Models\User;
use App\utils\TelegramService;
use Illuminate\Http\Request;

class TelegramWebhookController extends BaseController
{
    public function handle(Request $request)
    {
        $update = $request->all();

        if (!isset($update['callback_query'])) {
            return response()->json(['ok' => true]);
        }

        $callback = $update['callback_query'];
        $data = $callback['data'] ?? '';
        $chatId = $callback['message']['chat']['id'] ?? null;
        $callbackId = $callback['id'] ?? null;

        $adminChatId = config('services.telegram.admin_chat_id');
        if ($adminChatId && (string) $chatId !== (string) $adminChatId) {
            return response()->json(['ok' => true]);
        }

        if (strpos($data, 'restock:') !== 0) {
            return response()->json(['ok' => true]);
        }

        $parts = explode(':', $data);
        if (count($parts) !== 4) {
            return response()->json(['ok' => true]);
        }

        [$prefix, $action, $id, $token] = $parts;
        $restockRequest = RestockRequest::where('id', $id)
            ->where('telegram_token', $token)
            ->first();

        $telegram = new TelegramService();

        if (!$restockRequest || $restockRequest->status !== 'pending') {
            if ($callbackId) {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackId,
                    'text' => 'Request already processed',
                    'show_alert' => false,
                ]);
            }
            return response()->json(['ok' => true]);
        }

        $approvedByUserId = null;
        if (isset($callback['from']['id'])) {
            $telegramId = (string) $callback['from']['id'];
            $user = User::where('telegram_id', $telegramId)->first();
            if ($user) {
                $approvedByUserId = $user->id;
            }
        }

        if ($action === 'approve') {
            $controller = app(RestockController::class);
            $result = $controller->applyTransferSent($restockRequest, $approvedByUserId);

            $text = $result['success'] ? 'Restock approved' : ($result['message'] ?? 'Failed');
            if ($callbackId) {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackId,
                    'text' => $text,
                    'show_alert' => false,
                ]);
            }

            if ($result['success']) {
                $controller->notifyRequesterStatus($restockRequest, 'approved');
            }
        } elseif ($action === 'reject') {
            $restockRequest->status = 'rejected';
            $restockRequest->approved_by = $approvedByUserId;
            $restockRequest->telegram_token = null;
            $restockRequest->save();

            if ($callbackId) {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackId,
                    'text' => 'Restock rejected',
                    'show_alert' => false,
                ]);
            }

            $controller = app(RestockController::class);
            $controller->notifyRequesterStatus($restockRequest, 'rejected');
        }

        return response()->json(['ok' => true]);
    }
}
