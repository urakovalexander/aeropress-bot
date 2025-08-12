<?php

namespace App\MessageHandler;

use App\Application\Telegram\TelegramUpdateHandler;
use App\Message\TelegramMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class TelegramMessageHandler
{
    public function __construct(
        private readonly TelegramUpdateHandler $updateHandler,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(TelegramMessage $message): void
    {
        try {
            $this->logger->info('Processing Telegram message', [
                'update_id' => $message->getUpdate()->getUpdateId()
            ]);
            
            $this->updateHandler->handle($message->getUpdate());
        } catch (\Exception $e) {
            $this->logger->error('Error processing Telegram message', [
                'error' => $e->getMessage(),
                'update_id' => $message->getUpdate()->getUpdateId()
            ]);
            throw $e;
        }
    }
} 