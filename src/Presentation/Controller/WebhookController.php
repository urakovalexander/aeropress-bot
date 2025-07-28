<?php
namespace App\Presentation\Controller;

use App\Infrastructure\Telegram\TelegramClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController
{
#[Route('/webhook/telegram', name: 'telegram_webhook', methods: ['POST'])]
public function __invoke(Request $request, TelegramClient $client): JsonResponse
{
$update = json_decode($request->getContent(), true);

$message = $update['message'] ?? null;
if ($message) {
$chatId = $message['chat']['id'];
$text = $message['text'] ?? '';

if ($text === '/start') {
$client->sendMessage($chatId, "👋 Привет! Добро пожаловать в AeropressBot!");
} else {
$client->sendMessage($chatId, "Вы написали: $text");
}
}

return new JsonResponse(['status' => 'ok']);
}
}
