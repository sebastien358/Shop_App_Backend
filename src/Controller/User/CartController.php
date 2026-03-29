<?php

namespace App\Controller\User;

use App\Entity\Cart;
use App\Entity\CartItems;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/cart')]
final class CartController extends AbstractController
{
    private $entityManager;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/items/list', methods: ['GET'])]
    public function list(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Aucun utilisateur connecté'], Response::HTTP_FORBIDDEN);
            }

            $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
            if (empty($cart)) {
                return $this->json(['message' => 'Cart not found'], Response::HTTP_NOT_FOUND);
            }

            $items = $cart->getCartItems();

            $dataItems = $serializer->normalize($items, 'json', ['groups' => ['cart', 'cart-items', 'products', 'pictures'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);

            $baseUrl = $request->getSchemeAndHttpHost() . '/images/' ;
            foreach ($dataItems as &$item) {
                if (isset($item['product']['pictures'])) {
                    foreach ($item['product']['pictures'] as &$picture) {
                        if (isset($picture['filename'])) {
                            $picture['filename'] = $baseUrl . $picture['filename'];
                        }
                    }
                }
            }

            return $this->json($dataItems, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la récupération des items du panier', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/to/items', methods: ['POST'])]
    public function cart(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non connecté'], Response::HTTP_FORBIDDEN);
            }

            $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
            if (empty($cart)) {
                return $this->json(['error' => 'Cart not found'], Response::HTTP_NOT_FOUND);
            }

            if ($cart->getUser() !== $user) {
                return $this->json(['error' => 'Le panier n\'appartient pas au client'], Response::HTTP_FORBIDDEN);
            }

            foreach ($data as $item) {
                $product = $this->entityManager->getRepository(Product::class)->findOneBy(['id' => $item['id']]);
                if (empty($product)) {
                    return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
                }

                $cartItem = $this->entityManager->getRepository(CartItems::class)->findOneBy(['cart' => $cart, 'product' => $product]);
                if ($cartItem) {
                    $cartItem->setQuantity($cartItem->getQuantity() + $item['quantity']);
                    $this->entityManager->persist($cartItem);
                } else {
                    $cartItem = new CartItems();
                    $cartItem->setCart($cart);
                    $cartItem->setProduct($product);
                    $cartItem->setTitle($item['title']);
                    $cartItem->setPrice($item['price']);
                    $cartItem->setQuantity($item['quantity']);
                    $this->entityManager->persist($cartItem);
                }
            }

            $this->entityManager->flush();

            return $this->json(['message' => 'Produit ajouté au panier'], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->logger->error('Item added to car', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete/item/{id}', methods: ['DELETE'])]
    public function deleteItem(CartItems $cartItems): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_FORBIDDEN);
            }

            $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
            if(!$cart) {
                return $this->json(['error' => 'Aucun panier pour cette utisateur'], Response::HTTP_NOT_FOUND);
            }

            if ($cartItems->getCart() !== $cart) {
                return $this->json(['error' => 'Le produit ne correspond pas au panier'], Response::HTTP_FORBIDDEN);
            }

            if ($cartItems->getQuantity() > 1) {
                $cartItems->setQuantity($cartItems->getQuantity() - 1);
                $this->entityManager->persist($cartItems);
            } else {
                $this->entityManager->remove($cartItems);
            }

            $this->entityManager->flush();

            return $this->json(['message' => 'Le produit a été supprimé du panier'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la récupération des items du panier', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/add/item/{id}', methods: ['POST'])]
    public function addItem(CartItems $cartItems): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_FORBIDDEN);
            }

            $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
            if(!$cart) {
                return $this->json(['error' => 'Aucun panier pour cette utisateur'], Response::HTTP_NOT_FOUND);
            }

            if ($cartItems->getCart() !== $cart) {
                return $this->json(['error' => 'Le produit ne correspond pas au panier'], Response::HTTP_FORBIDDEN);
            }

            $cartItems->setQuantity($cartItems->getQuantity() + 1);
            $this->entityManager->persist($cartItems);

            $this->entityManager->flush();

            return $this->json(['message' => 'Le produit a été ajouté du panier'], Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('Error add item to cart', ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

