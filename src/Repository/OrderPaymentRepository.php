<?php

namespace App\Repository;

use App\Entity\OrderPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderPayment>
 *
 * @method OrderPayment|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderPayment|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderPayment[]    findAll()
 * @method OrderPayment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderPayment::class);
    }

    public function findByOrderId(string $orderId): ?OrderPayment
    {
        $orderPayment = $this->createQueryBuilder('op')
            ->select(['order_payment'])
            ->from(OrderPayment::class, 'order_payment')
            ->where('order_payment.orderId = :paymentId')
            ->setParameter('paymentId', $orderId)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$orderPayment instanceof OrderPayment) {
            return null;
        }

        return $orderPayment;
    }

    public function saveOrderPayment(string $orderId, string $returnUrl, int $accepted): OrderPayment
    {
        $orderPayment = $this->findByOrderId($orderId);
        if (!$orderPayment instanceof OrderPayment) {
            $orderPayment = new OrderPayment();
        }

        $orderPayment->setOrderId($orderId);
        $orderPayment->setReturnUrl($returnUrl);
        $orderPayment->setAccepted($accepted);

        $this->_em->persist($orderPayment);
        $this->_em->flush();

        return $orderPayment;
    }
}
