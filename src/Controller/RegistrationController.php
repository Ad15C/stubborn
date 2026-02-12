<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash du mot de passe
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Générer un token unique pour l'activation
            $activationToken = Uuid::v4()->toRfc4122();
            $user->setVerificationToken($activationToken);
            $user->setIsVerified(false);

            $em->persist($user);
            $em->flush();

            // Préparer le mail de confirmation
            $activationLink = $this->generateUrl('app_verify_email', [
                'token' => $activationToken
            ], true);

            $email = (new Email())
                ->from('stubborn@blabla.com')
                ->to($user->getEmail())
                ->subject('Activez votre compte Stubborn')
                ->html("
                    <p>Bonjour {$user->getName()},</p>
                    <p>Merci de vous être inscrit sur Stubborn !</p>
                    <p>Pour activer votre compte, cliquez sur le lien ci-dessous :</p>
                    <p><a href='{$activationLink}'>Activer mon compte</a></p>
                    <p>Si vous n'avez pas créé de compte, ignorez cet email.</p>
                ");

            $mailer->send($email);

            $this->addFlash('success', 'Un email de confirmation vous a été envoyé.');

            return $this->redirectToRoute('home');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verifyUserEmail(string $token, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('home');
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $em->flush();

        $this->addFlash('success', 'Votre compte est activé, vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }
}