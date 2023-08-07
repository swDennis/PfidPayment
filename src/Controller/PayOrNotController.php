<?php

namespace App\Controller;

use App\Entity\OrderPayment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class PayOrNotController extends AbstractController
{
    #[Route('/accepted/or/cancel', name: 'app_payment_accept_or_cancel')]
    public function payOrNot(Request $request): Response
    {
        return $this->render('base.html.twig', [
            'opi' => $request->get('opi')
        ]);
    }

    #[Route('/accepted', name: 'app_payment_accepted')]
    public function accepted(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var OrderPayment $orderPayment */
        $orderPayment = $entityManager->getRepository(OrderPayment::class)->findByOrderId($request->get('opi'));

        if (!$orderPayment instanceof OrderPayment) {
            // TODO: REMOVE AFTER DEBUG
            echo '<pre>';
            var_export('No $orderPayment found');
            echo '<br />';
            die();
            // TODO: REMOVE AFTER DEBUG
        }

        $orderPayment->setAccepted(1);

        $entityManager->persist($orderPayment);
        $entityManager->flush();

        return $this->redirect($orderPayment->getReturnUrl());
    }

    #[Route('/canceled', name: 'app_payment_cancel')]
    public function cancel(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var OrderPayment $orderPayment */
        $orderPayment = $entityManager->getRepository(OrderPayment::class)->findByOrderId($request->get('opi'));

        if (!$orderPayment instanceof OrderPayment) {
            // TODO: REMOVE AFTER DEBUG
            echo '<pre>';
            var_export('Cannot find $orderPayment found');
            echo '<br />';
            die();
            // TODO: REMOVE AFTER DEBUG
        }

        $orderPayment->setAccepted(0);

        $entityManager->persist($orderPayment);
        $entityManager->flush();

        return $this->redirect($orderPayment->getReturnUrl());
    }
}