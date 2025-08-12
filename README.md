# AeropressBot ☕️

Telegram бот для рецептов Aeropress с асинхронной обработкой сообщений через RabbitMQ и Redis для таймеров.

## Архитектура

```
Telegram Webhook → RabbitMQ → Workers → Database
```

### Компоненты:
- **Webhook Controller** - принимает сообщения от Telegram
- **Message Bus** - отправляет сообщения в очереди
- **Message Handlers** - обрабатывают сообщения асинхронно
- **RabbitMQ** - очередь сообщений
- **Redis** - кэширование и таймеры
- **PostgreSQL** - база данных пользователей и рецептов

## Установка и настройка

### 1. Зависимости
```bash
composer install
```

### 2. База данных
```bash
# Создание миграций
php bin/console make:migration

# Применение миграций
php bin/console doctrine:migrations:migrate

# Загрузка рецептов
php bin/console app:load-recipes
```

### 3. RabbitMQ и Redis
```bash
# Запуск RabbitMQ и Redis
docker compose up -d rabbitmq redis

# Проверка статуса
docker compose ps
```

### 4. Переменные окружения
Создайте файл `.env.local`:
```env
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_BOT_NAME=your_bot_name
DATABASE_URL="postgresql://app:gt6pyZf7@127.0.0.1:53055/app?serverVersion=16&charset=utf8"
MESSENGER_TRANSPORT_DSN=amqp://app:gt6pyZf7@localhost:5672/%2f/messages
REDIS_URL=redis://localhost:6379
```

## Запуск

### 1. Webhook сервер
```bash
# В одном терминале
php -S localhost:8000 -t public
```

### 2. Workers (воркеры)
```bash
# В другом терминале - обработка сообщений
php bin/console messenger:consume telegram_messages

# В третьем терминале - обработка callback'ов
php bin/console messenger:consume telegram_callbacks

# В четвертом терминале - отправка уведомлений
php bin/console messenger:consume telegram_notifications
```

### 3. Мониторинг
- **RabbitMQ Management**: http://localhost:15672 (app/gt6pyZf7)
- **Redis**: localhost:6379
- **Database**: localhost:53055

## Структура проекта

```
src/
├── Application/
│   └── Telegram/
│       └── TelegramUpdateHandler.php    # Бизнес-логика обработки
├── Command/
│   └── LoadRecipesCommand.php           # Команда загрузки рецептов
├── Domain/
│   ├── Recipe/
│   │   ├── Recipe.php                   # Entity рецепта
│   │   └── Repository/
│   │       └── RecipeRepository.php     # Репозиторий рецептов
│   └── User/
│       ├── User.php                     # Entity пользователя
│       ├── UserSession.php              # Entity сессии пользователя
│       └── Repository/
│           ├── UserRepository.php       # Репозиторий пользователей
│           └── UserSessionRepository.php # Репозиторий сессий
├── Infrastructure/
│   ├── Cache/
│   │   └── RedisService.php            # Сервис для работы с Redis
│   ├── Timer/
│   │   └── TimerService.php            # Сервис таймеров
│   └── Telegram/
│       └── TelegramClient.php           # Клиент Telegram API
├── Message/
│   ├── TelegramMessage.php              # Сообщения для очереди
│   ├── TelegramCallback.php             # Callback'и для очереди
│   └── TelegramNotification.php         # Уведомления для очереди
├── MessageHandler/
│   ├── TelegramMessageHandler.php       # Обработчик сообщений
│   ├── TelegramCallbackHandler.php      # Обработчик callback'ов
│   └── TelegramNotificationHandler.php  # Обработчик уведомлений
└── Presentation/
    └── Controller/
        └── TelegramWebhookController.php # Webhook контроллер
```

## Функциональность бота

### 🍳 Рецепты Aeropress
- **Классический рецепт Джеймса Хоффмана** - идеальный баланс вкуса и простоты
- **Рецепт для начинающих** - простейший способ познакомиться с Aeropress
- **Продвинутый рецепт** - для опытных бариста с точными таймингами

### ⏱️ Таймеры
- **Автоматические таймеры** для каждого шага рецепта
- **Уведомления** о завершении таймера
- **Возможность остановки** таймера в любой момент

### 📋 Ингредиенты и советы
- **Список ингредиентов** для каждого рецепта
- **Полезные советы** по приготовлению
- **Пошаговые инструкции** с подробным описанием

### 🌍 Мультиязычность
- **Русский и английский** языки
- **Автоматическое определение** языка пользователя

## Преимущества архитектуры

1. **Быстрый ответ webhook** - Telegram получает ответ 200 OK мгновенно
2. **Масштабируемость** - можно запускать несколько воркеров
3. **Надежность** - сообщения не теряются при сбоях
4. **Redis для таймеров** - быстрые и надежные таймеры
5. **Мониторинг** - видно все сообщения в RabbitMQ Management
6. **Retry механизм** - автоматические повторные попытки при ошибках

## Команды для отладки

```bash
# Просмотр статистики очередей
php bin/console messenger:stats

# Просмотр неудачных сообщений
php bin/console messenger:failed:show

# Повторная попытка неудачных сообщений
php bin/console messenger:failed:retry

# Остановка воркеров
php bin/console messenger:stop-workers
```

## Разработка

### Добавление нового рецепта:
1. Создайте новый Recipe в `LoadRecipesCommand.php`
2. Добавьте шаги, ингредиенты и таймеры
3. Запустите команду `php bin/console app:load-recipes`

### Добавление нового типа сообщений:
1. Создайте класс сообщения в `src/Message/`
2. Создайте обработчик в `src/MessageHandler/`
3. Добавьте транспорт в `config/packages/messenger.yaml`
4. Запустите новый воркер

### Добавление новых команд бота:
1. Расширьте `TelegramUpdateHandler`
2. Добавьте новые методы обработки
3. Используйте `$this->messageBus->dispatch()` для отправки уведомлений

### Использование Redis:
- **Кэширование** - `$this->redisService->set()` и `$this->redisService->get()`
- **Таймеры** - `$this->timerService->startTimer()` и `$this->timerService->stopTimer()`
- **Сессии** - хранение состояния пользователя

## Команды бота

- `/start` - начало работы с ботом
- `/recipes` или `/рецепты` - показать список рецептов
- `/help` или `/помощь` - показать справку по командам

## Навигация в боте

1. **Главное меню** → Выбор языка → Рецепты или Помощь
2. **Список рецептов** → Выбор рецепта → Информация о рецепте
3. **Рецепт** → Ингредиенты / Советы / Начать приготовление
4. **Приготовление** → Пошаговые инструкции с таймерами
5. **Таймеры** → Запуск / Остановка / Уведомления о завершении

## Технологии

- **Symfony 7.3** - основной фреймворк
- **RabbitMQ** - асинхронная обработка
- **Redis** - таймеры и кэширование
- **PostgreSQL** - база данных
- **Telegram Bot API** - интеграция с Telegram
- **Doctrine ORM** - работа с базой данных 