<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\ServiceVisitor;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ServiceVisitorRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class ServiceVisitorRepositoryTest
 *
 * Test cases for doctrine service visitor repository
 *
 * @package App\Tests\Repository
 */
class ServiceVisitorRepositoryTest extends KernelTestCase
{
    private ServiceVisitorRepository $visitorRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->visitorRepository = $this->entityManager->getRepository(ServiceVisitor::class);

        // create testing data
        $visitor1 = new ServiceVisitor();
        $visitor1->setServiceName('paste.becvar.xyz');
        $visitor1->setIpAddress('192.168.0.1');
        $visitor1->setLocation('Czech Republic');
        $visitor1->setReferer('https://google.com');
        $visitor1->setUserAgent('PHPUnit UA 1');
        $visitor1->setLastVisitTime(new DateTime());

        $visitor2 = new ServiceVisitor();
        $visitor2->setServiceName('paste.becvar.xyz');
        $visitor2->setIpAddress('192.168.0.2');
        $visitor2->setLocation('Germany');
        $visitor2->setReferer('https://bing.com');
        $visitor2->setUserAgent('PHPUnit UA 2');
        $visitor2->setLastVisitTime(new DateTime());

        $visitor3 = new ServiceVisitor();
        $visitor3->setServiceName('speed-test.becvar.xyz');
        $visitor3->setIpAddress('192.168.0.3');
        $visitor3->setLocation('Czech Republic');
        $visitor3->setReferer('https://google.com');
        $visitor3->setUserAgent('PHPUnit UA 3');
        $visitor3->setLastVisitTime(new DateTime());

        // persist
        $this->entityManager->persist($visitor1);
        $this->entityManager->persist($visitor2);
        $this->entityManager->persist($visitor3);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\ServiceVisitor')->execute();
        parent::tearDown();
    }

    /**
     * Test repository can find visitors by IP address
     *
     * @return void
     */
    public function testFindByIpAddress(): void
    {
        // call tested method
        $results = $this->visitorRepository->findByIpAddress('192.168.0.1');

        // assert results
        $this->assertCount(1, $results);
        $this->assertSame('192.168.0.1', $results[0]->getIpAddress());
    }

    /**
     * Test repository can find visitors by service name
     *
     * @return void
     */
    public function testFindByServiceName(): void
    {
        // call tested method
        $results = $this->visitorRepository->findByServiceName('paste.becvar.xyz');

        // assert results
        $this->assertCount(2, $results);
        $this->assertSame('paste.becvar.xyz', $results[0]->getServiceName());
    }

    /**
     * Test repository can get referers with count for a given service name
     *
     * @return void
     */
    public function testGetReferersByServiceName(): void
    {
        // call tested method
        $referers = $this->visitorRepository->getReferersByServiceName('paste.becvar.xyz');

        // assert results
        $this->assertNotEmpty($referers);
        $this->assertSame('https://google.com', $referers[0]['referer'] ?? $referers[1]['referer']);
    }

    /**
     * Test repository can get locations with count for a given service name
     *
     * @return void
     */
    public function testGetLocationsByServiceName(): void
    {
        // call tested method
        $locations = $this->visitorRepository->getLocationsByServiceName('paste.becvar.xyz');

        // assert results
        $this->assertNotEmpty($locations);
        $this->assertContains('Czech Republic', array_column($locations, 'location'));
    }

    /**
     * Test repository can get total visitors count for a given service name
     *
     * @return void
     */
    public function testGetCountByServiceName(): void
    {
        // call tested method
        $count = $this->visitorRepository->getCountByServiceName('paste.becvar.xyz');

        // assert results
        $this->assertSame(2, $count);
    }

    /**
     * Test repository can get total visitors count (all services)
     *
     * @return void
     */
    public function testGetTotalCount(): void
    {
        // call tested method
        $count = $this->visitorRepository->getTotalCount();

        // assert results
        $this->assertSame(3, $count);
    }
}
