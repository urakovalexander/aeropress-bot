<?php

namespace App\MessageHandler;

use App\Application\Telegram\TelegramUpdateHandler;
use App\Message\TelegramCallback;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class TelegramCallbackHandler
{
    public function __construct(
        private readonly TelegramUpdateHandler $updateHandler,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(TelegramCallback $message): void
    {
        try {
            $this->logger->info('Processing Telegram callback', [
                'update_id' => $message->getUpdate()->getUpdateId(),
                'callback_data' => $message->getUpdate()->getCallbackQuery()->getData()
            ]);
            
            $this->updateHandler->handle($message->getUpdate());
        } catch (\Exception $e) {
            $this->logger->error('Error processing Telegram callback', [
                'error' => $e->getMessage(),
                'update_id' => $message->getUpdate()->getUpdateId()
            ]);
            throw $e;
        }
    }
} 