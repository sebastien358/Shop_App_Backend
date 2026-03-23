<?php

namespace App\Entity;

use App\Repository\CommandItemsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CommandItemsRepository::class)]
class CommandItems
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['commandItems'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['commandItems'])]
    private ?string $title = null;

    #[ORM\Column]
    #[Groups(['commandItems'])]
    private ?int $quantity = null;

    #[ORM\Column]
    #[Groups(['commandItems'])]
    private ?float $price = null;

    #[ORM\ManyToOne(targetEntity: Command::class, inversedBy: 'commandItems')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['commandItems'])]
    private ?Command $command = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'commandItems')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['commandItems'])]
    private ?Product $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCommand(): ?Command
    {
        return $this->command;
    }

    public function setCommand(Command $command): static
    {
        $this->command = $command;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }
}
