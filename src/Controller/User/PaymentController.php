<?php

namespace App\Controller\User;

use App\Entity\Command;
use App\Entity\User;
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

    public function __construct(string $keyPrivate)
    {
        $this->keyPrivate = $keyPrivate;
    }

    #[Route('/api/payment', methods: ['POST'])]
    public function payment(Request $request, LoggerInterface $logger): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $token = $data['token'] ?? null;

            if (!$token) {
                return $this->json(['error' => 'Token Stripe requis'], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non connecté'], Response::HTTP_UNAUTHORIZED);
            }

            // Stripe
           //$stripe = new \Stripe\StripeClient($this->keyPrivate);

            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => 100, // 1€ test
                'currency' => 'eur',
                'payment_method_data' => [
                    'type' => 'card',
                    'card' => ['token' => $token],
                ],
                'payment_method_types' => ['card'],
                'confirm' => true,
            ]);

            // Vérifie le statut
            if ($paymentIntent->status === 'succeeded') {
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
            return $this->json(['error' => 'Erreur serveur, paiement non effectué'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
