<?php

namespace App\Message;

use Telegram\Bot\Objects\Update;

class TelegramMessage
{
    public function __construct(
        private readonly Update $update
    ) {}

    public function getUpdate(): Update
    {
        return $this->update;
    }
} 