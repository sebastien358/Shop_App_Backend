<?php

namespace App\Controller\User;

use App\Entity\CartItems;
use App\Entity\Command;
use Doctrine\ORM\EntityManagerInterface;
use Proxies\__CG__\App\Entity\Cart;
use Psr\Log\LoggerInterface;
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
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(string $keyPrivate, LoggerInterface $logger, EntityManagerInterface $entityManager){
        $this->keyPrivate = $keyPrivate;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/payment', methods: ['POST'])]
    public function payment(Request $request, LoggerInterface $logger): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $token = $data['token'] ?? null;
            $commandId = $data['commandId'] ?? null;
            $cartItems = $data['cartItems'] ?? null;

            if (!$token) {
                return $this->json(['error' => 'Token Stripe requis'], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur non connecté'], Response::HTTP_UNAUTHORIZED);
            }

            $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);

            if ($cart->getUser() !== $user) {
                return $this->json(['error' => 'Le panier ne correspond pas au client'], Response::HTTP_FORBIDDEN);
            }

            $totalAmount = 0;

            if ($commandId) {
                $command = $this->entityManager->getRepository(Command::class)->find($commandId);
                if (!$command) {
                    return $this->json(['error' => 'Command introuvable'], Response::HTTP_NOT_FOUND);
                }

                foreach ($command->getCommandItems() as $commandItem) {
                    $totalAmount += $commandItem->getPrice() * $commandItem->getQuantity();
                }

            } elseif ($cartItems) {
                foreach ($cartItems as $cartItem) {
                    $item = $this->entityManager->getRepository(CartItems::class)->find($cartItem['id']);
                    if (!$item) {
                        return $this->json(['error' => 'Panier vide'], Response::HTTP_NOT_FOUND);
                    }

                    $totalAmount += $item->getPrice() * $item->getQuantity();
                }
            } else {
                return $this->json(['error' => 'Commande ou Items introuvable'], Response::HTTP_NOT_FOUND);
            }

            // Montant en centimes pour Stripe

            $totalAmountCents = (int) ($totalAmount * 100);

            //dd($totalAmountCents);

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
                // Mettre à jour le statut de la commande
                if (isset($command)) {
                    $command->setStatus(Command::STATUS_PAID);
                }

                // Supprimer les produits du panier
                $cart = $user->getCart();
                if ($cart) {
                    foreach ($cart->getCartItems() as $cartItem) {
                        $this->entityManager->remove($cartItem);
                    }
                }

                // Flush unique pour tout enregistrer
                $this->entityManager->flush();

                return $this->json([
                    'type' => 'SUCCESS_PAYMENT',
                    'message' => 'Paiement accepté',
                    'paymentId' => $paymentIntent->id
                ], Response::HTTP_CREATED);
            } else {
                return $this->json([
                    'type' => 'ERROR_PAYMENT',
                    'message' => 'Paiement échoué',
                    'status' => $paymentIntent->status
                ], Response::HTTP_CONFLICT);
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
