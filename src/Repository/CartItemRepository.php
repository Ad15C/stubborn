<?php

namespace App\Repository;

use App\Entity\CartItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartItem>
 *
 * @method CartItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method CartItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method CartItem[]    findAll()
 * @method CartItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    /**
     * Récupère tous les items d’un panier spécifique
     */
    public function findByCartId(int $cartId): array
    {
        return $this->createQueryBuilder('ci')
            ->andWhere('ci.cart = :cartId')
            ->setParameter('cartId', $cartId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un item d’un panier pour un produit précis
     */
    public function findOneByCartAndProduct(int $cartId, int $productId): ?CartItem
    {
        return $this->createQueryBuilder('ci')
            ->andWhere('ci.cart = :cartId')
            ->andWhere('ci.product = :productId')
            ->setParameters([
                'cartId' => $cartId,
                'productId' => $productId,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    
}
