<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Telegram\Bot\Api;

class TelegramWebhookController extends AbstractController
{
    #[Route('/telegram/webhook', name: 'telegram_webhook', methods: ['POST'])]
    public function webhook(Request $request, Api $telegram): Response
    {
        $update = $telegram->getWebhookUpdate();
        $message = $update->getMessage();

        if ($message && $message->text === '/start') {
            $telegram->sendMessage([
                'chat_id' => $message->chat->id,
                'text' => "🌐 Please choose a language / Пожалуйста, выберите язык",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '🇷🇺 Русский', 'callback_data' => 'lang_ru'],
                            ['text' => '🇬🇧 English', 'callback_data' => 'lang_en'],
                        ],
                    ],
                ]),
            ]);
        }

        return new Response('OK');
    }
}
