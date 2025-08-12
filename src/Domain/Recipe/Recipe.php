<?php

namespace App\Domain\Recipe;

use Doctrine\ORM\Mapping as ORM;

/**
 * Рецепт приготовления кофе в Aeropress
 */
#[ORM\Entity]
#[ORM\Table(name: 'recipes')]
class Recipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 10)]
    private string $languageCode;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'json')]
    private array $steps = [];

    #[ORM\Column(type: 'json')]
    private array $ingredients = [];

    #[ORM\Column(type: 'json')]
    private array $timers = [];

    #[ORM\Column(type: 'json')]
    private array $tips = [];

    #[ORM\Column]
    private int $difficulty = 1; // 1-5

    #[ORM\Column]
    private int $totalTime = 0; // в секундах

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $name,
        string $languageCode,
        string $description,
        array $steps,
        array $ingredients,
        array $timers = [],
        array $tips = [],
        int $difficulty = 1
    ) {
        $this->name = $name;
        $this->languageCode = $languageCode;
        $this->description = $description;
        $this->steps = $steps;
        $this->ingredients = $ingredients;
        $this->timers = $timers;
        $this->tips = $tips;
        $this->difficulty = $difficulty;
        $this->createdAt = new \DateTimeImmutable();
        $this->calculateTotalTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function getIngredients(): array
    {
        return $this->ingredients;
    }

    public function getTimers(): array
    {
        return $this->timers;
    }

    public function getTips(): array
    {
        return $this->tips;
    }

    public function getDifficulty(): int
    {
        return $this->difficulty;
    }

    public function getTotalTime(): int
    {
        return $this->totalTime;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Получить шаг по номеру
     */
    public function getStep(int $stepNumber): ?string
    {
        return $this->steps[$stepNumber - 1] ?? null;
    }

    /**
     * Получить таймер для шага
     */
    public function getTimerForStep(int $stepNumber): ?int
    {
        return $this->timers[$stepNumber - 1] ?? null;
    }

    /**
     * Получить количество шагов
     */
    public function getStepsCount(): int
    {
        return count($this->steps);
    }

    /**
     * Рассчитать общее время приготовления
     */
    private function calculateTotalTime(): void
    {
        $this->totalTime = array_sum($this->timers);
    }

    /**
     * Получить форматированное время приготовления
     */
    public function getFormattedTotalTime(): string
    {
        $minutes = (int) ($this->totalTime / 60);
        $seconds = $this->totalTime % 60;
        
        if ($minutes > 0) {
            return sprintf('%d:%02d', $minutes, $seconds);
        }
        
        return sprintf('%ds', $seconds);
    }

    /**
     * Получить уровень сложности в виде звездочек
     */
    public function getDifficultyStars(): string
    {
        return str_repeat('⭐', $this->difficulty);
    }
} 