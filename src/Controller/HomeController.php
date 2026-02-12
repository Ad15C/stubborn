<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(ProductRepository $productRepository): Response
    {
        // Récupérer les produits mis en avant
        $featuredProducts = $productRepository->findBy(
            ['isFeatured' => true],
            ['featuredRank' => 'ASC'] // tri par id ou autre critère si nécessaire
        );


        return $this->render('home/index.html.twig', [
            'featuredProducts' => $featuredProducts,
            'currentRoute' => 'home', // pour gérer le menu actif
        ]);
    }
}
