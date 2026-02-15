<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\SecurityBundle\Security;


class CartPageTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private User $testUser;
    private Product $testProduct;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // créer schéma SQLite en mémoire
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        if (!empty($metadata)) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        // créer utilisateur test
        $this->testUser = new User();
        $this->testUser->setName('Test User');
        $this->testUser->setEmail('test@test.com');
        $this->testUser->setPassword(
            $this->passwordHasher->hashPassword($this->testUser, 'password123')
        );
        $this->testUser->setRoles(['ROLE_USER']);
        $this->testUser->setIsVerified(true);

        $this->entityManager->persist($this->testUser);

        // créer produit test
        $this->testProduct = new Product();
        $this->testProduct->setName('Produit Test');
        $this->testProduct->setPrice(10.00);
        $this->testProduct->setImage('test.jpg');

        $this->entityManager->persist($this->testProduct);

        $this->entityManager->flush();
    }

    public function testCartPageLoads(): void
    {
        $this->client->loginUser($this->testUser);

        $this->client->request('GET', '/cart');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h1', 'Mon panier');
    }

    public function testCartIsEmpty(): void
    {
        $this->client->loginUser($this->testUser);

        $this->client->request('GET', '/cart');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('body', 'Votre panier est vide');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
    }
}
