<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductShowPageTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private Product $product;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        // Nettoyer la table
        $this->em->createQuery('DELETE FROM App\Entity\Product')->execute();

        // Créer un produit de test
        $this->product = (new Product())
            ->setName('Produit Show Test')
            ->setPrice(49.99)
            ->setImage('images/products/test-show.jpeg');

        $this->em->persist($this->product);
        $this->em->flush();
    }

    public function testProductShowPageLoads(): void
    {
        $this->client->request('GET', '/product/' . $this->product->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testProductInformationIsDisplayed(): void
    {
        $crawler = $this->client->request('GET', '/product/' . $this->product->getId());

        // Vérifier nom
        $this->assertStringContainsString(
            'Produit Show Test',
            $crawler->filter('.product-details')->text()
        );

        // Vérifier prix
        $this->assertStringContainsString(
            '49.99',
            $crawler->filter('.product-details')->text()
        );

        // Vérifier image
        $this->assertSelectorExists('.product-image img');
    }

    public function testSizesAreDisplayed(): void
    {
        $this->client->request('GET', '/product/' . $this->product->getId());

        $this->assertSelectorExists('input[value="XS"]');
        $this->assertSelectorExists('input[value="S"]');
        $this->assertSelectorExists('input[value="M"]');
        $this->assertSelectorExists('input[value="L"]');
        $this->assertSelectorExists('input[value="XL"]');
    }

    public function testAddToCartButtonExists(): void
    {
        $this->client->request('GET', '/product/' . $this->product->getId());

        $this->assertSelectorExists('.btn-add');
        $this->assertSelectorTextContains('.btn-add', 'Ajouter au panier');
    }

    public function testBackLinkWorks(): void
    {
        $crawler = $this->client->request('GET', '/product/' . $this->product->getId());

        $link = $crawler->selectLink('Retour à la liste des produits')->link();

        $this->client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.products-grid');
    }
}
