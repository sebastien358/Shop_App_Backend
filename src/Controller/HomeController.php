<?php

namespace App\Controller;

use App\Entity\Product;
use App\Services\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/product', methods: ['GET'])]
final class HomeController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ProductService $productService;

    public function __construct(EntityManagerInterface $entityManager, ProductService $productService)
    {
        $this->entityManager = $entityManager;
        $this->productService = $productService;
    }

    #[Route('/list', methods: ['GET'])]
    public function list(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $offset = (int) $request->query->get('offset', 0);
            $limit = (int) $request->query->get('limit', 20);

            $products = $this->entityManager->getRepository(Product::class)->findAllLoadProducts($offset, $limit);

            $dataProducts = $this->productService->getProductData($request, $products, $serializer);

            return $this->json($dataProducts, Response::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/search', methods: ['GET'])]
    public function search(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $search = $request->query->get('search');

            if (!$search || !is_string($search)) {
                return $this->json(['error' => 'Paramètre search obligatoire'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $search = trim((string) $search);

            $products = $this->entityManager->getRepository(Product::class)->findAllSearch($search);

            $dataProducts = $this->productService->getProductData($request, $products, $serializer);

            return $this->json($dataProducts, Response::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/filtered/price', methods: ['GET'])]
    public function price(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $minPrice = $request->query->get('minPrice');
            $maxPrice = $request->query->get('maxPrice');

            if (!is_numeric($minPrice) || !is_numeric($maxPrice)) {
                return $this->json(['error' => 'Paramètres minPrice et maxPrice obligatoires'], Response::HTTP_BAD_REQUEST);
            }

            $minPrice = (int) $minPrice;
            $maxPrice = (int) $maxPrice;

            $products = $this->entityManager->getRepository(Product::class)->findAllPrice($minPrice, $maxPrice);

            $dataProducts = $this->productService->getProductData($request, $products, $serializer);
            return $this->json($dataProducts, Response::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/filtered/category', methods: ['GET'])]
    public function category(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $category = $request->query->get('category');

            if (!$category || !is_string($category)) {
                return $this->json(['error' => 'Paramètre category obligatoire'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $category = trim((string) $category);

            $products = $this->entityManager->getRepository(Product::class)->findAllCategory($category);
            $dataProducts = $this->productService->getProductData($request, $products, $serializer);

            return $this->json($dataProducts, Response::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
