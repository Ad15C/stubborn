<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationTest extends WebTestCase
{
    private $client;
    private $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->em = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();

        if ($metadata) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->em) {
            $this->em->close();
            $this->em = null;
        }

        $this->client = null;
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
            'registration_form[email]' => 'testuser@example.com',
            'registration_form[deliveryAddress]' => '8 Rue du Bac',
            'registration_form[plainPassword][first]' => 'Password123!',
            'registration_form[plainPassword][second]' => 'Password123!',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');

        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => 'testuser@example.com']);

        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->getName());
    }

    public function testUserCanLogin(): void
    {
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

        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'testuser@example.com',
            '_password' => 'Password123!',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/');

        $this->client->followRedirect();

        $this->assertSelectorExists('nav');
    }
}
