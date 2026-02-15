<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Product;
use App\Entity\Cart;
use App\Entity\CartItem;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CartAddTest extends WebTestCase
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

        // Créer le schéma en mémoire
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
        $this->testUser->setIsVerified(true);

        $this->entityManager->persist($this->testUser);

        // Créer un produit test
        $this->testProduct = new Product();
        $this->testProduct->setName('Produit Test');
        $this->testProduct->setPrice(10.00);
        $this->testProduct->setImage('test.jpg');

        $this->entityManager->persist($this->testProduct);

        $this->entityManager->flush();
    }

    public function testAddProductToCart(): void
    {
        // Connecter l'utilisateur
        $this->client->loginUser($this->testUser);

        // POST pour ajouter le produit
        $this->client->request(
            'POST',
            '/cart/add/' . $this->testProduct->getId(),
            ['size' => 'M'] // param obligatoire pour ton controller
        );

        // Vérifier redirection vers le panier
        $this->assertResponseRedirects('/cart');

        $this->client->followRedirect();

        // Vérifier que la page panier affiche le produit
        $this->assertSelectorTextContains('body', 'Produit Test');

        // Vérifier en base que l'article a bien été ajouté
        $cart = $this->entityManager
            ->getRepository(Cart::class)
            ->findOneBy(['user' => $this->testUser]);

        $this->assertNotNull($cart, 'Le panier doit exister après ajout');

        $cartItem = $this->entityManager
            ->getRepository(CartItem::class)
            ->findOneBy([
                'cart' => $cart,
                'product' => $this->testProduct
            ]);

        $this->assertNotNull($cartItem, 'L’article doit être présent dans le panier');
        $this->assertEquals(1, $cartItem->getQuantity());
        $this->assertEquals('M', $cartItem->getSize());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
    }
}
