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

            // Vérifier si email déjà utilisé
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé. Veuillez en choisir un autre.');
                return $this->redirectToRoute('app_register');
            }

            // Hash du mot de passe
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Attribuer le rôle client par défaut
            $user->setRoles(['ROLE_USER']);

            // Générer un token unique pour l'activation
            $activationToken = Uuid::v4()->toRfc4122();
            $user->setVerificationToken($activationToken);
            $user->setIsVerified(false);

            $em->persist($user);
            $em->flush();

            // --- DEV ONLY : forcer la vérification pour tester l’appli ---
            if ($this->getParameter('kernel.environment') === 'dev') {
                $user->setIsVerified(true);
                $user->setVerificationToken(null);
                $em->flush();
            }

            // Générer le lien d’activation
            $activationLink = $this->generateUrl('app_verify_email', [
                'token' => $activationToken
            ], true);

            // Préparer le mail – en dev, envoyer toujours à Mailtrap
            $to = $this->getParameter('kernel.environment') === 'dev'
                ? 'e9ed2e81d9-25808c+user1@inbox.mailtrap.io'
                : $user->getEmail();

            $email = (new Email())
                ->from('stubborn@blabla.com')
                ->to($to)
                ->subject('Activez votre compte Stubborn')
                ->html("
                    <p>Bonjour {$user->getName()},</p>
                    <p>Merci de vous être inscrit sur Stubborn !</p>
                    <p>Pour activer votre compte, cliquez sur le lien ci-dessous :</p>
                    <p><a href='{$activationLink}'>Activer mon compte</a></p>
                    <p>Si vous n'avez pas créé de compte, ignorez cet email.</p>
                ");

            try {
                $mailer->send($email);
                $this->addFlash('success', 'Un email de confirmation vous a été envoyé (Mailtrap en dev).');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible d\’envoyer l\’email : ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_login');
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

    #[Route('/test-mail', name: 'test_mail')]
    public function testMail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('stubborn@blabla.com')
            ->to('e9ed2e81d9-25808c+user1@inbox.mailtrap.io')
            ->subject('Test Mail Symfony')
            ->text('Ceci est un test.');

        try {
            $mailer->send($email);
            return new Response('Mail envoyé !');
        } catch (\Exception $e) {
            return new Response('Erreur lors de l\’envoi : ' . $e->getMessage());
        }
    }
}