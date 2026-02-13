<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class AdminProductController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $products = $em->getRepository(Product::class)->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/admin/product/add', name: 'admin_product_add', methods: ['POST'])]
    public function addProduct(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = new Product();
        $product->setName($request->request->get('name'));
        $product->setPrice((float)$request->request->get('price'));
        $product->setStockXs((int)$request->request->get('stock_xs'));
        $product->setStockS((int)$request->request->get('stock_s'));
        $product->setStockM((int)$request->request->get('stock_m'));
        $product->setStockL((int)$request->request->get('stock_l'));
        $product->setStockXl((int)$request->request->get('stock_xl'));
        $product->setIsFeatured($request->request->get('is_featured') ? true : false);

        // Upload image
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move($this->getParameter('product_images_directory'), $newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l’upload de l’image');
            }

            $product->setImage($newFilename);
        }

        $em->persist($product);
        $em->flush();

        $this->addFlash('success', 'Produit ajouté !');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/product/{id}/update', name: 'admin_product_update', methods: ['POST'])]
    public function updateProduct(Request $request, Product $product, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product->setPrice((float)$request->request->get('price'));
        $product->setStockXs((int)$request->request->get('stock_xs'));
        $product->setStockS((int)$request->request->get('stock_s'));
        $product->setStockM((int)$request->request->get('stock_m'));
        $product->setStockL((int)$request->request->get('stock_l'));
        $product->setStockXl((int)$request->request->get('stock_xl'));
        $product->setIsFeatured($request->request->get('is_featured') ? true : false);

        // Upload nouvelle image si présente
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move($this->getParameter('product_images_directory'), $newFilename);
                $product->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\’upload de l\’image');
            }
        }

        $em->flush();

        $this->addFlash('success', 'Produit mis à jour !');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/product/{id}/delete', name: 'admin_product_delete', methods: ['POST'])]
    public function deleteProduct(Product $product, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'Produit supprimé !');
        return $this->redirectToRoute('admin_dashboard');
    }
}
