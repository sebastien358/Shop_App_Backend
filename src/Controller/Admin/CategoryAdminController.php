<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class CategoryAdminController extends AbstractController
{
    private $entityManager;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/list', methods: ['GET'])]
    public function list(SerializerInterface $serializer): JsonResponse
    {
        try {
            $categories = $this->entityManager->getRepository(Category::class)->findAll();
            if (empty($categories)) {
                return new JsonResponse(['success' => false, 'message' => 'No categories found'], Response::HTTP_NOT_FOUND);
            }
            $dataCategories = $serializer->normalize($categories, 'json', ['groups' => ['categories']]);
            return new JsonResponse($dataCategories, Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('error recovery categories', ['error' => $e->getMessage()]);
            return new JsonResponse(['error', $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
