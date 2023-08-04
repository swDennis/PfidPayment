<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[AsController]
class PayOrNotController extends AbstractController
{

    #[Route('/pay/or/not', name: 'app_payment_pay_or_not')]
    public function payOrNot(): Response
    {
        return $this->render('base.html.twig', []);
    }
}