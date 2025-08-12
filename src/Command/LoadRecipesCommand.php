<?php

namespace App\Command;

use App\Domain\Recipe\Recipe;
use App\Domain\Recipe\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-recipes',
    description: 'Load sample Aeropress recipes into database'
)]
class LoadRecipesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RecipeRepository $recipeRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Loading Aeropress Recipes');

        // Русские рецепты
        $this->createRussianRecipes();
        
        // Английские рецепты
        $this->createEnglishRecipes();

        $this->entityManager->flush();

        $io->success('Recipes loaded successfully!');

        return Command::SUCCESS;
    }

    private function createRussianRecipes(): void
    {
        // Классический рецепт Джеймса Хоффмана
        $classicRecipe = new Recipe(
            'Классический Aeropress',
            'ru',
            'Классический рецепт от Джеймса Хоффмана - идеальный баланс вкуса и простоты',
            [
                'Поместите фильтр в крышку и промойте горячей водой',
                'Взвесьте 17г кофе и измельчите до среднего помола',
                'Вскипятите воду до 85°C',
                'Установите Aeropress на чашку и залейте 50г воды',
                'Перемешайте и оставьте на 30 секунд',
                'Долейте еще 100г воды и перемешайте',
                'Установите крышку с фильтром и оставьте на 1 минуту',
                'Медленно нажмите на поршень в течение 30 секунд'
            ],
            [
                'Кофе: 17г',
                'Вода: 150г',
                'Температура воды: 85°C',
                'Помол: средний'
            ],
            [30, 60, 30], // таймеры в секундах
            [
                'Используйте свежеобжаренный кофе',
                'Следите за равномерностью помола',
                'Не торопитесь при нажатии поршня'
            ],
            2
        );

        // Рецепт для начинающих
        $beginnerRecipe = new Recipe(
            'Для начинающих',
            'ru',
            'Простейший рецепт для тех, кто только знакомится с Aeropress',
            [
                'Поместите фильтр в крышку',
                'Взвесьте 15г кофе и измельчите до среднего помола',
                'Вскипятите воду до 90°C',
                'Установите Aeropress на чашку',
                'Залейте 60г воды и перемешайте',
                'Оставьте на 1 минуту',
                'Долейте еще 90г воды',
                'Установите крышку и нажмите поршень'
            ],
            [
                'Кофе: 15г',
                'Вода: 150г',
                'Температура воды: 90°C',
                'Помол: средний'
            ],
            [60, 30], // таймеры
            [
                'Не переживайте о точности времени',
                'Главное - равномерный помол',
                'Нажимайте поршень медленно'
            ],
            1
        );

        // Продвинутый рецепт
        $advancedRecipe = new Recipe(
            'Продвинутый рецепт',
            'ru',
            'Для опытных бариста - сложный, но очень вкусный рецепт',
            [
                'Поместите фильтр в крышку и промойте',
                'Взвесьте 18г кофе и измельчите до мелкого помола',
                'Вскипятите воду до 82°C',
                'Установите Aeropress на чашку',
                'Залейте 30г воды и перемешайте',
                'Оставьте на 45 секунд для блуминга',
                'Долейте еще 70г воды и перемешайте',
                'Оставьте на 1 минуту 30 секунд',
                'Установите крышку и медленно нажмите поршень в течение 45 секунд'
            ],
            [
                'Кофе: 18г',
                'Вода: 100г',
                'Температура воды: 82°C',
                'Помол: мелкий'
            ],
            [45, 90, 45], // таймеры
            [
                'Точность времени критична',
                'Следите за температурой воды',
                'Используйте качественный кофе'
            ],
            4
        );

        $this->entityManager->persist($classicRecipe);
        $this->entityManager->persist($beginnerRecipe);
        $this->entityManager->persist($advancedRecipe);
    }

    private function createEnglishRecipes(): void
    {
        // Classic James Hoffmann recipe
        $classicRecipe = new Recipe(
            'Classic Aeropress',
            'en',
            'Classic recipe from James Hoffmann - perfect balance of taste and simplicity',
            [
                'Place filter in cap and rinse with hot water',
                'Weigh 17g coffee and grind to medium',
                'Boil water to 85°C',
                'Set Aeropress on cup and pour 50g water',
                'Stir and let sit for 30 seconds',
                'Add another 100g water and stir',
                'Place cap with filter and let sit for 1 minute',
                'Slowly press plunger for 30 seconds'
            ],
            [
                'Coffee: 17g',
                'Water: 150g',
                'Water temperature: 85°C',
                'Grind: medium'
            ],
            [30, 60, 30], // timers
            [
                'Use freshly roasted coffee',
                'Watch for even grind',
                'Don\'t rush when pressing plunger'
            ],
            2
        );

        // Beginner recipe
        $beginnerRecipe = new Recipe(
            'For Beginners',
            'en',
            'Simplest recipe for those just getting to know Aeropress',
            [
                'Place filter in cap',
                'Weigh 15g coffee and grind to medium',
                'Boil water to 90°C',
                'Set Aeropress on cup',
                'Pour 60g water and stir',
                'Let sit for 1 minute',
                'Add another 90g water',
                'Place cap and press plunger'
            ],
            [
                'Coffee: 15g',
                'Water: 150g',
                'Water temperature: 90°C',
                'Grind: medium'
            ],
            [60, 30], // timers
            [
                'Don\'t worry about exact timing',
                'Main thing is even grind',
                'Press plunger slowly'
            ],
            1
        );

        // Advanced recipe
        $advancedRecipe = new Recipe(
            'Advanced Recipe',
            'en',
            'For experienced baristas - complex but very tasty recipe',
            [
                'Place filter in cap and rinse',
                'Weigh 18g coffee and grind to fine',
                'Boil water to 82°C',
                'Set Aeropress on cup',
                'Pour 30g water and stir',
                'Let sit for 45 seconds for blooming',
                'Add another 70g water and stir',
                'Let sit for 1 minute 30 seconds',
                'Place cap and slowly press plunger for 45 seconds'
            ],
            [
                'Coffee: 18g',
                'Water: 100g',
                'Water temperature: 82°C',
                'Grind: fine'
            ],
            [45, 90, 45], // timers
            [
                'Timing precision is critical',
                'Watch water temperature',
                'Use quality coffee'
            ],
            4
        );

        $this->entityManager->persist($classicRecipe);
        $this->entityManager->persist($beginnerRecipe);
        $this->entityManager->persist($advancedRecipe);
    }
} 