<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class HomePageTest extends WebTestCase
{
    private $client;
    private $em;
    private $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->em = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->passwordHasher = $this->client->getContainer()
            ->get(UserPasswordHasherInterface::class);

        // Réinitialiser la base
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        if ($metadata) {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        // Ajouter un produit mis en avant pour tester la page
        $product = new Product();
        $product->setName('Chaise Design');
        $product->setPrice(129.99);
        $product->setImage('images/products/chaise_design.jpeg');
        $product->setIsFeatured(true);
        $product->setFeaturedRank(1);

        $this->em->persist($product);
        $this->em->flush();
    }

    public function testHomePageForAnonymousUser(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        // Vérifier titre de bienvenue
        $this->assertSelectorTextContains('h1', 'Bienvenue chez Stubborn');

        // Vérifier nav
        $this->assertSelectorTextContains('nav', 'S’inscrire');
        $this->assertSelectorTextContains('nav', 'Se connecter');
        $this->assertSelectorTextContains('nav', 'Accueil');

        // Vérifier sections principales
        $this->assertSelectorExists('section.featured-products');
        $this->assertSelectorExists('section.company-info');
        $this->assertSelectorExists('footer');

        // Vérifier le produit mis en avant et le bouton "Voir"
        $products = $crawler->filter('section.featured-products .product-card');
        $this->assertCount(1, $products, 'Il doit y avoir exactement 1 produit mis en avant.');

        $productHtml = $products->eq(0)->html();
        $this->assertStringContainsString('Chaise Design', $productHtml);
        $this->assertStringContainsString('129.99', $productHtml);
        $this->assertStringContainsString('btn-view', $productHtml);
        $this->assertStringContainsString('/product/', $productHtml); // Vérifie que le lien vers product_show existe
    }

    public function testHomePageForLoggedInUser(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'Password123!')
        );

        $this->em->persist($user);
        $this->em->flush();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        // Vérifier nav
        $this->assertSelectorTextContains('nav', 'Accueil');
        $this->assertSelectorTextContains('nav', 'Boutique');
        $this->assertSelectorTextContains('nav', 'Panier');
        $this->assertSelectorTextContains('nav', 'Se déconnecter');

        // Vérifier sections principales
        $this->assertSelectorExists('section.featured-products');
        $this->assertSelectorExists('section.company-info');
        $this->assertSelectorExists('footer');

        // Vérifier le produit mis en avant et le bouton "Voir"
        $products = $crawler->filter('section.featured-products .product-card');
        $this->assertCount(1, $products, 'Il doit y avoir exactement 1 produit mis en avant.');

        $productHtml = $products->eq(0)->html();
        $this->assertStringContainsString('Chaise Design', $productHtml);
        $this->assertStringContainsString('129.99', $productHtml);
        $this->assertStringContainsString('btn-view', $productHtml);
        $this->assertStringContainsString('/product/', $productHtml); // Vérifie que le lien vers product_show existe
    }
}
