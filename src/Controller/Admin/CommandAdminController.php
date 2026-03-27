<?php

namespace App\Controller\Admin;

use App\Entity\Command;
use App\Services\CommandService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin/command')]
#[IsGranted('ROLE_ADMIN')]
class CommandAdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CommandService $commandService;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager, CommandService $commandService, LoggerInterface $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->commandService = $commandService;
        $this->logger = $logger;
    }

    #[Route('/list', methods: ['GET'])]
    public function list(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_FORBIDDEN);
            }

            $commands = $this->entityManager->getRepository(Command::class)->findAllPaidCommands();

            $dataCommands = $this->commandService->getCommandData($request, $commands, $serializer);

            return $this->json($dataCommands, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des commandes : ', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/preparation/{preparationStatus}', methods: ['GET'])]
    public function preparation(int $id, string $preparationStatus): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_FORBIDDEN);
            }

            $command = $this->entityManager->getRepository(Command::class)->find($id);
            if (!$command) {
                return $this->json(['error' => 'Command introuvable'], Response::HTTP_BAD_REQUEST);
            }

            $command->setPreparationStatus($preparationStatus);

            $this->entityManager->flush();

            return $this->json(['message' => 'Le status de la préparation de la commande a été modifié'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des commandes : ', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_FORBIDDEN);
            }

            $command = $this->entityManager->getRepository(Command::class)->find($id);

            if (!$command) {
                return $this->json(['error' => 'Command introuvable'], Response::HTTP_BAD_REQUEST);
            }

            if ($command->getPreparationStatus() !== Command::COMMAND_STATUS_DELIVERED) {
                return $this->json(['error' => 'Impossible de supprimer une commande non prête'], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->remove($command);
            $this->entityManager->flush();

            return $this->json(['message' => 'La commande a été modifiée'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la suppression de la commande : ', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
