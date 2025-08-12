<?php

namespace App\Application\Telegram;

use App\Domain\Recipe\Repository\RecipeRepository;
use App\Domain\User\Repository\UserRepository;
use App\Domain\User\Repository\UserSessionRepository;
use App\Infrastructure\Telegram\TelegramClient;
use App\Infrastructure\Timer\TimerService;
use App\Message\TelegramNotification;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Telegram\Bot\Objects\Update;

class TelegramUpdateHandler
{
    public function __construct(
        private readonly TelegramClient $telegramClient,
        private readonly UserRepository $userRepository,
        private readonly UserSessionRepository $userSessionRepository,
        private readonly RecipeRepository $recipeRepository,
        private readonly TimerService $timerService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $messageBus
    ) {}

    public function handle(Update $update): void
    {
        try {
            // Обработка нажатия на inline-кнопку
            if ($update->isType('callback_query')) {
                $this->handleCallbackQuery($update);
            }
            // Обработка входящего сообщения
            elseif ($update->isType('message') && $update->getMessage()->has('text')) {
                $this->handleMessage($update);
            }
        } catch (\Exception $e) {
            $this->logger->error('Telegram Update Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'update_id' => $update->getUpdateId()
            ]);
            // Можно отправить сообщение об ошибке пользователю, если это уместно
        }
    }

    private function handleMessage(Update $update): void
    {
        $message = $update->getMessage();
        $text = $message->getText();
        $chatId = $message->getChat()->getId();
        $from = $message->getFrom();

        // Ищем или создаем пользователя
        $user = $this->userRepository->findOrCreateByTelegramId(
            $chatId,
            $from->getFirstName(),
            $from->getUsername()
        );

        if ($text === '/start') {
            $this->sendLanguageSelection($chatId);
        }
        elseif ($text === '/recipes' || $text === '/рецепты') {
            $this->sendRecipesList($chatId, $user->getLanguageCode() ?? 'en');
        }
        elseif ($text === '/help' || $text === '/помощь') {
            $this->sendHelpMessage($chatId, $user->getLanguageCode() ?? 'en');
        }
        // Здесь можно будет добавлять обработку других команд
    }

    private function handleCallbackQuery(Update $update): void
    {
        $callbackQuery = $update->getCallbackQuery();
        $data = $callbackQuery->getData();
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $from = $callbackQuery->getFrom();

        // Ищем или создаем пользователя
        $user = $this->userRepository->findOrCreateByTelegramId(
            $chatId,
            $from->getFirstName(),
            $from->getUsername()
        );

        // Обработка выбора языка
        if (str_starts_with($data, 'lang_')) {
            $langCode = substr($data, 5); // 'ru' или 'en'
            $user->setLanguageCode($langCode);
            $this->entityManager->flush();

            // Отвечаем на callback, чтобы убрать "часики" на кнопке
            $this->telegramClient->getBot()->answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);

            $this->sendMainMenu($chatId, $langCode);
        }
        // Обработка выбора рецепта
        elseif (str_starts_with($data, 'recipe_')) {
            $recipeId = (int) substr($data, 7);
            $this->handleRecipeSelection($chatId, $recipeId, $user);
        }
        // Обработка выбора шага рецепта
        elseif (str_starts_with($data, 'step_')) {
            $parts = explode('_', $data);
            $recipeId = (int) $parts[1];
            $stepNumber = (int) $parts[2];
            $this->handleStepSelection($chatId, $recipeId, $stepNumber, $user);
        }
        // Обработка запуска таймера
        elseif (str_starts_with($data, 'timer_')) {
            $parts = explode('_', $data);
            $recipeId = (int) $parts[1];
            $stepNumber = (int) $parts[2];
            $this->handleTimerStart($chatId, $recipeId, $stepNumber, $user);
        }
        // Обработка остановки таймера
        elseif (str_starts_with($data, 'stop_timer_')) {
            $parts = explode('_', $data);
            $recipeId = (int) $parts[2];
            $stepNumber = (int) $parts[3];
            $this->handleTimerStop($chatId, $recipeId, $stepNumber, $user);
        }
        // Обработка завершения шага
        elseif (str_starts_with($data, 'complete_step_')) {
            $parts = explode('_', $data);
            $recipeId = (int) $parts[2];
            $stepNumber = (int) $parts[3];
            $this->handleStepCompletion($chatId, $recipeId, $stepNumber, $user);
        }
        // Обработка возврата к рецептам
        elseif ($data === 'back_to_recipes') {
            $this->sendRecipesList($chatId, $user->getLanguageCode());
        }
        // Обработка показа рецептов
        elseif ($data === 'show_recipes') {
            $this->sendRecipesList($chatId, $user->getLanguageCode());
        }
        // Обработка показа помощи
        elseif ($data === 'show_help') {
            $this->sendHelpMessage($chatId, $user->getLanguageCode());
        }
        // Обработка показа ингредиентов
        elseif (str_starts_with($data, 'ingredients_')) {
            $recipeId = (int) substr($data, 11);
            $this->handleIngredientsShow($chatId, $recipeId, $user);
        }
        // Обработка показа советов
        elseif (str_starts_with($data, 'tips_')) {
            $recipeId = (int) substr($data, 5);
            $this->handleTipsShow($chatId, $recipeId, $user);
        }
        // Обработка возврата в главное меню
        elseif ($data === 'back_to_main') {
            $this->sendMainMenu($chatId, $user->getLanguageCode());
        }
    }

    private function sendLanguageSelection(int $chatId): void
    {
        $this->messageBus->dispatch(new TelegramNotification($chatId, "🌐 Please choose a language / Пожалуйста, выберите язык", [
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '🇷🇺 Русский', 'callback_data' => 'lang_ru'],
                        ['text' => '🇬🇧 English', 'callback_data' => 'lang_en'],
                    ],
                ],
            ]),
        ]));
    }

    private function sendMainMenu(int $chatId, string $langCode): void
    {
        $text = ($langCode === 'ru')
            ? "Добро пожаловать в AeropressBot! ☕️\n\nВыберите, что вас интересует:"
            : "Welcome to AeropressBot! ☕️\n\nChoose what you are interested in:";

        $recipesButtonText = ($langCode === 'ru') ? '📚 Рецепты' : '📚 Recipes';
        $helpButtonText = ($langCode === 'ru') ? '❓ Помощь' : '❓ Help';

        $this->messageBus->dispatch(new TelegramNotification($chatId, $text, [
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $recipesButtonText, 'callback_data' => 'show_recipes'],
                    ],
                    [
                        ['text' => $helpButtonText, 'callback_data' => 'show_help'],
                    ],
                ],
            ]),
        ]));
    }

    private function sendRecipesList(int $chatId, string $langCode): void
    {
        $recipes = $this->recipeRepository->findActiveByLanguage($langCode);
        
        if (empty($recipes)) {
            $text = ($langCode === 'ru') 
                ? "Рецепты не найдены. Попробуйте позже."
                : "No recipes found. Try again later.";
            
            $this->messageBus->dispatch(new TelegramNotification($chatId, $text, [
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => ($langCode === 'ru') ? '🔙 Назад' : '🔙 Back', 'callback_data' => 'back_to_main']],
                    ],
                ]),
            ]));
            return;
        }

        $text = ($langCode === 'ru') 
            ? "📚 Выберите рецепт Aeropress:"
            : "📚 Choose an Aeropress recipe:";

        $keyboard = [];
        foreach ($recipes as $recipe) {
            $keyboard[] = [
                [
                    'text' => "{$recipe->getName()} {$recipe->getDifficultyStars()} ({$recipe->getFormattedTotalTime()})",
                    'callback_data' => "recipe_{$recipe->getId()}"
                ]
            ];
        }
        
        $keyboard[] = [['text' => ($langCode === 'ru') ? '🔙 Назад' : '🔙 Back', 'callback_data' => 'back_to_main']];

        $this->messageBus->dispatch(new TelegramNotification($chatId, $text, [
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]));
    }

    private function handleRecipeSelection(int $chatId, int $recipeId, $user): void
    {
        $recipe = $this->recipeRepository->findByIdAndLanguage($recipeId, $user->getLanguageCode());
        
        if (!$recipe) {
            $text = ($user->getLanguageCode() === 'ru') 
                ? "Рецепт не найден."
                : "Recipe not found.";
            
            $this->messageBus->dispatch(new TelegramNotification($chatId, $text));
            return;
        }

        // Создаем или получаем сессию пользователя
        $session = $this->userSessionRepository->findOrCreateSession($user, $recipeId);
        $this->entityManager->flush();

        $text = $this->formatRecipeInfo($recipe, $user->getLanguageCode());
        
        $keyboard = [
            [['text' => ($user->getLanguageCode() === 'ru') ? '▶️ Начать приготовление' : '▶️ Start cooking', 'callback_data' => "step_{$recipeId}_1"]],
            [['text' => ($user->getLanguageCode() === 'ru') ? '📋 Ингредиенты' : '📋 Ingredients', 'callback_data' => "ingredients_{$recipeId}"]],
            [['text' => ($user->getLanguageCode() === 'ru') ? '💡 Советы' : '💡 Tips', 'callback_data' => "tips_{$recipeId}"]],
            [['text' => ($user->getLanguageCode() === 'ru') ? '🔙 Назад к рецептам' : '🔙 Back to recipes', 'callback_data' => 'back_to_recipes']],
        ];

        $this->messageBus->dispatch(new TelegramNotification($chatId, $text, [
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]));
    }

    private function handleStepSelection(int $chatId, int $recipeId, int $stepNumber, $user): void
    {
        $recipe = $this->recipeRepository->findByIdAndLanguage($recipeId, $user->getLanguageCode());
        $session = $this->userSessionRepository->findActiveSessionForRecipe($user, $recipeId);
        
        if (!$recipe || !$session) {
            $this->messageBus->dispatch(new TelegramNotification($chatId, "Error: Recipe or session not found."));
            return;
        }

        $step = $recipe->getStep($stepNumber);
        $timer = $recipe->getTimerForStep($stepNumber);
        
        if (!$step) {
            $this->messageBus->dispatch(new TelegramNotification($chatId, "Step not found."));
            return;
        }

        $text = $this->formatStepInfo($recipe, $stepNumber, $user->getLanguageCode());
        
        $keyboard = [];
        
        if ($timer) {
            $keyboard[] = [
                ['text' => ($user->getLanguageCode() === 'ru') ? '⏱️ Запустить таймер' : '⏱️ Start timer', 'callback_data' => "timer_{$recipeId}_{$stepNumber}"]
            ];
        }
        
        $keyboard[] = [
            ['text' => ($user->getLanguageCode() === 'ru') ? '✅ Завершить шаг' : '✅ Complete step', 'callback_data' => "complete_step_{$recipeId}_{$stepNumber}"]
        ];
        
        if ($stepNumber > 1) {
            $keyboard[] = [
                ['text' => '⬅️', 'callback_data' => "step_{$recipeId}_" . ($stepNumber - 1)]
            ];
        }
        
        if ($stepNumber < $recipe->getStepsCount()) {
            $keyboard[] = [
                ['text' => '➡️', 'callback_data' => "step_{$recipeId}_" . ($stepNumber + 1)]
            ];
        }
        
        $keyboard[] = [['text' => ($user->getLanguageCode() === 'ru') ? '🔙 Назад к рецепту' : '🔙 Back to recipe', 'callback_data' => "recipe_{$recipeId}"]];

        $this->messageBus->dispatch(new TelegramNotification($chatId, $text, [
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]));
    }

    private function handleTimerStart(int $chatId, int $recipeId, int $stepNumber, $user): void
    {
        $recipe = $this->recipeRepository->findByIdAndLanguage($recipeId, $user->getLanguageCode());
        $timer = $recipe->getTimerForStep($stepNumber);
        
        if (!$timer) {
            $this->messageBus->dispatch(new TelegramNotification($chatId, "Timer not available for this step."));
            return;
        }

        $this->timerService->startTimer($chatId, $recipeId, $stepNumber, $timer);
        
        $formattedTime = $this->timerService->formatTime($timer);
        $text = ($user->getLanguageCode() === 'ru') 
            ? "⏱️ Таймер запущен на {$formattedTime}"
            : "⏱️ Timer started for {$formattedTime}";
        
        $keyboard = [
            [['text' => ($user->getLanguageCode() === 'ru') ? '⏹️ Остановить таймер' : '⏹️ Stop timer', 'callback_data' => "stop_timer_{$recipeId}_{$stepNumber}"]],
            [['text' => ($user->getLanguageCode() === 'ru') ? '✅ Завершить шаг' : '✅ Complete step', 'callback_data' => "complete_step_{$recipeId}_{$stepNumber}"]],
            [['text' => ($user->getLanguageCode() === 'ru') ? '🔙 Назад к шагу' : '🔙 Back to step', 'callback_data' => "step_{$recipeId}_{$stepNumber}"]],
        ];

        $this->messageBus->dispatch(new TelegramNotification($chatId, $text, [
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]));
    }

    private function handleTimerStop(int $chatId, int $recipeId, int $stepNumber, $user): void
    {
        $this->timerService->stopTimer($chatId, $recipeId, $stepNumber);
        
        $text = ($user->getLanguageCode() === 'ru') 
            ? "⏹️ Таймер остановлен"
            : "⏹️ Timer stopped";
        
        $this->messageBus->dispatch(new TelegramNotification($chatId, $text, [
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => ($user->getLanguageCode() === 'ru') ? '✅ Завершить шаг' : '✅ Complete step', 'callback_data' => "complete_step_{$recipeId}_{$stepNumber}"]],
                    [['text' => ($user->getLanguageCode() === 'ru') ? '🔙 Назад к шагу' : '🔙 Back to step', 'callback_data' => "step_{$recipeId}_{$stepNumber}"]],
                ],
            ]),
        ]));
    }

    private function handleStepCompletion(int $chatId, int $recipeId, int $stepNumber, $user): void
    {
        $session = $this->userSessionRepository->findActiveSessionForRecipe($user, $recipeId);
        
        if (!$session) {
            $this->messageBus->dispatch(new TelegramNotification($chatId, "Session not found."));
            return;
        }

        $session->markStepCompleted($stepNumber);
        $this->entityManager->flush();

        $recipe = $this->recipeRepository->findByIdAndLanguage($recipeId, $user->getLanguageCode());
        
        $text = ($user->getLanguageCode() === 'ru') 
            ? "✅ Шаг {$stepNumber} завершен!"
            : "✅ Step {$stepNumber} completed!";
        
        if ($stepNumber < $recipe->getStepsCount()) {
            $text .= "\n" . (($user->getLanguageCode() === 'ru') ? "Переходим к следующему шагу:" : "Moving to next step:");
            
            $keyboard = [
                [['text' => ($user->getLanguageCode() === 'ru') ? '➡️ Следующий шаг' : '➡️ Next step', 'callback_data' => "step_{$recipeId}_" . ($stepNumber + 1)]],
                [['text' => ($user->getLanguageCode() === 'ru') ? '🔙 Назад к рецепту' : '🔙 Back to recipe', 'callback_data' => "recipe_{$recipeId}"]],
            ];
        } else {
            $text .= "\n" . (($user->getLanguageCode() === 'ru') ? "🎉 Рецепт завершен! Приятного кофепития!" : "🎉 Recipe completed! Enjoy your coffee!");
            $session->complete();
            $this->entityManager->flush();
            
            $keyboard = [
                [['text' => ($user->getLanguageCode() === 'ru') ? '📚 Другие рецепты' : '📚 Other recipes', 'callback_data' => 'back_to_recipes']],
                [['text' => ($user->getLanguageCode() === 'ru') ? '🔙 В главное меню' : '🔙 Main menu', 'callback_data' => 'back_to_main']],
            ];
        }

        $this->messageBus->dispatch(new TelegramNotification($chatId, $text, [
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]));
    }



    private function formatRecipeInfo($recipe, string $langCode): string
    {
        $text = "📖 {$recipe->getName()}\n\n";
        $text .= $recipe->getDescription() . "\n\n";
        
        $text .= ($langCode === 'ru') 
            ? "⏱️ Время приготовления: {$recipe->getFormattedTotalTime()}\n"
            : "⏱️ Cooking time: {$recipe->getFormattedTotalTime()}\n";
        
        $text .= ($langCode === 'ru') 
            ? "⭐ Сложность: {$recipe->getDifficultyStars()}\n"
            : "⭐ Difficulty: {$recipe->getDifficultyStars()}\n";
        
        $text .= ($langCode === 'ru') 
            ? "📝 Шагов: {$recipe->getStepsCount()}"
            : "📝 Steps: {$recipe->getStepsCount()}";
        
        return $text;
    }

    private function formatStepInfo($recipe, int $stepNumber, string $langCode): string
    {
        $step = $recipe->getStep($stepNumber);
        $timer = $recipe->getTimerForStep($stepNumber);
        
        $text = "📝 Шаг {$stepNumber}/{$recipe->getStepsCount()}\n\n";
        $text .= $step . "\n\n";
        
        if ($timer) {
            $formattedTime = $this->timerService->formatTime($timer);
            $text .= ($langCode === 'ru') 
                ? "⏱️ Таймер: {$formattedTime}"
                : "⏱️ Timer: {$formattedTime}";
        }
        
        return $text;
    }

    private function handleIngredientsShow(int $chatId, int $recipeId, $user): void
    {
        $recipe = $this->recipeRepository->findByIdAndLanguage($recipeId, $user->getLanguageCode());
        
        if (!$recipe) {
            $text = ($user->getLanguageCode() === 'ru') 
                ? "Рецепт не найден."
                : "Recipe not found.";
            
            $this->messageBus->dispatch(new TelegramNotification($chatId, $text));
            return;
        }

        $ingredients = $recipe->getIngredients();
        
        $text = ($user->getLanguageCode() === 'ru') 
            ? "📋 Ингредиенты для рецепта «{$recipe->getName()}»:\n\n"
            : "📋 Ingredients for «{$recipe->getName()}» recipe:\n\n";
        
        foreach ($ingredients as $ingredient) {
            $text .= "• {$ingredient}\n";
        }
        
        $keyboard = [
            [['text' => ($user->getLanguageCode() === 'ru') ? '▶️ Начать приготовление' : '▶️ Start cooking', 'callback_data' => "step_{$recipeId}_1"]],
            [['text' => ($user->getLanguageCode() === 'ru') ? '💡 Советы' : '💡 Tips', 'callback_data' => "tips_{$recipeId}"]],
            [['text' => ($user->getLanguageCode() === 'ru') ? '🔙 Назад к рецепту' : '🔙 Back to recipe', 'callback_data' => "recipe_{$recipeId}"]],
        ];

        $this->messageBus->dispatch(new TelegramNotification($chatId, $text, [
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]));
    }

    private function handleTipsShow(int $chatId, int $recipeId, $user): void
    {
        $recipe = $this->recipeRepository->findByIdAndLanguage($recipeId, $user->getLanguageCode());
        
        if (!$recipe) {
            $text = ($user->getLanguageCode() === 'ru') 
                ? "Рецепт не найден."
                : "Recipe not found.";
            
            $this->messageBus->dispatch(new TelegramNotification($chatId, $text));
            return;
        }

        $tips = $recipe->getTips();
        
        if (empty($tips)) {
            $text = ($user->getLanguageCode() === 'ru') 
                ? "💡 Для этого рецепта пока нет специальных советов."
                : "💡 No special tips for this recipe yet.";
        } else {
            $text = ($user->getLanguageCode() === 'ru') 
                ? "💡 Советы для рецепта «{$recipe->getName()}»:\n\n"
                : "💡 Tips for «{$recipe->getName()}» recipe:\n\n";
            
            foreach ($tips as $index => $tip) {
                $text .= ($index + 1) . ". {$tip}\n";
            }
        }
        
        $keyboard = [
            [['text' => ($user->getLanguageCode() === 'ru') ? '▶️ Начать приготовление' : '▶️ Start cooking', 'callback_data' => "step_{$recipeId}_1"]],
            [['text' => ($user->getLanguageCode() === 'ru') ? '📋 Ингредиенты' : '📋 Ingredients', 'callback_data' => "ingredients_{$recipeId}"]],
            [['text' => ($user->getLanguageCode() === 'ru') ? '🔙 Назад к рецепту' : '🔙 Back to recipe', 'callback_data' => "recipe_{$recipeId}"]],
        ];

        $this->messageBus->dispatch(new TelegramNotification($chatId, $text, [
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]));
    }

    private function sendHelpMessage(int $chatId, string $langCode): void
    {
        $text = ($langCode === 'ru') 
            ? "🤖 AeropressBot - ваш помощник в приготовлении кофе!\n\n"
            : "🤖 AeropressBot - your coffee brewing assistant!\n\n";
        
        $text .= ($langCode === 'ru') 
            ? "📚 Доступные команды:\n\n"
            : "📚 Available commands:\n\n";
        
        $text .= ($langCode === 'ru') 
            ? "/start - Начать работу с ботом\n"
            : "/start - Start working with the bot\n";
        
        $text .= ($langCode === 'ru') 
            ? "/recipes или /рецепты - Показать список рецептов\n"
            : "/recipes - Show recipe list\n";
        
        $text .= ($langCode === 'ru') 
            ? "/help или /помощь - Показать эту справку\n\n"
            : "/help - Show this help\n\n";
        
        $text .= ($langCode === 'ru') 
            ? "☕️ Выберите рецепт и следуйте пошаговым инструкциям с таймерами!"
            : "☕️ Choose a recipe and follow step-by-step instructions with timers!";

        $keyboard = [
            [['text' => ($langCode === 'ru') ? '📚 Рецепты' : '📚 Recipes', 'callback_data' => 'show_recipes']],
        ];

        $this->messageBus->dispatch(new TelegramNotification($chatId, $text, [
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]));
    }
}
