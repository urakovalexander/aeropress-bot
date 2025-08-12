<?php

namespace App\Message;

use Telegram\Bot\Objects\Update;

class TelegramCallback
{
    public function __construct(
        private readonly Update $update
    ) {}

    public function getUpdate(): Update
    {
        return $this->update;
    }
} 