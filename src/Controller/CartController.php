<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Product;

class CartController extends AbstractController
{
    // Ajouter un produit avec la taille
    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add($id, Request $request, SessionInterface $session)
    {
        $size = $request->request->get('size');
        $product = $this->getDoctrine()->getRepository(Product::class)->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        $cart = $session->get('cart', []);

        $cart[] = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'size' => $size,
            'image' => $product->getImage()
        ];

        $session->set('cart', $cart);

        return $this->redirectToRoute('cart_show');
    }


    // Afficher le panier
    #[Route('/cart', name: 'cart_show')]
    public function show(SessionInterface $session)
    {
        $cart = $session->get('cart', []);
        $total = 0;

        foreach ($cart as $item) {
            $total += $item['price'];
        }

        return $this->render('cart/index.html.twig', [
            'products' => $cart,
            'total' => $total
        ]);
    }

    // Supprimer un produit
     #[Route('/cart/remove/{index}', name: 'cart_remove')]
    public function remove($index, SessionInterface $session)
    {
        $cart = $session->get('cart', []);
        if (isset($cart[$index])) {
            unset($cart[$index]);
        }

        $cart = array_values($cart);
        $session->set('cart', $cart);

        return $this->redirectToRoute('cart_show');
    }

    // Stripe
    #[Route('/checkout', name: 'checkout', methods:['POST'])]
    public function checkout(SessionInterface $session)
    {
        $cart = $session->get('cart', []);
        // Ici tu calcules le total et déclenches Stripe
        // ...
    }
}
