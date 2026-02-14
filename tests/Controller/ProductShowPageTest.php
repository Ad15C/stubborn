<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductShowPageTest extends WebTestCase
{
    public function testProductShowPage()
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // --- Créer un utilisateur pour le test (connecté) ---
        $userRepo = $entityManager->getRepository(User::class);
        $user = $userRepo->findOneBy([]);
        if (!$user) {
            $user = new User();
            $user->setEmail('testuser@example.com');
            $user->setPassword(password_hash('password', PASSWORD_BCRYPT));
            $entityManager->persist($user);
            $entityManager->flush();
        }
        $client->loginUser($user);

        // --- Prendre un produit existant ---
        $productRepo = $entityManager->getRepository(Product::class);
        $product = $productRepo->findOneBy([]);
        $this->assertNotNull($product, 'Un produit doit exister dans la base.');

        // --- Charger la page de détails ---
        $crawler = $client->request('GET', '/product/'.$product->getId());
        $this->assertResponseIsSuccessful();

        // --- Vérifier que le nom et le prix du produit sont affichés ---
        $this->assertSelectorTextContains('.column-left p:nth-of-type(1)', $product->getName());
        $this->assertSelectorTextContains('.column-left p:nth-of-type(2)', (string)$product->getPrice());


        // --- Vérifier que l'image existe ---
        $this->assertSelectorExists('img[alt="'.$product->getName().'"]');

        // --- Vérifier les tailles XS → XL ---
        foreach (['XS','S','M','L','XL'] as $size) {
            $this->assertSelectorExists('input[name="size"][value="'.$size.'"]');
        }

        // --- Vérifier le bouton Ajouter au panier (opérationnel) ---
        $this->assertSelectorExists('button.btn-add');

        // --- Vérifier lien retour vers la liste des produits ---
        $this->assertSelectorExists('a[href="/products"]');

        // --- Footer visible ---
        $this->assertSelectorExists('footer');

        // --- Nav adaptée à l’utilisateur connecté ---
        $this->assertSelectorExists('nav');
        $this->assertSelectorNotExists('nav a[href="/app_login"]');   // pas de "Se connecter"
        $this->assertSelectorNotExists('nav a[href="/app_register"]'); // pas de "S’inscrire"
    }
}
