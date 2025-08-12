<?php

namespace App\Infrastructure\Cache;

use Predis\Client;
use Psr\Log\LoggerInterface;

/**
 * Сервис для работы с Redis
 */
class RedisService
{
    private Client $redis;

    public function __construct(string $redisUrl, private readonly LoggerInterface $logger)
    {
        $this->redis = new Client($redisUrl);
    }

    /**
     * Установить значение с TTL
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        try {
            $serializedValue = is_array($value) ? json_encode($value) : $value;
            return $this->redis->setex($key, $ttl, $serializedValue);
        } catch (\Exception $e) {
            $this->logger->error('Redis set error', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Получить значение
     */
    public function get(string $key): mixed
    {
        try {
            $value = $this->redis->get($key);
            if ($value === null) {
                return null;
            }
            
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        } catch (\Exception $e) {
            $this->logger->error('Redis get error', ['key' => $key, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Удалить ключ
     */
    public function delete(string $key): bool
    {
        try {
            return (bool) $this->redis->del($key);
        } catch (\Exception $e) {
            $this->logger->error('Redis delete error', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Проверить существование ключа
     */
    public function exists(string $key): bool
    {
        try {
            return (bool) $this->redis->exists($key);
        } catch (\Exception $e) {
            $this->logger->error('Redis exists error', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Установить TTL для ключа
     */
    public function expire(string $key, int $ttl): bool
    {
        try {
            return (bool) $this->redis->expire($key, $ttl);
        } catch (\Exception $e) {
            $this->logger->error('Redis expire error', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Получить TTL ключа
     */
    public function ttl(string $key): int
    {
        try {
            return $this->redis->ttl($key);
        } catch (\Exception $e) {
            $this->logger->error('Redis ttl error', ['key' => $key, 'error' => $e->getMessage()]);
            return -1;
        }
    }

    /**
     * Увеличить значение счетчика
     */
    public function increment(string $key, int $value = 1): int
    {
        try {
            return $this->redis->incrby($key, $value);
        } catch (\Exception $e) {
            $this->logger->error('Redis increment error', ['key' => $key, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Установить значение в хэш
     */
    public function hset(string $key, string $field, mixed $value): bool
    {
        try {
            $serializedValue = is_array($value) ? json_encode($value) : $value;
            return (bool) $this->redis->hset($key, $field, $serializedValue);
        } catch (\Exception $e) {
            $this->logger->error('Redis hset error', ['key' => $key, 'field' => $field, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Получить значение из хэша
     */
    public function hget(string $key, string $field): mixed
    {
        try {
            $value = $this->redis->hget($key, $field);
            if ($value === null) {
                return null;
            }
            
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        } catch (\Exception $e) {
            $this->logger->error('Redis hget error', ['key' => $key, 'field' => $field, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Получить весь хэш
     */
    public function hgetall(string $key): array
    {
        try {
            $hash = $this->redis->hgetall($key);
            $result = [];
            
            foreach ($hash as $field => $value) {
                $decoded = json_decode($value, true);
                $result[$field] = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Redis hgetall error', ['key' => $key, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Добавить в список
     */
    public function lpush(string $key, mixed $value): int
    {
        try {
            $serializedValue = is_array($value) ? json_encode($value) : $value;
            return $this->redis->lpush($key, $serializedValue);
        } catch (\Exception $e) {
            $this->logger->error('Redis lpush error', ['key' => $key, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Получить из списка
     */
    public function lpop(string $key): mixed
    {
        try {
            $value = $this->redis->lpop($key);
            if ($value === null) {
                return null;
            }
            
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        } catch (\Exception $e) {
            $this->logger->error('Redis lpop error', ['key' => $key, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Получить размер списка
     */
    public function llen(string $key): int
    {
        try {
            return $this->redis->llen($key);
        } catch (\Exception $e) {
            $this->logger->error('Redis llen error', ['key' => $key, 'error' => $e->getMessage()]);
            return 0;
        }
    }
} 