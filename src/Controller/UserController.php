<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Services\MailerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user', name: 'user')]
final class UserController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;
    private $mailerProvider;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher,
        MailerProvider $mailerProvider, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->mailerProvider = $mailerProvider;
        $this->logger = $logger;
    }

    #[Route('/email/existing', methods: ['POST'])]
    public function emailExisting(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $email = $data['email'] ?? null;
            if (!$email) {
                return new JsonResponse(['error' => 'user no exists'], Response::HTTP_NOT_FOUND);
            }

            $emailExists = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($emailExists) {
                return new JsonResponse(['exists' => true, 'message' => 'Email already exists'], Response::HTTP_ACCEPTED);
            } else {
                return new JsonResponse(['exists' => false, 'message' => 'Email does not exist'], Response::HTTP_ACCEPTED);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Error email existing user', ['error' => $e->getMessage()]);
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/request-password', methods: ['POST'])]
    public function emailPassword(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (empty($data['email'])) {
                return $this->json(['error' => 'Email requis'], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);

            if (!$user) {
                return $this->json(['type' => 'REQUEST-PASSWORD', 'message' => 'Aucun compte n\'existe avec cet email'], Response::HTTP_CONFLICT);
            }

            $token = bin2hex(random_bytes(32));
            $hour = new \DateTimeImmutable('+1 hour');

            $user->setResetToken($token);
            $user->setResetTokenExpiresAt($hour);

            $this->entityManager->flush();

            $url = $this->getParameter('frontend_url') . '/reset-password/' . $token;
            $this->sendResetNotification($user, $url);

            return $this->json(['message' => 'Un email a été envoyé'], Response::HTTP_CREATED);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur verification utilissateur', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/reset-password/{token}', methods: ['POST'])]
    public function resetPassword(Request $request, string $token): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (empty($data['password'])) {
                return $this->json(['error' => 'Mot de passe manquant'], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_BAD_REQUEST);
            }

            $newPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($newPassword);

            if ($user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
                return $this->json(['type' => 'RESET-PASSWORD', 'message' => 'Votre demande a expirée'], Response::HTTP_CONFLICT);
            }

            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);

            $this->entityManager->flush();

            return $this->json(['message' => 'Le mot de passe a été modifié'], Response::HTTP_CREATED);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur modification du mot de passe', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function sendResetNotification(User $user, string $url): void
    {
        $body = $this->render('emails/reset-password.html.twig', [
            'url' => $url,
        ])->getContent();

        $this->mailerProvider->sendEmail($user->getEmail(), 'Demande de réinitialisation de mot de passe', $body);
    }
}
