<?php

namespace App\Controller\User;

use App\Entity\Command;
use App\Entity\User;
use App\Repository\CommandRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_USER')]
final class PaymentController extends AbstractController
{
    private string $keyPrivate;
    private EntityManagerInterface $entityManager;

    public function __construct(string $keyPrivate, EntityManagerInterface $entityManager)
    {
        $this->keyPrivate = $keyPrivate;
        $this->entityManager = $entityManager;

    }

    #[Route('/api/payment', methods: ['POST'])]
    public function payment(Request $request, LoggerInterface $logger, ProductRepository $productRepository, CommandRepository $commandRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $token = $data['token'] ?? null;
            $commandId = $data['commandId'] ?? null;
            $items = $data['items'] ?? null;

            if (!$token) {
                return $this->json(['error' => 'Token Stripe requis'], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non connecté'], Response::HTTP_UNAUTHORIZED);
            }

            $totalAmount = 0;

            if ($commandId) {
                // Commande depuis le profil user
                $command = $commandRepository->find($commandId);
                if (!$command) {
                    return $this->json(['error' => 'Commande introuvable'], Response::HTTP_NOT_FOUND);
                }

                foreach ($command->getCommandItems() as $item) {
                    $totalAmount += $item->getPrice() * $item->getQuantity();
                }
            } elseif ($items) {
                // Commande depuis le panier
                foreach ($items as $item) {
                    $product = $productRepository->find($item['productId']);
                    if (!$product) {
                        return $this->json(['error' => 'Produit introuvable: ' . $item['productId']], Response::HTTP_NOT_FOUND);
                    }
                    $totalAmount += $product->getPrice() * $item['quantity'];
                }
            } else {
                return $this->json(['error' => 'Aucune commande ou items fournis'], Response::HTTP_BAD_REQUEST);
            }

            // Montant en centimes pour Stripe

            $totalAmountCents = (int) ($totalAmount * 100);

            $total = 100;

            $stripe = new \Stripe\StripeClient($this->keyPrivate);
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $total,
                'currency' => 'eur',
                'payment_method_data' => [
                    'type' => 'card',
                    'card' => ['token' => $token],
                ],
                'payment_method_types' => ['card'],
                'confirm' => true,
            ]);

            if ($paymentIntent->status === 'succeeded') {
                // Retirer les produits du panier au paiment
                $cart = $user->getCart();
                if ($cart) {
                    foreach ($cart->getCartItems() as $cartItem) {
                        $this->entityManager->remove($cartItem);
                    }
                    $this->entityManager->flush();
                }

                return $this->json([
                    'type' => 'SUCCESS_PAYMENT',
                    'message' => 'Paiement accepté',
                    'paymentId' => $paymentIntent->id
                ]);
            } else {
                return $this->json([
                    'type' => 'ERROR_PAYMENT',
                    'message' => 'Paiement échoué',
                    'status' => $paymentIntent->status
                ], Response::HTTP_BAD_REQUEST);
            }

        } catch (\Stripe\Exception\ApiErrorException $e) {
            $logger->error('Erreur Stripe : ' . $e->getMessage());
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            $logger->error('Erreur serveur : ' . $e->getMessage());
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
