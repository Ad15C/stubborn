<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\Tools\SchemaTool;

class AdminUserTest extends WebTestCase
{
    /**
     * Réinitialise la base de données avant chaque test
     */
    private function resetDatabase(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();

        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        if (!empty($metadata)) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }
    }

    public function testAdminCanCreateUser(): void
    {
        $client = static::createClient();

        // Réinitialiser la base
        $this->resetDatabase();

        $em = static::getContainer()->get('doctrine')->getManager();
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Créer un utilisateur admin
        $admin = new User();
        $admin->setName('Admin Test');
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'adminpass'));
        $admin->setIsVerified(true);

        $em->persist($admin);
        $em->flush();

        // Se connecter avec l’admin
        $client->loginUser($admin);

        // Accéder à la page de création d’utilisateur
        $crawler = $client->request('GET', '/admin/create-user');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="admin_user_form"]');

        // Soumettre le formulaire pour créer un nouvel utilisateur
        $form = $crawler->selectButton('Créer')->form([
            'admin_user_form[name]' => 'John Doe',
            'admin_user_form[email]' => 'john@example.com',
            'admin_user_form[plainPassword][first]' => 'password123',
            'admin_user_form[plainPassword][second]' => 'password123',
            'admin_user_form[roles]' => ['ROLE_USER'],
        ]);

        $client->submit($form);

        // Vérifier la redirection et le flash message
        $this->assertResponseRedirects('/admin/create-user');
        $client->followRedirect();
        $this->assertSelectorExists('.flash-success');
        $this->assertSelectorTextContains('.flash-success', 'Utilisateur créé !');

        // Vérifier que l’utilisateur a bien été créé en base
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'john@example.com']);
        $this->assertNotNull($user);
        $this->assertSame('John Doe', $user->getName());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testNonAdminCannotAccessCreateUser(): void
    {
        $client = static::createClient();

        // Réinitialiser la base
        $this->resetDatabase();

        $em = static::getContainer()->get('doctrine')->getManager();
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Créer un utilisateur normal
        $user = new User();
        $user->setName('Regular User');
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, 'userpass'));
        $user->setIsVerified(true);

        $em->persist($user);
        $em->flush();

        // Se connecter avec l’utilisateur non-admin
        $client->loginUser($user);

        // Tenter d’accéder à la page admin
        $client->request('GET', '/admin/create-user');

        // Vérifier qu’on reçoit une 403 Forbidden
        $this->assertResponseStatusCodeSame(403);
    }
}
