<?php
namespace App\Infrastructure\Telegram;

use Telegram\Bot\Api;

class TelegramClient
{
private Api $telegram;

public function __construct(string $token)
{
$this->telegram = new Api($token);
}

public function sendMessage(int|string $chatId, string $text, array $options = []): void
{
$this->telegram->sendMessage(array_merge([
'chat_id' => $chatId,
'text' => $text,
], $options));
}

public function getBot(): Api
{
return $this->telegram;
}
}
