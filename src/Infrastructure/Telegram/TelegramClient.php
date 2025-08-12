<?php

namespace App\Infrastructure\Telegram;

use Telegram\Bot\Api;

class TelegramClient
{
    private Api $telegram;

    /**
     * Конструктор получает токен из .env через конфигурацию services.yaml
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->telegram = new Api($token);
    }

    /**
     * Отправляет сообщение пользователю.
     * @param int|string $chatId
     * @param string $text
     * @param array $options Дополнительные параметры, например reply_markup
     */
    public function sendMessage(int|string $chatId, string $text, array $options = []): void
    {
        // SDK ожидает один массив с параметрами
        $params = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $options);

        $this->telegram->sendMessage($params);
    }

    /**
     * Возвращает экземпляр SDK для выполнения специфичных запросов.
     * @return Api
     */
    public function getBot(): Api
    {
        return $this->telegram;
    }
}
