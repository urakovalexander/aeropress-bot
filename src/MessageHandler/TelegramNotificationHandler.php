<?php

namespace App\MessageHandler;

use App\Infrastructure\Telegram\TelegramClient;
use App\Message\TelegramNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class TelegramNotificationHandler
{
    public function __construct(
        private readonly TelegramClient $telegramClient,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(TelegramNotification $message): void
    {
        try {
            $this->logger->info('Sending Telegram notification', [
                'chat_id' => $message->getChatId()
            ]);
            
            $this->telegramClient->sendMessage(
                $message->getChatId(),
                $message->getMessage(),
                $message->getOptions()
            );
        } catch (\Exception $e) {
            $this->logger->error('Error sending Telegram notification', [
                'error' => $e->getMessage(),
                'chat_id' => $message->getChatId()
            ]);
            throw $e;
        }
    }
} 