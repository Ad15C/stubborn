<?php

namespace App\Repository;

use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    /**
     * Calcule le total à payer pour Stripe
     *
     * @param int $orderId
     * @return float
     */
    public function calculateTotal(int $orderId): float
    {
        $qb = $this->createQueryBuilder('oi')
            ->select('SUM(oi.price * oi.quantity) as total')
            ->andWhere('oi.order = :orderId')
            ->setParameter('orderId', $orderId)
            ->getQuery()
            ->getSingleScalarResult();

        return (float)$qb;
    }

    /**
     * Affiche le détail d’une commande
     *
     * @param int $orderId
     * @return OrderItem[]
     */
    public function findByOrderId(int $orderId): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.order = :orderId')
            ->setParameter('orderId', $orderId)
            ->getQuery()
            ->getResult();
    }
}
