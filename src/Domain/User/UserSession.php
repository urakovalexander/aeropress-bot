<?php

namespace App\Domain\User;

use Doctrine\ORM\Mapping as ORM;

/**
 * Сессия пользователя для отслеживания прогресса рецептов
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_sessions')]
class UserSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column]
    private int $recipeId;

    #[ORM\Column]
    private int $currentStep = 1;

    #[ORM\Column(type: 'json')]
    private array $completedSteps = [];

    #[ORM\Column(type: 'json')]
    private array $timerStates = [];

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct(User $user, int $recipeId)
    {
        $this->user = $user;
        $this->recipeId = $recipeId;
        $this->startedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRecipeId(): int
    {
        return $this->recipeId;
    }

    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }

    public function setCurrentStep(int $currentStep): self
    {
        $this->currentStep = $currentStep;
        return $this;
    }

    public function getCompletedSteps(): array
    {
        return $this->completedSteps;
    }

    public function markStepCompleted(int $stepNumber): self
    {
        if (!in_array($stepNumber, $this->completedSteps)) {
            $this->completedSteps[] = $stepNumber;
        }
        return $this;
    }

    public function isStepCompleted(int $stepNumber): bool
    {
        return in_array($stepNumber, $this->completedSteps);
    }

    public function getTimerStates(): array
    {
        return $this->timerStates;
    }

    public function setTimerState(int $stepNumber, array $state): self
    {
        $this->timerStates[$stepNumber] = $state;
        return $this;
    }

    public function getTimerState(int $stepNumber): ?array
    {
        return $this->timerStates[$stepNumber] ?? null;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function complete(): self
    {
        $this->isActive = false;
        $this->completedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Получить прогресс в процентах
     */
    public function getProgressPercentage(): int
    {
        if (empty($this->completedSteps)) {
            return 0;
        }

        // Предполагаем, что у рецепта максимум 10 шагов
        $maxSteps = 10;
        return (int) ((count($this->completedSteps) / $maxSteps) * 100);
    }

    /**
     * Получить время, проведенное в сессии
     */
    public function getSessionDuration(): int
    {
        $endTime = $this->completedAt ?? new \DateTimeImmutable();
        return $endTime->getTimestamp() - $this->startedAt->getTimestamp();
    }

    /**
     * Получить форматированное время сессии
     */
    public function getFormattedSessionDuration(): string
    {
        $duration = $this->getSessionDuration();
        $minutes = (int) ($duration / 60);
        $seconds = $duration % 60;
        
        if ($minutes > 0) {
            return sprintf('%d:%02d', $minutes, $seconds);
        }
        
        return sprintf('%ds', $seconds);
    }
} 