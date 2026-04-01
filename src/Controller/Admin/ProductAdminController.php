<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Repository\Form\ProductType;
use App\Services\FileUploader;
use App\Services\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class ProductAdminController extends AbstractController
{
    private $entityManager;
    private $fileUploader;
    private $productService;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager, FileUploader $fileUploader, ProductService $productService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->productService = $productService;
        $this->fileUploader = $fileUploader;
        $this->logger = $logger;
    }

    #[Route('/product/list', methods: ['GET'])]
    public function list(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 20);

            $page = (int) $page;
            $limit = (int) $limit;

            if (!number_format($page) || !number_format($limit)) {
                return $this->json(['error' => 'Donneés manquantes'], Response::HTTP_BAD_REQUEST);
            }

            $products = $this->entityManager->getRepository(Product::class)->findAllProductPerPageAdmin($page, $limit);
            if(!$products) {
                return $this->json(['error' => 'Pas de produit'], Response::HTTP_BAD_REQUEST);
            }

            $total = $this->entityManager->getRepository(Product::class)->findAllCountProducts();
            $dataProducts = $this->productService->getProductData($request, $products, $serializer);

            return $this->json([
                'total' => $total,
                'products' => $dataProducts,
                'pages' => ceil($total / $limit)
            ], Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('Error récupération des produits', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/product/current/{id}', methods: ['GET'])]
    public function current(int $id, Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $product = $this->entityManager->getRepository(Product::class)->find($id);

            if (!$product) {
                return $this->json(['error' => 'Produit introuvable'], Response::HTTP_BAD_REQUEST);
            }

            $dataProduct = $this->productService->getProductData($request, $product, $serializer);

            return $this->json($dataProduct, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur récupération d\'un produit', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/product/edit/{id}', methods: ['POST'])]
    public function edit(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable',], Response::HTTP_UNAUTHORIZED);
            }

            $product = $this->entityManager->getRepository(Product::class)->find($id);

            if (!$product) {
                return $this->json(['error' => 'Produit introuvable'], Response::HTTP_BAD_REQUEST);
            }

            $form = $this->createForm(ProductType::class, $product);

            $formData = $request->request->all();
            $form->submit($formData);

            if (!$form->isValid()) {
                $errors = $this->getErrorMessages($form);
                return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $category = $form->get('category')->getData();
            $product->setCategory($category);

            $this->productService->handleProductImages($request, $product);

            $this->entityManager->flush();

            return $this->json(['message' => 'Le produit a été modifié'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur modification d\'un produit', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/product/add', methods: ['POST'])]
    public function addProduct(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_FORBIDDEN);
            }

            $product = new Product();

            $form = $this->createForm(ProductType::class, $product);
            $formData = $request->request->all();
            $form->submit($formData);

            if (!$form->isValid()) {
                $errors = $this->getErrorMessages($form);
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $category = $form->get('category')->getData();
            $product->setCategory($category);

            $this->productService->handleProductImages($request, $product);

            $this->entityManager->persist($product);
            $this->entityManager->flush();

            return $this->json(['message' => 'Produit ajouté avec succès'], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de l\'ajout d\'un produit', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->json(['error' => 'Impossible d’ajouter le produit', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/product/delete/{id}', methods: ['DELETE'])]
    public function deleteProduct(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_FORBIDDEN);
            }

            $product = $this->entityManager->getRepository(Product::class)->find($id);
            if (empty($product)) {
                return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
            }

            $images = $product->getPictures();

            if ($images && !$images->isEmpty()) {
                $this->fileUploader->removeProductImage($images);
            }

            $this->entityManager->remove($product);
            $this->entityManager->flush();

            return $this->json(['success delete product' => Response::HTTP_OK]);
        } catch (\Throwable $e) {
            $this->logger->error('error recovery products', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/product/delete/{productId}/image/{imageId}', methods: ['DELETE'])]
    public function deleteImage(int $productId, int $imageId): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $product = $this->entityManager->getRepository(Product::class)->find($productId);
            if (empty($product)) {
                return $this->json(['error' => 'Product not found'], Response::HTTP_BAD_REQUEST);
            }

            foreach ($product->getPictures() as $picture) {
                if ($picture->getId() !== $imageId) {
                    return $this->json(['error' => 'L\'image ne correspond pas au produit'], Response::HTTP_BAD_REQUEST);
                }
            }

            $images = $product->getPictures();

            if ($images && !$images->isEmpty()) {
                $this->fileUploader->removeProductImage($images);
            }

            $this->entityManager->flush();

            return $this->json(['success delete product' => Response::HTTP_OK]);
        } catch (\Throwable $e) {
            $this->logger->error('error recovery products', ['error' => $e->getMessage()]);
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
