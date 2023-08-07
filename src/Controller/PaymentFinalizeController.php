<?php

namespace App\Controller;

use App\Entity\OrderPayment;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shopware\App\SDK\Authentication\ResponseSigner;
use Shopware\App\SDK\Response\PaymentResponse;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\App\SDK\Shop\ShopResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class PaymentFinalizeController extends AbstractController
{
    public function __construct(private readonly ShopRepositoryInterface $shopRepository, private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/payment/finalize', name: 'app_payment_finalize')]
    public function index(RequestInterface $request): ResponseInterface
    {
        $orderId = json_decode($request->getBody()->getContents(), true)['orderTransaction']['orderId'];
        $request->getBody()->rewind();

        $shopResolver = new ShopResolver($this->shopRepository);
        $shop = $shopResolver->resolveShop($request);

        $orderPayment = $this->entityManager->getRepository(OrderPayment::class)->findByOrderId($orderId);
        $signer = new ResponseSigner();
        if (!$orderPayment instanceof OrderPayment) {
            return $signer->signResponse(PaymentResponse::failed('Cannot found order'), $shop);
        }

        if ($orderPayment->getAccepted() === 1) {
            return $signer->signResponse(PaymentResponse::paid(), $shop);
        }

        return $signer->signResponse(PaymentResponse::cancelled('User canceled order'), $shop);
    }
}