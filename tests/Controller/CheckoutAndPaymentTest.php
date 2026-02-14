<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class CheckoutAndPaymentTest extends WebTestCase
{
    private $client;
    private ?EntityManagerInterface $em = null;
    private ?Order $createdOrder = null;

    protected function setUp(): void
    {
        $this->client = static::createClient([], [
            'environment' => 'test', // active le bypass Stripe
        ]);

        $this->em = $this->client->getContainer()->get(EntityManagerInterface::class);
    }

    private function addOneProductToCart(User $user, int $productId, string $size = 'M', int $quantity = 1): Cart
    {
        $productRepo = $this->client->getContainer()->get(ProductRepository::class);
        $product = $productRepo->find($productId);
        if (!$product) {
            throw new \Exception("Produit avec ID $productId introuvable en base");
        }

        $cart = $this->em->getRepository(Cart::class)->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->em->persist($cart);
        }

        $cartItem = new CartItem();
        $cartItem->setCart($cart)
                 ->setProduct($product)
                 ->setPrice($product->getPrice())
                 ->setQuantity($quantity)
                 ->setSize($size);

        $this->em->persist($cartItem);
        $cart->addItem($cartItem);
        $this->em->flush();

        return $cart;
    }

    public function testCheckoutAndPaymentFlow(): void
    {
        // 1. Récupérer un utilisateur existant et se connecter
        $userRepo = $this->client->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy([]);
        $this->client->loginUser($user);

        // 2. Ajouter un produit au panier
        $cart = $this->addOneProductToCart($user, 1, 'M', 2); // ID produit existant

        // 3. Vérifier que le panier n'est pas vide
        $crawler = $this->client->request('GET', '/cart');
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('.cart-item')->count(), 'Le panier doit contenir au moins un produit');

        // 4. Envoyer POST /checkout
        $this->client->request('POST', '/checkout');

        // 5. Vérifier redirection vers /success/{id}
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $redirectUrl = $this->client->getResponse()->headers->get('Location');
        preg_match('#/success/(\d+)#', $redirectUrl, $matches);
        $orderId = $matches[1] ?? null;
        $this->assertNotNull($orderId, 'L’URL de redirection doit contenir l’ID de la commande');

        // 6. Vérifier que la commande est créée et appartient à l’utilisateur
        $order = $this->em->getRepository(Order::class)->find($orderId);
        $this->assertNotNull($order, 'La commande doit exister en base');
        $this->assertEquals($user->getId(), $order->getUser()->getId(), 'La commande doit appartenir à l’utilisateur');
        $this->createdOrder = $order;

        // 7. Suivre la redirection vers /success/{id}
        $crawler = $this->client->followRedirect();

        // 8. Vérifier que le panier est vidé
        $cart = $this->em->getRepository(Cart::class)->findOneBy(['user' => $user]);
        $this->assertEquals(0, $this->em->getRepository(CartItem::class)->count(['cart' => $cart]), 'Le panier doit être vidé après checkout');

        // 9. Vérifier que le statut est PAID
        $this->em->refresh($order);
        $this->assertEquals(Order::STATUS_PAID, $order->getStatus(), 'Le statut de la commande doit être PAID');

        // 10. Vérifier que la page affiche le numéro de commande et le total
        $bodyText = $crawler->filter('body')->text();
        $this->assertStringContainsString((string)$order->getId(), $bodyText, 'La page doit afficher le numéro de commande');
        $this->assertStringContainsString((string)$order->getTotal(), $bodyText, 'La page doit afficher le total de la commande');
    }

    protected function tearDown(): void
    {
        // Supprimer seulement la commande créée pour laisser les produits intacts
        if ($this->createdOrder) {
            $this->em->remove($this->createdOrder);
            $this->em->flush();
        }

        parent::tearDown();
        $this->em->close();
        $this->em = null;
    }
}
