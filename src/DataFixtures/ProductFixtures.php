<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Définir les produits avec leurs infos
        $productsData = [
            ['name' => 'Blackbelt',  'price' => 29.90, 'isFeatured' => true, 'image' => 'Blackbelt.jpeg'],
            ['name' => 'BlueBelt',   'price' => 29.90, 'isFeatured' => false, 'image' => 'BlueBelt.jpeg'],
            ['name' => 'Street',     'price' => 34.50, 'isFeatured' => false, 'image' => 'Street.jpeg'],
            ['name' => 'Pokeball',   'price' => 45.00, 'isFeatured' => true, 'image' => 'Pokeball.jpeg'],
            ['name' => 'PinkLady',   'price' => 29.90, 'isFeatured' => false, 'image' => 'PinkLady.jpeg'],
            ['name' => 'Snow',       'price' => 32.00, 'isFeatured' => false, 'image' => 'Snow.jpeg'],
            ['name' => 'Greyback',   'price' => 28.50, 'isFeatured' => false, 'image' => 'Greyback.jpeg'], 
            ['name' => 'BlueCloud',  'price' => 45.00, 'isFeatured' => false, 'image' => 'BlueCloud.jpeg'],
            ['name' => 'BornInUsa',  'price' => 59.90, 'isFeatured' => true, 'image' => 'BornInUsa.jpeg'],
            ['name' => 'GreenSchool','price' => 42.20, 'isFeatured' => false, 'image' => 'GreenSchool.jpeg'],
        ];

        foreach ($productsData as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setPrice($data['price']);
            $product->setIsFeatured($data['isFeatured']);
            
            // Stock par taille
            $product->setStockXs(2);
            $product->setStockS(2);
            $product->setStockM(2);
            $product->setStockL(2);
            $product->setStockXl(2);

            // Image
            $product->setImage('images/products/' . $data['image']);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
