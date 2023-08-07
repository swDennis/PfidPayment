<?php

namespace App\Entity;

use App\Repository\OrderPaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderPaymentRepository::class)]
class OrderPayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $orderId = null;

    #[ORM\Column(type: Types::TEXT)]
    private $returnUrl = null;

    #[ORM\Column]
    private ?int $accepted = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): static
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    public function setReturnUrl($returnUrl): static
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    public function getAccepted(): ?int
    {
        return $this->accepted;
    }

    public function setAccepted(int $accepted): static
    {
        $this->accepted = $accepted;

        return $this;
    }
}
