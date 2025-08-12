<?php

namespace App\Presentation\Controller;

use App\Infrastructure\Telegram\TelegramClient;
use App\Message\TelegramMessage;
use App\Message\TelegramCallback;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class TelegramWebhookController extends AbstractController
{
    public function __construct(
        private readonly TelegramClient $telegramClient,
        private readonly MessageBusInterface $messageBus
    ) {}

    #[Route('/telegram/webhook', name: 'telegram_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        // Получаем объект Update из SDK
        $update = $this->telegramClient->getBot()->getWebhookUpdate();

        // Отправляем сообщение в очередь для асинхронной обработки
        if ($update->isType('callback_query')) {
            $this->messageBus->dispatch(new TelegramCallback($update));
        } else {
            $this->messageBus->dispatch(new TelegramMessage($update));
        }

        // Telegram ждет от нас ответ 200 OK, чтобы понять, что вебхук получен
        return new Response('OK');
    }
}
