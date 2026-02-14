<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductListPageTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $userRepo = $entityManager->getRepository(User::class);

        // Créer un utilisateur complet si nécessaire
        if (!$userRepo->findOneBy([])) {
            $user = new User();
            $user->setEmail('testuser@example.com');
            $user->setName('Test User'); // obligatoire
            $user->setPassword('dummy'); // loginUser ne vérifie pas le mot de passe
            $user->setRoles(['ROLE_USER']);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        // Récupérer l'utilisateur créé
        $user = $userRepo->findOneBy([]);
        $this->client->loginUser($user);
    }

    public function testProductListPage(): void
    {
        $crawler = $this->client->request('GET', '/products');

        // La page se charge correctement
        $this->assertResponseIsSuccessful();

        // Titre principal
        $this->assertSelectorTextContains('h1', 'Nos produits');

        // Vérification des filtres
        $this->assertSelectorExists('.filter-box');
        $this->assertSelectorExists('.filter-box a[href*="min=10&max=29"]');
        $this->assertSelectorExists('.filter-box a[href*="min=30&max=35"]');
        $this->assertSelectorExists('.filter-box a[href*="min=35&max=50"]');

        // Vérification d’au moins un produit
        $this->assertSelectorExists('.product-card');

        // Nom, prix et image des produits
        $this->assertSelectorExists('.product-card h3');
        $this->assertSelectorExists('.product-card p');
        $this->assertSelectorExists('.product-card img');

        // Bouton "Voir" pour accéder à la page de détails
        $this->assertSelectorExists('.product-card a.btn-view');

        // Lien retour vers l'accueil
        $this->assertSelectorExists('a[href="/"]');

        // Footer présent
        $this->assertSelectorExists('footer');

        // Nav adaptée à l’utilisateur connecté
        $this->assertSelectorExists('nav a[href="/products"]'); // Accès à la boutique
        $this->assertSelectorNotExists('nav a[href="/app_login"]'); // pas de lien "Se connecter"
        $this->assertSelectorNotExists('nav a[href="/app_register"]'); // pas de lien "S’inscrire"
    }
}
