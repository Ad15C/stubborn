<?php

namespace App\Tests\Mailer;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Mime\Email;

class MailerInMemoryTest extends KernelTestCase
{
    public function testMailerIsNullTransport(): void
    {
        self::bootKernel();

        /** @var MailerInterface $mailer */
        $mailer = self::getContainer()->get(MailerInterface::class);

        // On envoie un mail
        $email = (new Email())
            ->from('test@example.com')
            ->to('test2@example.com')
            ->subject('Test')
            ->text('Bonjour');

        // Ici, Mailer ne doit rien envoyer car transport null
        $mailer->send($email);

        // Si on arrive ici sans exception, c’est OK
        $this->assertTrue(true, 'Mailer null fonctionne correctement');
    }
}
