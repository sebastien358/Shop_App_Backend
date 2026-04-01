<?php

namespace App\Services;

class CartService
{
    public function getCartData($request, $items, $serializer): array
    {
        if ($items) {
            $dataItems = $serializer->normalize($items, 'json', ['groups' => ['cart', 'cart-items', 'products', 'pictures'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);

            $baseUrl = $request->getSchemeAndHttpHost() . '/images/' ;
            foreach ($dataItems as &$item) {
                if (isset($item['product']['pictures'])) {
                    foreach ($item['product']['pictures'] as &$picture) {
                        if (isset($picture['filename'])) {
                            $picture['filename'] = $baseUrl . $picture['filename'];
                        }
                    }
                }
            }

            return $dataItems;

        } else {
            $dataItem = $serializer->normalize($items, 'json', ['groups' => ['cart', 'cart-items', 'products', 'pictures'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);

            return $dataItem;
        }
    }
}
