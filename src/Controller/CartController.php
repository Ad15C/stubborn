<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\ProductRepository;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class CartController extends AbstractController
{
    // Ajouter un produit au panier
    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(
        int $id,
        Request $request,
        ProductRepository $productRepository,
        CartRepository $cartRepository,
        EntityManagerInterface $em,
        Security $security
    ): RedirectResponse {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $size = $request->request->get('size');

        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
        }

        $existingItem = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct() === $product && $item->getSize() === $size) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            $existingItem->setQuantity($existingItem->getQuantity() + 1);
        } else {
            $cartItem = new CartItem();
            $cartItem->setProduct($product);
            $cartItem->setPrice($product->getPrice());
            $cartItem->setQuantity(1);
            $cartItem->setSize($size);
            $cartItem->setCart($cart);

            $em->persist($cartItem);
            $cart->addItem($cartItem);
        }

        $em->persist($cart);
        $em->flush();

        return $this->redirectToRoute('cart_show');
    }

    // Afficher le panier
    #[Route('/cart', name: 'cart_show')]
    public function show(Security $security, CartRepository $cartRepository): Response
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $cart = $cartRepository->findOneBy(['user' => $user]);
        $items = $cart ? $cart->getItems() : [];
        $total = 0;

        foreach ($items as $item) {
            $total += $item->getPrice() * $item->getQuantity();
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'items' => $items,
            'total' => $total,
        ]);
    }

    // Supprimer un produit
    #[Route('/cart/remove/{id}', name: 'cart_remove')]
    public function remove(
        int $id,
        CartRepository $cartRepository,
        EntityManagerInterface $em,
        Security $security
    ): RedirectResponse {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $cart = $cartRepository->findOneBy(['user' => $user]);
        if ($cart) {
            foreach ($cart->getItems() as $item) {
                if ($item->getId() === $id) {
                    $cart->removeItem($item); // retire de la collection
                    $em->remove($item);       // supprime de la DB
                    break;
                }
            }
            $em->flush();
        }

        return $this->redirectToRoute('cart_show');
    }

    // Checkout Stripe (bypass Stripe en test)
    #[Route('/checkout', name: 'checkout', methods:['POST'])]
    public function checkout(
        CartRepository $cartRepository,
        EntityManagerInterface $em,
        Security $security
    ): RedirectResponse {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart || $cart->getItems()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_show');
        }

        // Création de la commande
        $order = new Order();
        $order->setUser($user)
            ->setStatus(Order::STATUS_PENDING);

        $em->persist($order);
        $em->flush(); // générer l’ID

        $total = 0;
        foreach ($cart->getItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setProduct($cartItem->getProduct())
                ->setProductName($cartItem->getProduct()->getName())
                ->setPrice($cartItem->getPrice())
                ->setQuantity($cartItem->getQuantity())
                ->setSize($cartItem->getSize())
                ->setOrder($order);

            $em->persist($orderItem);
            $total += $cartItem->getPrice() * $cartItem->getQuantity();
        }

        $order->setTotal($total);
        $em->flush();

        // Bypass Stripe en environnement test
        if ($this->getParameter('kernel.environment') === 'test') {
            return $this->redirectToRoute('payment_success', ['id' => $order->getId()]);
        }

        // Stripe réel
        $key = $_ENV['STRIPE_SECRET_KEY'] ?? 'sk_test_dummy';
        Stripe::setApiKey($key);

        $checkoutSession = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => array_map(function(CartItem $item) {
                return [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $item->getProduct()->getName() . ' - Taille ' . $item->getSize(),
                        ],
                        'unit_amount' => $item->getPrice() * 100,
                    ],
                    'quantity' => $item->getQuantity(),
                ];
            }, $cart->getItems()->toArray()),
            'mode' => 'payment',
            'success_url' => $this->generateUrl(
                'payment_success',
                ['id' => $order->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'cancel_url' => $this->generateUrl('cart_show', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($checkoutSession->url);
    }

    // Page succès
    #[Route('/success/{id}', name: 'payment_success')]
    public function success(
        Order $order,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        Security $security
    ): Response {
        if ($order->getStatus() !== Order::STATUS_PAID) {
            $order->setStatus(Order::STATUS_PAID);
            $em->flush();
        }

        // Vider le panier
        $user = $security->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);
        if ($cart) {
            foreach ($cart->getItems() as $item) {
                $em->remove($item);
            }
            $em->flush();

            // ✅ vider la collection pour tests
            $cart->getItems()->clear();
        }

        return $this->render('cart/success.html.twig', [
            'order' => $order
        ]);
    }
}
