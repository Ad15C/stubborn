<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Cart;
use App\Entity\CartItem;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartRemoveTest extends WebTestCase
{
    private $client;
    private $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine')->getManager();
    }

    public function testRemoveProductFromCart(): void
    {
        // 1. Récupérer un utilisateur existant et le connecter
        $user = $this->em->getRepository(User::class)->findOneBy([]);
        $this->client->loginUser($user);

        // 2. Récupérer le panier de l'utilisateur
        $cart = $this->em->getRepository(Cart::class)->findOneBy(['user' => $user]);

        if (!$cart) {
            // Créer un panier si nécessaire
            $cart = new Cart();
            $cart->setUser($user);
            $this->em->persist($cart);
            $this->em->flush();
        }

        // 3. Ajouter un produit existant au panier si nécessaire
        $cartItem = $cart->getItems()->first();
        if (!$cartItem) {
            $product = $this->em->getRepository(\App\Entity\Product::class)->findOneBy([]);
            $cartItem = new CartItem();
            $cartItem->setCart($cart)
                     ->setProduct($product)
                     ->setPrice($product->getPrice())
                     ->setQuantity(1)
                     ->setSize('M');
            $this->em->persist($cartItem);
            $cart->addItem($cartItem);
            $this->em->persist($cart);
            $this->em->flush();
        }

        $cartItemId = $cartItem->getId();

        // 4. Appeler la route de suppression
        $this->client->request('GET', '/cart/remove/' . $cartItemId);

        // 5. Vérifier que le CartItem n’existe plus en base
        $removedItem = $this->em->getRepository(CartItem::class)->find($cartItemId);
        $this->assertNull($removedItem, 'Le produit doit être supprimé du panier.');

        // 6. Vérifier que le panier est mis à jour
        $this->em->refresh($cart);
        $this->assertFalse($cart->getItems()->contains($cartItem), 'Le panier ne doit plus contenir le produit supprimé.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
        $this->em = null;
    }
}
