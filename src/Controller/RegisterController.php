<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->logger = $logger;
    }

    #[Route('/api/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $userExist = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($userExist) {
                return $this->json(['type' => 'REGISTER_EMAIL_EXIST', 'message' => 'Un compte existe avec cet email'], Response::HTTP_CONFLICT);
            }

            $user = new User();

            $form = $this->createForm(UserType::class, $user);
            $form->submit($data);

            if (!$form->isSubmitted() || !$form->isValid()) {
                $errors = $this->getErrorMessages($form);
                return $this->json($errors, Response::HTTP_BAD_REQUEST);
            }

            $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
            $user->setRoles(['ROLE_USER']);

            $cart = new Cart();
            $user->setCart($cart);
            $cart->setUser($user);

            $this->entityManager->persist($user);
            $this->entityManager->persist($cart);

            $this->entityManager->flush();

            return $this->json(['message' => 'Un compte a été créé'], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur création d\'un compte', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getErrorMessages(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors() as $key => $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $child) {
            if ($child->isSubmitted() && !$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }
        return $errors;
    }
}
