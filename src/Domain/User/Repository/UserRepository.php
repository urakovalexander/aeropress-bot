<?php

namespace App\Domain\User\Repository;

use App\Domain\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Находит пользователя по ID чата Telegram или создает нового, если он не найден.
     * @param int $telegramId
     * @param string|null $firstName
     * @param string|null $username
     * @return User
     */
    public function findOrCreateByTelegramId(int $telegramId, ?string $firstName, ?string $username): User
    {
        $user = $this->findOneBy(['telegramId' => $telegramId]);

        if (!$user) {
            $user = new User($telegramId, $firstName, $username);
            $this->getEntityManager()->persist($user);
            // Сохранение (flush) будет выполнено в сервисе, который вызвал этот метод
        }

        return $user;
    }
}
