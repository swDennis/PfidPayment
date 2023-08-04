<?php
namespace App\Controller;

use App\Entity\Shop;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shopware\App\SDK\Authentication\ResponseSigner;
use Shopware\App\SDK\Context\ContextResolver;
use Shopware\App\SDK\Response\PaymentResponse;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\App\SDK\Shop\ShopResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class PaymentPayController extends AbstractController
{
    public function __construct(private readonly ShopRepositoryInterface $shopRepository)
    {
    }

    #[Route('/payment/pay', name: 'app_payment_pay')]
    public function index(RequestInterface $request): ResponseInterface
    {
        $shopResolver = new ShopResolver($this->shopRepository);
        $signer = new ResponseSigner();

        $shop = $shopResolver->resolveShop($request);

        $url = 'http://pfidpayment.localhost' .$this->generateUrl('app_payment_pay_or_not');

        return $signer->signResponse(PaymentResponse::redirect($url), $shop);
    }
}