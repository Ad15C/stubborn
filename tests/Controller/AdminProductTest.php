<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AdminProductTest extends WebTestCase
{
    public function testAdminFullProductManagement(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();

        // --- Créer un admin si nécessaire ---
        $adminEmail = 'admin@example.com';
        $admin = $em->getRepository(User::class)->findOneBy(['email' => $adminEmail]);
        if (!$admin) {
            $admin = new User();
            $admin->setName('Admin');
            $admin->setEmail($adminEmail);
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setPassword(password_hash('password', PASSWORD_BCRYPT));
            $em->persist($admin);
            $em->flush();
        }

        // --- Se connecter en admin ---
        $client->loginUser($admin);

        // --- Accéder au dashboard ---
        $client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Dashboard Admin');

        // --- Préparer un produit unique ---
        $productName = 'Test Product ' . uniqid();

        // --- Préparer un fichier image simulé pour l'ajout ---
        $imagePath1 = sys_get_temp_dir() . '/test_product_image1.jpg';
        copy(__DIR__ . '/fixtures/default.jpeg', $imagePath1);
        $uploadedFile1 = new UploadedFile(
            $imagePath1,
            'test_product_image1.jpg',
            'image/jpeg',
            null,
            true
        );

        // --- Ajouter un produit ---
        $client->request('POST', '/admin/product/add', [
            'name' => $productName,
            'price' => 79.99,
            'stock_xs' => 2,
            'stock_s' => 4,
            'stock_m' => 6,
            'stock_l' => 8,
            'stock_xl' => 10,
            'is_featured' => 1,
        ], [
            'image' => $uploadedFile1
        ]);

        $this->assertResponseRedirects('/admin');
        $client->followRedirect();

        // --- Vérifier que le produit est ajouté ---
        $product = $em->getRepository(Product::class)->findOneBy(['name' => $productName]);
        $this->assertNotNull($product, "Produit ajouté");
        $this->assertEquals(79.99, $product->getPrice(), "Prix après ajout");
        $this->assertTrue($product->getIsFeatured(), "Produit mis en avant");
        $this->assertNotNull($product->getImage(), "Image ajoutée");
        $originalImage = $product->getImage();

        // --- Préparer un fichier image simulé pour la modification ---
        $imagePath2 = sys_get_temp_dir() . '/test_product_image2.jpg';
        copy(__DIR__ . '/fixtures/default2.jpeg', $imagePath2);
        $uploadedFile2 = new UploadedFile(
            $imagePath2,
            'test_product_image2.jpg',
            'image/jpeg',
            null,
            true
        );

        // --- Modifier le produit ---
        $client->request('POST', '/admin/product/' . $product->getId() . '/update', [
            'price' => 99.99,
            'stock_xs' => 1,
            'stock_s' => 2,
            'stock_m' => 3,
            'stock_l' => 4,
            'stock_xl' => 5,
            'is_featured' => '', // décoché
        ], [
            'image' => $uploadedFile2
        ]);

        $this->assertResponseRedirects('/admin');
        $client->followRedirect();

        // --- Rafraîchir l'entité depuis la DB ---
        $em->refresh($product);
        $this->assertEquals(99.99, $product->getPrice(), "Prix après modification");
        $this->assertFalse($product->getIsFeatured(), "Produit décoché");
        $this->assertEquals(1, $product->getStockXS());
        $this->assertEquals(5, $product->getStockXL());
        $this->assertNotEquals($originalImage, $product->getImage(), "Image modifiée");

        // --- Supprimer le produit ---
        $client->request('POST', '/admin/product/' . $product->getId() . '/delete');
        $this->assertResponseRedirects('/admin');
        $client->followRedirect();

        // --- Forcer Doctrine à vider sa mémoire ---
        $em->clear();
        $deletedProduct = $em->getRepository(Product::class)->find($product->getId());
        $this->assertNull($deletedProduct, "Produit supprimé");

    }
}
