<?php

namespace App\Domain\User\Repository;

use App\Domain\User\User;
use App\Domain\User\UserSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSession>
 */
class UserSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSession::class);
    }

    /**
     * Найти активную сессию пользователя
     */
    public function findActiveSession(User $user): ?UserSession
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.isActive = :isActive')
            ->setParameter('user', $user)
            ->setParameter('isActive', true)
            ->orderBy('s.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Найти активную сессию для рецепта
     */
    public function findActiveSessionForRecipe(User $user, int $recipeId): ?UserSession
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.recipeId = :recipeId')
            ->andWhere('s.isActive = :isActive')
            ->setParameter('user', $user)
            ->setParameter('recipeId', $recipeId)
            ->setParameter('isActive', true)
            ->orderBy('s.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Создать новую сессию или вернуть существующую
     */
    public function findOrCreateSession(User $user, int $recipeId): UserSession
    {
        $session = $this->findActiveSessionForRecipe($user, $recipeId);
        
        if (!$session) {
            $session = new UserSession($user, $recipeId);
            $this->getEntityManager()->persist($session);
        }
        
        return $session;
    }

    /**
     * Завершить все активные сессии пользователя
     */
    public function completeAllActiveSessions(User $user): void
    {
        $this->createQueryBuilder('s')
            ->update()
            ->set('s.isActive', ':isActive')
            ->set('s.completedAt', ':completedAt')
            ->where('s.user = :user')
            ->andWhere('s.isActive = :wasActive')
            ->setParameter('user', $user)
            ->setParameter('isActive', false)
            ->setParameter('wasActive', true)
            ->setParameter('completedAt', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Получить статистику пользователя
     */
    public function getUserStats(User $user): array
    {
        $qb = $this->createQueryBuilder('s');
        
        $totalSessions = $qb->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $completedSessions = $qb->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->andWhere('s.completedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $totalTime = $qb->select('SUM(s.completedAt - s.startedAt)')
            ->where('s.user = :user')
            ->andWhere('s.completedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_sessions' => (int) $totalSessions,
            'completed_sessions' => (int) $completedSessions,
            'total_time_seconds' => (int) $totalTime,
            'completion_rate' => $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 1) : 0
        ];
    }

    /**
     * Получить популярные рецепты (по количеству сессий)
     */
    public function getPopularRecipes(int $limit = 5): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.recipeId, COUNT(s.id) as sessionCount')
            ->where('s.completedAt IS NOT NULL')
            ->groupBy('s.recipeId')
            ->orderBy('sessionCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
} 