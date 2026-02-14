<?php

namespace App\Tests\User;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomePageTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private ?EntityManagerInterface $entityManager = null;
    private ?Product $testProduct = null;

    protected function setUp(): void
    {
        // Créer le client
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        // --- Créer le schéma en mémoire pour SQLite ---
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        if (!empty($metadata)) {
            $schemaTool->dropSchema($metadata); // optionnel pour repartir propre
            $schemaTool->createSchema($metadata);
        }

        // --- Créer un produit mis en avant ---
        $this->testProduct = new Product();
        $this->testProduct->setName('Chaussure Test');
        $this->testProduct->setPrice(49.99);
        $this->testProduct->setImage('images/products/default.jpeg');
        $this->testProduct->setIsFeatured(true);
        $this->testProduct->setFeaturedRank(1);

        $this->entityManager->persist($this->testProduct);
        $this->entityManager->flush();
    }

    public function testHomePageLoads(): void
    {
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
    }

    public function testFeaturedProductIsDisplayed(): void
    {
        $crawler = $this->client->request('GET', '/');
        $this->assertSelectorTextContains(
            '.featured-products .product-card h3',
            $this->testProduct->getName()
        );
    }

    public function testFeaturedProductLinkRedirectsToDetail(): void
    {
        // Accéder à la page d'accueil
        $crawler = $this->client->request('GET', '/');

        // Cliquer sur le lien du produit mis en avant
        $productLink = $crawler->filter('a[href="/product/'.$this->testProduct->getId().'"]')->link();
        $this->client->click($productLink);

        // Vérifier que la page se charge
        $this->assertResponseIsSuccessful();

        // Vérifier que le nom du produit s'affiche dans la page
        $this->assertSelectorTextContains('div.product-details p', $this->testProduct->getName());
    }


    public function testNavigationLinks(): void
{
    $crawler = $this->client->request('GET', '/');

    $router = $this->client->getContainer()->get('router');

    $routes = [
        'home',
        'app_register',
        'app_login',
    ];

    foreach ($routes as $routeName) {

        $url = $router->generate($routeName);

        $this->assertSelectorExists('a[href="'.$url.'"]');

        $link = $crawler->filter('a[href="'.$url.'"]')->link();
        $this->client->click($link);

        $this->assertResponseIsSuccessful();

        $crawler = $this->client->request('GET', '/');
    }
}

    protected function tearDown(): void
    {
        // Nettoyer le produit de test
        if ($this->entityManager && $this->testProduct && $this->entityManager->contains($this->testProduct)) {
            $this->entityManager->remove($this->testProduct);
            $this->entityManager->flush();
        }

        parent::tearDown();

        $this->client = null;
        $this->entityManager = null;
        $this->testProduct = null;
    }
}

