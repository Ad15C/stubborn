<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private User $testUser;

    protected function setUp(): void
    {
        // Créer le client
        $this->client = static::createClient();
        $container = static::getContainer();

        // Récupérer EntityManager et PasswordHasher
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Créer le schéma SQLite en mémoire pour les tests
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        // Créer un utilisateur test
        $this->testUser = new User();
        $this->testUser->setName('Test User');
        $this->testUser->setEmail('test@test.com');
        $this->testUser->setPassword(
            $this->passwordHasher->hashPassword($this->testUser, 'password123')
        );
        $this->testUser->setRoles(['ROLE_USER']);
        $this->testUser->setIsVerified(true); // très important pour le test de login

        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    public function testLoginPageLoads(): void
    {
        $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Se Connecter');
    }

    public function testUserCanLogin(): void
    {
        // Aller sur la page login
        $crawler = $this->client->request('GET', '/login');

        // Soumettre le formulaire avec les bons identifiants
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'test@test.com',
            '_password' => 'password123',
        ]);
        $this->client->submit($form);

        // Vérifier la redirection vers la home
        $this->assertResponseRedirects('/');

        // Suivre la redirection et vérifier la page
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Optionnel : vérifier que le nom de l’utilisateur s’affiche sur la page
        // $this->assertSelectorTextContains('.user-name', 'Test User');
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'test@test.com',
            '_password' => 'wrongpassword',
        ]);
        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorExists('.field-error');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
    }
}
