<?php

namespace App\Message;

class TelegramNotification
{
    public function __construct(
        private readonly int $chatId,
        private readonly string $message,
        private readonly array $options = []
    ) {}

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
} 