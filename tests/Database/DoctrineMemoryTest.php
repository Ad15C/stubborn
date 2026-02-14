<?php

namespace App\Tests\Database;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;

class DoctrineMemoryTest extends KernelTestCase
{
    public function testDatabaseIsInMemorySQLite(): void
    {
        self::bootKernel();

        /** @var Connection $connection */
        $connection = self::getContainer()->get('doctrine')->getConnection();

        $params = $connection->getParams();

        // Vérifie que memory=true
        $this->assertArrayHasKey('memory', $params, 'La configuration doit avoir memory=true');
        $this->assertTrue($params['memory'], 'La base doit être en mémoire pour les tests');

        // Vérifie que la plateforme est SQLite
        $platform = $connection->getDatabasePlatform();
        $this->assertInstanceOf(
            SqlitePlatform::class,
            $platform,
            sprintf('La plateforme doit être SQLite, trouvé %s', get_class($platform))
        );
    }
}
