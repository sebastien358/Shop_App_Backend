<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['products', 'product'])]
    private ?int $id = null;

    #[ORM\Column(length: 125)]
    #[Groups(['products', 'product'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['products', 'product'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['products', 'product'])]
    private ?float $price = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[Groups(['products', 'product'])]
    private Category $category;

    #[ORM\OneToMany(targetEntity: Picture::class, mappedBy: 'product', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['products', 'product'])]
    private Collection $pictures;

    #[ORM\OneToMany(targetEntity: CartItems::class, mappedBy: 'product', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['products', 'product'])]
    private Collection $cartItems;

    #[ORM\OneToMany(targetEntity: CommandItems::class, mappedBy: 'product', orphanRemoval: true)]
    #[Groups(['products', 'product'])]
    private Collection $commands;

    public function __construct()
    {
        $this->pictures = new ArrayCollection();
        $this->cartItems = new ArrayCollection();
        $this->commands = new ArrayCollection();
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

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

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getPictures(): Collection
    {
        return $this->pictures;
    }

    public function addPicture(Picture $picture): static

    {
        if (!$this->pictures->contains($picture)) {
            $this->pictures->add($picture);
            $picture->setProduct($this);
        }

        return $this;
    }

    public function removePicture(Picture $picture): static
    {
        if ($this->pictures->contains($picture)) {
            $this->pictures->removeElement($picture);
            $picture->setProduct(null);
        }

        return $this;
    }

    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    public function addCartItem(CartItems $cartItem): self
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems[] = $cartItem;
            $cartItem->setProduct($this);
        }

        return $this;
    }

    public function removeCartItem(CartItems $cartItem): self
    {
        if ($this->cartItems->removeElement($cartItem)) {
            if ($cartItem->getProduct() === $this) {
                $cartItem->setProduct(null);
            }
        }

        return $this;
    }

    public function getCommands(): Collection
    {
        return $this->commands;
    }
}

