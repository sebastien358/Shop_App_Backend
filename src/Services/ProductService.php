<?php

namespace App\Services;

use App\Entity\Picture;
use Doctrine\ORM\EntityManagerInterface;

class ProductService
{
    private $fileUploader;
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager, FileUploader $fileUploader)
    {
        $this->fileUploader = $fileUploader;
        $this->entityManager = $entityManager;
    }

    public function getProductData($request, $products, $serializer)
    {
        if (is_array($products)) {
            $dataProducts = $serializer->normalize($products, 'json', ['groups' => ['products', 'pictures'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);

            $urlImage = $request->getSchemeAndHttpHost() . '/images/';

            foreach ($dataProducts as &$product) {
                if (!empty($product['pictures']) && is_array($product['pictures'])) {
                    foreach ($product['pictures'] as &$picture) {
                        if (isset($picture['filename'])) {
                            $picture['filename'] = $urlImage . $picture['filename'];
                        }
                    }
                }
            }

            return $dataProducts;

        } else {
            $dataProduct = $serializer->normalize($products, 'json', ['groups' => ['products', 'categories', 'pictures'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);

            $urlImage = $request->getSchemeAndHttpHost() . '/images/';

            foreach ($dataProduct['pictures'] as &$product) {
                if (isset($product['filename'])) {
                    $product['filename'] = $urlImage . $product['filename'];
                }
            }

            return $dataProduct;
        }
    }

    public function handleProductImages($request, $product)
    {
        $images = $request->files->get('images');

        if (!$images) {
            return; // rien à faire
        }

        foreach ($images as $image) {
            if ($image->getSize() > 10 * 1024 * 1024) {
                throw new \Exception('Image trop volumineuse : ' . $image->getClientOriginalName());
            }

            $filename = $this->fileUploader->upload($image);

            $picture = new Picture();
            $picture->setFilename($filename);
            $picture->setProduct($product);

            $this->entityManager->persist($picture);
        }
    }
}
