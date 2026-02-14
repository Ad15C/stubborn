<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartAddTest extends WebTestCase
{
    private $client;
    private $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine')->getManager();
    }

    public function testAddProductToCart(): void
    {
        // 1. Récupérer un utilisateur existant
        $user = $this->em->getRepository(User::class)->findOneBy([]);
        $this->client->loginUser($user);

        // 2. Récupérer un produit existant
        $productRepo = $this->em->getRepository(\App\Entity\Product::class);
        $product = $productRepo->findOneBy([]);

        // 3. Récupérer ou créer le panier
        $cart = $this->em->getRepository(Cart::class)->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->em->persist($cart);
            $this->em->flush();
        }

        // 4. Vérifier s’il existe déjà un CartItem pour ce produit et taille
        $size = 'M';
        $existingItem = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->getId() === $product->getId() && $item->getSize() === $size) {
                $existingItem = $item;
                break;
            }
        }

        $initialQuantity = $existingItem ? $existingItem->getQuantity() : 0;

        // 5. Ajouter le produit via la route /cart/add/{id} (POST)
        $this->client->request('POST', '/cart/add/' . $product->getId(), [
            'size' => $size
        ]);

        $this->em->refresh($cart); // Recharger le panier depuis la BDD

        // 6. Vérifier que le CartItem existe et est lié au bon Cart
        $cartItemRepo = $this->em->getRepository(CartItem::class);
        $cartItem = $cartItemRepo->findOneBy([
            'cart' => $cart,
            'product' => $product,
            'size' => $size
        ]);

        $this->assertNotNull($cartItem, 'Le CartItem doit être créé.');
        $this->assertEquals($cart->getId(), $cartItem->getCart()->getId(), 'Le CartItem doit appartenir au bon Cart.');

        // 7. Vérifier que la quantité a augmenté si le produit existait déjà
        if ($existingItem) {
            $this->assertEquals($initialQuantity + 1, $cartItem->getQuantity());
        } else {
            $this->assertEquals(1, $cartItem->getQuantity());
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
        $this->em = null;
    }
}
