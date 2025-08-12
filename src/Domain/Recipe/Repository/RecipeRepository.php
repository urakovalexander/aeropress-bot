<?php

namespace App\Domain\Recipe\Repository;

use App\Domain\Recipe\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    /**
     * Найти все активные рецепты для языка
     */
    public function findActiveByLanguage(string $languageCode): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.languageCode = :languageCode')
            ->andWhere('r.isActive = :isActive')
            ->setParameter('languageCode', $languageCode)
            ->setParameter('isActive', true)
            ->orderBy('r.difficulty', 'ASC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Найти рецепт по ID и языку
     */
    public function findByIdAndLanguage(int $id, string $languageCode): ?Recipe
    {
        return $this->createQueryBuilder('r')
            ->where('r.id = :id')
            ->andWhere('r.languageCode = :languageCode')
            ->andWhere('r.isActive = :isActive')
            ->setParameter('id', $id)
            ->setParameter('languageCode', $languageCode)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Найти рецепты по сложности
     */
    public function findByDifficulty(int $difficulty, string $languageCode): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.difficulty = :difficulty')
            ->andWhere('r.languageCode = :languageCode')
            ->andWhere('r.isActive = :isActive')
            ->setParameter('difficulty', $difficulty)
            ->setParameter('languageCode', $languageCode)
            ->setParameter('isActive', true)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Найти быстрые рецепты (до 3 минут)
     */
    public function findQuickRecipes(string $languageCode): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.languageCode = :languageCode')
            ->andWhere('r.isActive = :isActive')
            ->andWhere('r.totalTime <= :maxTime')
            ->setParameter('languageCode', $languageCode)
            ->setParameter('isActive', true)
            ->setParameter('maxTime', 180) // 3 минуты
            ->orderBy('r.totalTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Найти популярные рецепты (по количеству сессий)
     */
    public function findPopularRecipes(string $languageCode, int $limit = 5): array
    {
        // Здесь можно добавить логику для определения популярности
        // Пока возвращаем случайные рецепты
        return $this->createQueryBuilder('r')
            ->where('r.languageCode = :languageCode')
            ->andWhere('r.isActive = :isActive')
            ->setParameter('languageCode', $languageCode)
            ->setParameter('isActive', true)
            ->orderBy('RAND()')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
} 