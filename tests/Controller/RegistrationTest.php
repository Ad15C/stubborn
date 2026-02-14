<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationTest extends WebTestCase
{
    private ?\Symfony\Bundle\FrameworkBundle\KernelBrowser $client = null;
    private ?EntityManagerInterface $entityManager = null;
    private ?UserPasswordHasherInterface $passwordHasher = null;


    protected function setUp(): void
    {
        // 1. Crée le client
        $this->client = static::createClient();

        // 2. Récupère le container après la création du client
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // 3. Crée le schéma SQLite en mémoire
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        if (!empty($metadata)) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }
    }


    public function testRegisterPageLoads(): void
    {
        $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', "S'inscrire");
    }

    public function testUserCanRegister(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $form = $crawler->selectButton('Créer un compte')->form([
            'registration_form[name]' => 'Test User',
            'registration_form[email]' => 'test@test.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
            'registration_form[deliveryAddress]' => '1 Rue de Test',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'test@test.com']);

        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->getName());
        $this->assertContains('ROLE_USER', $user->getRoles());

        // Forcer la vérification pour tests
        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $this->entityManager->flush();

        $this->assertTrue($user->isVerified());
    }

    public function testUserCannotRegisterWithExistingEmail(): void
    {
        $existingUser = new User();
        $existingUser->setName('Existing');
        $existingUser->setEmail('existing@test.com');
        $existingUser->setPassword(
            $this->passwordHasher->hashPassword($existingUser, 'password123')
        );
        $existingUser->setRoles(['ROLE_USER']);
        $existingUser->setIsVerified(true);

        $this->entityManager->persist($existingUser);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', '/register');

        $form = $crawler->selectButton('Créer un compte')->form([
            'registration_form[name]' => 'New User',
            'registration_form[email]' => 'existing@test.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorExists('.flash.error');
    }

    protected function tearDown(): void
    {
        if ($this->entityManager) {
            $this->entityManager->close();
        }

        $this->client = null;
        $this->entityManager = null;
        $this->passwordHasher = null;

        parent::tearDown();
    }
}
