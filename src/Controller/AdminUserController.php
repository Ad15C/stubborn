<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminUserFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminUserController extends AbstractController
{
    #[Route('/admin/create-user', name: 'admin_create_user')]
    public function createUser(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response
    {
        // Autoriser uniquement les admins
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $form = $this->createForm(AdminUserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé !');
            return $this->redirectToRoute('admin_create_user');
        }

        return $this->render('admin/create_user.html.twig', [
            'adminUserForm' => $form->createView(),
        ]);
    }
}
