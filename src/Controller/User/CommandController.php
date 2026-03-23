<?php

namespace App\Controller\User;

use App\Entity\Cart;
use App\Entity\Command;
use App\Entity\CommandItems;
use App\Form\CommandType;
use App\Services\CommandService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/command')]
#[IsGranted("ROLE_USER")]
final class CommandController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CommandService $commandService;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, CommandService $commandService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->commandService = $commandService;
        $this->logger = $logger;
    }

    // Affichage de la commande au paiment

    #[Route('/user', methods: ['GET'])]
    public function user(SerializerInterface $serializer): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_FORBIDDEN);
            }

            $commands = $this->entityManager->getRepository(Command::class)->findOneBy(['user' => $user]);
            if (!$commands) {
                return $this->json(['error' => 'Erreur récupératioçn d\'une commande utilisateur'], Response::HTTP_BAD_REQUEST);
            }

            $dataCommands = $serializer->normalize($commands, 'json', ['groups' => ['commands', 'commandItems'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
            return $this->json($dataCommands, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur récupératioçn d\'une commande utilisateur', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Affichage de la liste des commandes d'un utilisateur

    #[Route('/user/list', methods: ['GET'])]
    public function list(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_FORBIDDEN);
            }

            $commands = $this->entityManager->getRepository(Command::class)->findBy(['user' => $user]);
            if (!$commands) {
                return new JsonResponse(['error' => 'no command user'], Response::HTTP_NO_CONTENT);
            }

            $dataCommands = $this->commandService->getCommandData($request, $commands, $serializer);
            return new JsonResponse($dataCommands, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('error recovery commands', ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Récupération d'une commande pour modifier les données utilisateur

    #[Route('/user/{id}', methods: ['GET'])]
    public function currentId(int $id, Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_FORBIDDEN);
            }

            $commands = $this->entityManager->getRepository(Command::class)->findOneBy(['user' => $user, 'id' => $id]);
            if (!$commands) {
                return new JsonResponse(['error' => 'no command user'], Response::HTTP_NO_CONTENT);
            }

            $dataCommand = $this->commandService->getCommandData($request, $commands, $serializer);
            return new JsonResponse($dataCommand, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('error recovery commands', ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Passer une commande utilidateur

    #[Route('/add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_FORBIDDEN);
            }

            $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
            if (!$cart) {
                return $this->json(['error' => 'No cart user'], Response::HTTP_NOT_FOUND);
            }

            $command = new Command();
            $command->setUser($user);

            $form = $this->createForm(CommandType::class, $command);
            $form->submit($data);

            if (!$form->isSubmitted() || !$form->isValid()) {
                $errors = $this->getErrorMessages($form);
                return $this->json($errors, Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($command);

            $cartItems = $cart->getCartItems();
            if ($cartItems->isEmpty()) {
                return $this->json(['error' => 'Panier vide'], Response::HTTP_BAD_REQUEST);
            }

            foreach ($cartItems as $item) {
                $commandItem = new CommandItems();

                $commandItem->setProduct($item->getProduct());
                $commandItem->setTitle($item->getTitle());
                $commandItem->setPrice($item->getPrice());
                $commandItem->setQuantity($item->getQuantity());

                $commandItem->setCommand($command);
                $this->entityManager->persist($commandItem);
            }

            $this->entityManager->flush();

            return $this->json(['message' => 'Commande validée'], Response::HTTP_CREATED);
        } catch(\Throwable $e) {
            $this->logger->error('Error commande : ', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/remove/{id}', methods: ['DELETE'])]
    public function delete(Command $command): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_FORBIDDEN);
            }

            if ($command->getUser() !== $user) {
                return $this->json(['error' => 'La commande n\'appartient pas à l\'utilisateur connecté'], Response::HTTP_NOT_FOUND);
            }

            if ($command->getStatus() === Command::STATUS_PAID) {
                return $this->json(['error' => 'Impossible de supprimer une coimmande payée'], Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($command);
            $this->entityManager->flush();

            return $this->json(['message' => 'Commande supprimée'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Error suppression commande', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->commandService->handleCommands();
        $output->writeln('Commandes expirées supprimées ✅');

        return Command::COMMAND_STATUS_DELIVERED;
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
