<?php
namespace App\Controller;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ToolsController extends AbstractController
{
    #[Route('/fix-images', name: 'fix_images')]
    public function fixImages(ProductRepository $productRepo, EntityManagerInterface $em): Response
    {
        $products = $productRepo->findAll();
        $folder = $this->getParameter('kernel.project_dir') . '/public/images/products/';

        $corrected = 0;

        foreach ($products as $product) {
            $imageName = $product->getImage();
            if (!$imageName) {
                continue;
            }

            $found = null;
            foreach (scandir($folder) as $file) {
                if (strcasecmp($file, $imageName) === 0) {
                    $found = $file;
                    break;
                }
            }

            if ($found && $found !== $imageName) {
                $product->setImage($found);
                $em->persist($product);
                $corrected++;
            }
        }

        $em->flush();

        return new Response("Correction terminée ! Produits mis à jour : $corrected");
    }
}