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

class CartRemoveTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;
    private User $testUser;
    private Product $testProduct;
    private Cart $cart;
    private CartItem $cartItem;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Réinitialiser la base
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        // Créer un utilisateur test
        $this->testUser = new User();
        $this->testUser
            ->setName('Test User')
            ->setEmail('test@test.com')
            ->setPassword(
                $this->passwordHasher->hashPassword($this->testUser, 'password123')
            )
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true);
        $this->em->persist($this->testUser);

        // Créer un produit test
        $this->testProduct = new Product();
        $this->testProduct
            ->setName('Produit Test')
            ->setPrice(10.00)
            ->setImage('test.jpg');
        $this->em->persist($this->testProduct);

        // Créer le panier
        $this->cart = new Cart();
        $this->cart->setUser($this->testUser);
        $this->em->persist($this->cart);

        // Créer le CartItem
        $this->cartItem = new CartItem();
        $this->cartItem
            ->setCart($this->cart)
            ->setProduct($this->testProduct)
            ->setPrice($this->testProduct->getPrice())
            ->setQuantity(1)
            ->setSize('M');
        $this->em->persist($this->cartItem);
        $this->cart->addItem($this->cartItem);

        // Flush final pour générer tous les ID
        $this->em->flush();
    }

    public function testRemoveProductFromCart(): void
    {
        // Connexion utilisateur
        $this->client->loginUser($this->testUser);

        // Vérifier que le CartItem existe avant suppression
        $cartItemFromDb = $this->em
            ->getRepository(CartItem::class)
            ->find($this->cartItem->getId());
        $this->assertNotNull($cartItemFromDb, 'Le CartItem doit exister avant suppression');

        // Appeler la route pour supprimer
        $this->client->request('GET', '/cart/remove/'.$cartItemFromDb->getId());

        // Vérifier la redirection
        $this->assertResponseRedirects('/cart');

        // Suivre la redirection
        $crawler = $this->client->followRedirect();

        // Recharger le panier depuis la DB
        $cart = $this->em->getRepository(Cart::class)->find($this->cart->getId());
        $items = $cart ? $cart->getItems() : [];
        $this->assertCount(0, $items, 'Le panier doit être vide après suppression');

        // Vérifier le message sur le DOM
        $this->assertSelectorTextContains('.cart-wrapper > p:first-of-type', 'Votre panier est vide');

        // Vérifier que le produit existe toujours (pas supprimé)
        $product = $this->em->getRepository(Product::class)->find($this->testProduct->getId());
        $this->assertNotNull($product, 'Le produit doit rester en base après suppression du CartItem');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
    }
}
