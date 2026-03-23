<?php

namespace App\Entity;

use App\Repository\CartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CartRepository::class)]
class Cart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['cart'])]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: "cart")]
    #[ORM\JoinColumn(nullable: false, onDelete: "cascade")]
    #[Groups(['cart'])]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: CartItems::class, mappedBy: "cart", cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['cart'])]
    private Collection $cartItems;

    public function __construct()
    {
        $this->cartItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    public function addCartItem(CartItems $cartItem): self
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems->add($cartItem);
            $cartItem->setCart($this);
        }
        return $this;
    }

    public function removeCartItem(CartItems $cartItem): self
    {
        if ($this->cartItems->contains($cartItem)) {
            $this->cartItems->removeElement($cartItem);
            $cartItem->setCart(null);
        }
        return $this;
    }

}
