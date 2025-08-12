<?php

namespace App\Domain\User;

use App\Domain\User\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`users`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'bigint', unique: true)]
    private int $telegramId;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $languageCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $username;

    // В будущем здесь можно хранить состояние пользователя, например, какой рецепт он сейчас проходит
    // #[ORM\Column(length: 50, nullable: true)]
    // private ?string $state = null;

    public function __construct(int $telegramId, ?string $firstName, ?string $username)
    {
        $this->telegramId = $telegramId;
        $this->firstName = $firstName;
        $this->username = $username;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTelegramId(): int
    {
        return $this->telegramId;
    }

    public function getLanguageCode(): ?string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(?string $languageCode): self
    {
        $this->languageCode = $languageCode;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }
}
