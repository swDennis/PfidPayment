<?php
namespace App\Controller;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Shopware\App\SDK\Authentication\ResponseSigner;
use Shopware\App\SDK\Context\ContextResolver;
use Shopware\App\SDK\Response\PaymentResponse;
use Shopware\App\SDK\Shop\ShopResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PaymentFinalizeController extends AbstractController
{
    #[Route('/payment/finalize', name: 'app_payment_finalize')]
    public function index(Request $request, ShopResolver $shopResolver, ContextResolver $contextResolver, ResponseSigner $responseSigner): ResponseInterface
    {
        // TODO: REMOVE AFTER DEBUG
        $errorLogFile =__DIR__ . '/error.log';
        \file_put_contents($errorLogFile, \var_export('FINALIZE', true) . PHP_EOL, FILE_APPEND);
        // TODO: REMOVE AFTER DEBUG

        $shop = $shopResolver->resolveShop($request);

        return $responseSigner->signResponse(PaymentResponse::paid(), $shop);
    }
}