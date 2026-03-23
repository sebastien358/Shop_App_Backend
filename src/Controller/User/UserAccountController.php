<?php

namespace App\Controller\User;

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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/user/account')]
#[IsGranted('ROLE_USER')]
class UserAccountController extends AbstractController
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

    #[Route('/me', methods: ['GET'])]
    public function me(SerializerInterface $serializer): JsonResponse
    {
        try {
            $user = $this->getUser();

            $dataUser = $serializer->normalize($user, 'json', ['groups' => ['user']]);
        } catch(\Throwable $e) {
            $this->logger->error('Something went wrong', ['Error' => $e->getMessage()]);
            return $this->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($dataUser, Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function currentId(int $id, SerializerInterface $serializer): JsonResponse
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->find($id);
            if (!$user) {
                return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $dataUser = $serializer->normalize($user, 'json', ['groups' => ['user']]);
            return $this->json($dataUser, Response::HTTP_ACCEPTED);
        } catch(\Throwable $e) {
            $this->logger->error('Something went wrong : ', ['Error' => $e->getMessage()]);
            return $this->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/edit/{id}', methods: ['PATCH'])]
    public function edit(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $userExist = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if (!$userExist) {
                return $this->json(['type' => 'ACCOUNT_EDIT_USER', 'message' => 'Aucun compte n\'existe avec cet email'], Response::HTTP_CONFLICT);
            }

            $user = $this->getUser();

            if (!$user || $user->getId() !== $id) {
                return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
            }

            $form = $this->createForm(UserType::class, $user);
            $form->submit($data, false);

            if (!$form->isValid()) {
                $errors = $this->getErrorMessages($form);
                return $this->json($errors, Response::HTTP_BAD_REQUEST);
            }

            if (!empty($data['password'])) {
                $newPassword = $this->passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($newPassword);
            }

            $this->entityManager->flush();

            return $this->json(['message' => 'Données modifiées'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            $this->logger->error('Erreur modification : ', ['error' => $e->getMessage()]);
            return $this->json(['message' => 'Erreur serveur'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
