<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Product;
use App\Repository\CartRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class CheckoutAndPaymentTest extends WebTestCase
{
    private $client;
    private $em;
    private $cartRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->cartRepository = self::getContainer()->get(CartRepository::class);
    }

    public function testCheckoutAndPaymentFlow(): void
    {
        // 1. Créer un utilisateur test
        $user = new User();
        $user->setEmail('test_' . uniqid() . '@example.com') // email unique
             ->setName('Test User')
             ->setPassword('password123');
        $this->em->persist($user);

        // 2. Créer un produit test
        $product = new Product();
        $product->setName('Produit Test')
                ->setPrice(25.50)
                ->setStockM(10);
        $this->em->persist($product);
        $this->em->flush();

        // 3. Simuler login
        $this->client->loginUser($user);

        // 4. Ajouter produit au panier
        $this->client->request('POST', '/cart/add/' . $product->getId(), [
            'size' => 'M'
        ]);
        $this->assertResponseRedirects('/cart');

        // 5. Vérifier que le panier contient l'article
        $cart = $this->cartRepository->findOneBy(['user' => $user]);
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart->getItems());
        $item = $cart->getItems()->first();
        $this->assertEquals($product->getId(), $item->getProduct()->getId());
        $this->assertEquals('M', $item->getSize());

        // 6. Checkout
        $this->client->request('POST', '/checkout');
        $this->assertResponseRedirects();

        // 7. Suivre la redirection vers /success/{id}
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Merci pour votre commande', $this->client->getResponse()->getContent());

        // 8. Vérifier que la commande a été créée et que son statut est PAID
        $orderRepo = self::getContainer()->get('doctrine')->getRepository('App\Entity\Order');
        $orders = $orderRepo->findBy(['user' => $user]);
        $this->assertCount(1, $orders);
        $order = $orders[0];
        $this->assertEquals('paid', $order->getStatus());
        $this->assertCount(1, $order->getItems());
        $orderItem = $order->getItems()->first();
        $this->assertEquals($product->getName(), $orderItem->getProductName());
        $this->assertEquals('M', $orderItem->getSize());

        // 9. Vérifier que le panier est vidé après paiement
        $cart = $this->cartRepository->findOneBy(['user' => $user]); // recharger
        $this->assertCount(0, $cart->getItems());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
        $this->em = null;
        $this->cartRepository = null;
    }
}
