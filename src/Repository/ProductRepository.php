<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    // Fonction pour récupérer seulement les produits mis en avant page d'accueil
    public function findFeatured(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isFeatured = :val')
            ->setParameter('val', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Retourne les produits filtrés par tranche de prix
     public function findByPriceRange(?float $min, ?float $max): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($min !== null) {
            $qb->andWhere('p.price >= :min')->setParameter('min', $min);
        }

        if ($max !== null) {
            $qb->andWhere('p.price <= :max')->setParameter('max', $max);
        }

        $qb->orderBy('p.price', 'ASC');

        return $qb->getQuery()->getResult();
    }

    // Retourne la page individuelle d'un produit
    public function findOneById(int $id): ?Product
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //Retourne ltous les produits
    public function findAllProducts(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
