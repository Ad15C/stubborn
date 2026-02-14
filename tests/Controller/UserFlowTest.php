<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFlowTest extends WebTestCase
{
    private ?\Symfony\Bundle\FrameworkBundle\KernelBrowser $client = null;
    private ?EntityManagerInterface $em = null;
    private ?User $testUser = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->em = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        // Création du schéma en mémoire
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();

        if (!empty($metadata)) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        // Création d'un utilisateur commun pour tous les tests
        $passwordHasher = $this->client->getContainer()
            ->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setName('Test User');
        $user->setEmail('testuser@example.com');
        $user->setDeliveryAddress('8 Rue du Bac');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);
        $user->setPassword(
            $passwordHasher->hashPassword($user, 'Password123!')
        );

        $this->em->persist($user);
        $this->em->flush();

        $this->testUser = $user;
    }

    protected function tearDown(): void
    {
        if ($this->em) {
            $this->em->close();
            $this->em = null;
        }

        $this->client = null;
        $this->testUser = null;

        parent::tearDown();
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
            'registration_form[name]' => 'New User',
            'registration_form[email]' => 'newuser@example.com',
            'registration_form[deliveryAddress]' => '10 Rue de Test',
            'registration_form[plainPassword][first]' => 'Password123!',
            'registration_form[plainPassword][second]' => 'Password123!',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');

        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => 'newuser@example.com']);

        $this->assertNotNull($user);
        $this->assertEquals('New User', $user->getName());
    }

    public function testUserCanLogin(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => $this->testUser->getEmail(),
            '_password' => 'Password123!',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/');
        $this->client->followRedirect();
        $this->assertSelectorExists('nav');
    }

    public function testLoginFailsWithInvalidCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'nonexistent@example.com',
            '_password' => 'wrongpassword',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorExists('.field-error');
    }
}
