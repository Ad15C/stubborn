<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartPageTest extends WebTestCase
{
    private $client;
    private $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine')->getManager();
    }

    public function testCartPageDisplaysCorrectly(): void
    {
        // 1. Récupérer un utilisateur existant
        $user = $this->em->getRepository(User::class)->findOneBy([]);
        $this->client->loginUser($user);

        // 2. Récupérer un produit existant dans l'appli
        $productRepo = $this->em->getRepository(\App\Entity\Product::class);
        $product = $productRepo->findOneBy([]);

        // 3. Récupérer ou créer le panier de l'utilisateur
        $cart = $this->em->getRepository(Cart::class)->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->em->persist($cart);
            $this->em->flush();
        }

        // 4. Vérifier qu'au moins un item est présent dans le panier, sinon ajouter un CartItem
        if ($cart->getItems()->isEmpty()) {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity(1);
            $cartItem->setPrice($product->getPrice());
            $cartItem->setSize('M');

            $this->em->persist($cartItem);
            $cart->addItem($cartItem);
            $this->em->persist($cart);
            $this->em->flush();
        }

        // 5. Charger la page /cart
        $crawler = $this->client->request('GET', '/cart');

        // 6. Vérifier le titre et le h1
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mon panier');

        // 7. Vérifier la présence des colonnes
        $this->assertSelectorExists('.cart-item .item-image img');
        $this->assertSelectorExists('.cart-item .item-name');
        $this->assertSelectorExists('.cart-item .item-size');
        $this->assertSelectorExists('.cart-item .item-quantity');
        $this->assertSelectorExists('.cart-item .item-price');

        // 8. Vérifier les boutons
        $this->assertSelectorExists('.cart-item .item-remove a');
        $this->assertSelectorExists('.summary-buttons form button.btn-checkout');
        $this->assertSelectorExists('.summary-buttons a.btn-continue');

        // 9. Vérifier le total
        $total = 0;
        foreach ($cart->getItems() as $item) {
            $total += $item->getPrice() * $item->getQuantity();
        }
        $this->assertSelectorTextContains('.total-box', number_format($total, 2, ',', ' '));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
        $this->em = null;
    }
}
