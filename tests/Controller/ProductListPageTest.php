<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class ProductListPageTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        // Nettoyer la table products
        $this->em->createQuery('DELETE FROM App\Entity\Product')->execute();

        // Ajouter produits de test
        $product1 = (new Product())
            ->setName('Produit Test 1')
            ->setPrice(20)
            ->setImage('images/products/test1.jpeg');

        $product2 = (new Product())
            ->setName('Produit Test 2')
            ->setPrice(35)
            ->setImage('images/products/test2.jpeg');

        $this->em->persist($product1);
        $this->em->persist($product2);
        $this->em->flush();
    }

    public function testProductListPage(): void
    {
        $crawler = $this->client->request('GET', '/products');

        // 1️⃣ Vérifier que le filtre est visible
        $this->assertSelectorExists('.filter-box');

        // 2️⃣ Vérifier que les produits sont affichés
        $this->assertCount(2, $crawler->filter('.product-card'));
        $this->assertStringContainsString('Produit Test 1', $crawler->filter('.product-card h3')->eq(0)->text());
        $this->assertStringContainsString('Produit Test 2', $crawler->filter('.product-card h3')->eq(1)->text());

        // 3️⃣ Vérifier que le clic sur "Voir" renvoie une réponse OK
        $link = $crawler->filter('.product-card a.btn-view')->eq(0)->link();
        $this->client->click($link);
        $this->assertResponseIsSuccessful();

        // 4️⃣ Vérifier le lien "Retour à l'accueil" sur la page liste
        $crawler = $this->client->request('GET', '/products');
        $homeLink = $crawler->filter('p a')->link();
        $this->client->click($homeLink);
        $this->assertResponseIsSuccessful();
    }

    public function testFilterByPrice(): void
    {
        $crawler = $this->client->request('GET', '/products?min=10&max=29');
        $this->assertCount(1, $crawler->filter('.product-card'));
        $this->assertStringContainsString('Produit Test 1', $crawler->filter('.product-card h3')->text());
    }

    public function testNoProductsInRange(): void
    {
        // Supprimer tous les produits
        $this->em->createQuery('DELETE FROM App\Entity\Product')->execute();

        $crawler = $this->client->request('GET', '/products?min=100&max=200');

        // Vérifier que le message "Aucun produit disponible" apparaît
        $this->assertStringContainsString(
            'Aucun produit disponible pour le moment.',
            $crawler->filter('.products-grid')->text()
        );
    }
}
