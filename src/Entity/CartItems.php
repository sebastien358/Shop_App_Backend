<?php

namespace App\Entity;

use App\Repository\CartItemsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CartItemsRepository::class)]
class CartItems
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['cart-items'])]
    private ?int $id = null;

    #[ORM\Column(length: 125)]
    #[Groups(['cart-items'])]
    private ?string $title = null;

    #[ORM\Column]
    #[Groups(['cart-items'])]
    private ?float $price = null;

    #[ORM\Column]
    #[Groups(['cart-items'])]
    private ?int $quantity = null;

    #[ORM\ManyToOne(targetEntity: Cart::class, inversedBy: "cartItems")]
    #[ORM\JoinColumn(nullable: false, onDelete: "cascade")]
    #[Groups(['cart-items'])]
    private ?Cart $cart = null;


    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: "cartItems")]
    #[ORM\JoinColumn(nullable: false, onDelete: "cascade")]
    #[Groups(['cart-items'])]
    private ?Product $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

   public function getProduct(): ?Product
   {
       return $this->product;
   }

   public function setProduct(Product $product): static
   {
       $this->product = $product;

       return $this;
   }
}
