<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [];
        $categoryNames = ['streaming', 'gamer', 'desktop'];
        foreach ($categoryNames as $categoryName) {
            $category = new Category();
            $category->setName($categoryName);
            $manager->persist($category);
            $categories[] = $category;
        }

        for ($i = 0; $i < 100; $i++) {
            $product = new Product();
            $product->setTitle('Ordinateur ' . $i);
            $product->setDescription('Un ordinateur puissant');
            $product->setPrice(mt_rand(500, 4000));
            $product->setCategory($categories[mt_rand(0, count($categories) - 1)]);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
