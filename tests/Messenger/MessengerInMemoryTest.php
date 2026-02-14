<?php

namespace App\Tests\Messenger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class MessengerInMemoryTest extends KernelTestCase
{
    public function testMessengerTransportIsInMemory(): void
    {
        self::bootKernel();

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');

        // Vérifie que c'est bien un transport in-memory
        $this->assertInstanceOf(
            InMemoryTransport::class,
            $transport,
            'Le transport Messenger doit être InMemory en environnement test'
        );

        // Vérifie qu'il est vide au départ
        $this->assertCount(0, $transport->getSent(), 'Le transport in-memory doit être vide au démarrage des tests');
    }
}
