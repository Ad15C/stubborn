<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof \App\Entity\User) return;

        if (!$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException(
                'Vous devez activer votre compte via le mail de confirmation.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // rien à faire ici pour l’instant
    }
}
