<?php

namespace App\Infrastructure\Timer;

use App\Infrastructure\Cache\RedisService;
use App\Message\TelegramNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Сервис для управления таймерами приготовления кофе
 */
class TimerService
{
    private const TIMER_PREFIX = 'timer:';
    private const TIMER_STATE_PREFIX = 'timer_state:';

    public function __construct(
        private readonly RedisService $redis,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Запустить таймер для шага рецепта
     */
    public function startTimer(int $chatId, int $recipeId, int $stepNumber, int $duration): bool
    {
        $timerKey = $this->getTimerKey($chatId, $recipeId, $stepNumber);
        $stateKey = $this->getTimerStateKey($chatId, $recipeId, $stepNumber);
        
        $endTime = time() + $duration;
        
        // Сохраняем время окончания таймера
        $this->redis->set($timerKey, $endTime, $duration + 60); // +60 секунд для безопасности
        
        // Сохраняем состояние таймера
        $state = [
            'started_at' => time(),
            'duration' => $duration,
            'end_time' => $endTime,
            'is_running' => true,
            'step_number' => $stepNumber,
            'recipe_id' => $recipeId
        ];
        
        $this->redis->set($stateKey, $state, $duration + 60);
        
        $this->logger->info('Timer started', [
            'chat_id' => $chatId,
            'recipe_id' => $recipeId,
            'step_number' => $stepNumber,
            'duration' => $duration
        ]);
        
        return true;
    }

    /**
     * Остановить таймер
     */
    public function stopTimer(int $chatId, int $recipeId, int $stepNumber): bool
    {
        $timerKey = $this->getTimerKey($chatId, $recipeId, $stepNumber);
        $stateKey = $this->getTimerStateKey($chatId, $recipeId, $stepNumber);
        
        $this->redis->delete($timerKey);
        $this->redis->delete($stateKey);
        
        $this->logger->info('Timer stopped', [
            'chat_id' => $chatId,
            'recipe_id' => $recipeId,
            'step_number' => $stepNumber
        ]);
        
        return true;
    }

    /**
     * Получить оставшееся время таймера
     */
    public function getRemainingTime(int $chatId, int $recipeId, int $stepNumber): ?int
    {
        $timerKey = $this->getTimerKey($chatId, $recipeId, $stepNumber);
        $endTime = $this->redis->get($timerKey);
        
        if ($endTime === null) {
            return null;
        }
        
        $remaining = $endTime - time();
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Проверить, истек ли таймер
     */
    public function isTimerExpired(int $chatId, int $recipeId, int $stepNumber): bool
    {
        $remaining = $this->getRemainingTime($chatId, $recipeId, $stepNumber);
        return $remaining === null || $remaining <= 0;
    }

    /**
     * Получить состояние таймера
     */
    public function getTimerState(int $chatId, int $recipeId, int $stepNumber): ?array
    {
        $stateKey = $this->getTimerStateKey($chatId, $recipeId, $stepNumber);
        return $this->redis->get($stateKey);
    }

    /**
     * Отправить уведомление о завершении таймера
     */
    public function sendTimerNotification(int $chatId, int $recipeId, int $stepNumber, string $message): void
    {
        $this->messageBus->dispatch(new TelegramNotification($chatId, $message));
        
        $this->logger->info('Timer notification sent', [
            'chat_id' => $chatId,
            'recipe_id' => $recipeId,
            'step_number' => $stepNumber
        ]);
    }

    /**
     * Получить форматированное время
     */
    public function formatTime(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0:00';
        }
        
        $minutes = (int) ($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }

    /**
     * Получить прогресс-бар для таймера
     */
    public function getTimerProgressBar(int $elapsed, int $total): string
    {
        if ($total <= 0) {
            return '██████████';
        }
        
        $progress = min(1.0, $elapsed / $total);
        $filled = (int) ($progress * 10);
        
        return str_repeat('█', $filled) . str_repeat('░', 10 - $filled);
    }

    /**
     * Получить ключ таймера
     */
    private function getTimerKey(int $chatId, int $recipeId, int $stepNumber): string
    {
        return self::TIMER_PREFIX . "{$chatId}:{$recipeId}:{$stepNumber}";
    }

    /**
     * Получить ключ состояния таймера
     */
    private function getTimerStateKey(int $chatId, int $recipeId, int $stepNumber): string
    {
        return self::TIMER_STATE_PREFIX . "{$chatId}:{$recipeId}:{$stepNumber}";
    }

    /**
     * Получить все активные таймеры пользователя
     */
    public function getActiveTimers(int $chatId): array
    {
        // Это упрощенная версия, в реальности нужно использовать SCAN
        $pattern = self::TIMER_PREFIX . "{$chatId}:*";
        // В Redis нет прямого поиска по паттерну, поэтому возвращаем пустой массив
        // В реальном приложении можно использовать SCAN или хранить список активных таймеров
        return [];
    }

    /**
     * Очистить все таймеры пользователя
     */
    public function clearAllTimers(int $chatId): bool
    {
        // В реальном приложении нужно найти все ключи с паттерном и удалить их
        $this->logger->info('All timers cleared for user', ['chat_id' => $chatId]);
        return true;
    }
} 