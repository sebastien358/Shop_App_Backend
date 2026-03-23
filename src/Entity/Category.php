<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['categories'])]
    private ?int $id = null;

    #[ORM\Column(length: 125)]
    #[Groups(['categories'])]
    private ?string $name = null;

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category', orphanRemoval: true)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    #[Groups(['categories'])]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

   public function getProducts(): Collection
   {
       return $this->products;
   }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setCategory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
            $product->setCategory(null);
        }

        return $this;
    }
}
