<?php

namespace App\Repository;

use App\Entity\ServiceVisitor;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class ServiceVisitorRepository
 *
 * Repository class for ServiceVisitor entity
 *
 * @extends ServiceEntityRepository<ServiceVisitor>
 *
 * @package App\Repository
 */
class ServiceVisitorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceVisitor::class);
    }

    /**
     * Find visitors by IP address
     *
     * @param string $ipAddress The IP address to search for
     *
     * @return ServiceVisitor[] An array of ServiceVisitor objects
     */
    public function findByIpAddress(string $ipAddress): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.ip_address = :ip')
            ->setParameter('ip', $ipAddress)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find visitors by service name
     *
     * @param string $serviceName The service name to search for
     *
     * @return ServiceVisitor[] An array of ServiceVisitor objects
     */
    public function findByServiceName(string $serviceName, ?int $limit = null, ?int $offset = null): array
    {
        $query = $this->createQueryBuilder('v')
            ->andWhere('v.service_name = :serviceName')
            ->setParameter('serviceName', $serviceName);

        if (isset($limit)) {
            $query->setMaxResults($limit);
        }

        if (isset($offset)) {
            $query->setFirstResult($offset);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Get list of referers with count for a given service name
     *
     * @param string $serviceName The service name to search for
     *
     * @return array<int, array{referer: string, total: int}> An array of referers with count
     */
    public function getReferersByServiceName(string $serviceName): array
    {
        return $this->createQueryBuilder('v')
            ->select('v.referer AS referer, COUNT(v.id) AS total')
            ->andWhere('v.service_name = :serviceName')
            ->setParameter('serviceName', $serviceName)
            ->groupBy('v.referer')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Get list of locations with count for a given service name
     *
     * @param string $serviceName The service name to search for
     *
     * @return array<int, array{location: string, total: int}> An array of locations with count
     */
    public function getLocationsByServiceName(string $serviceName): array
    {
        return $this->createQueryBuilder('v')
            ->select('v.location AS location, COUNT(v.id) AS total')
            ->andWhere('v.service_name = :serviceName')
            ->setParameter('serviceName', $serviceName)
            ->groupBy('v.location')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Get total visitors count for a given service name
     *
     * @param string $serviceName The service name to search for
     *
     * @return int The total visitors count
     */
    public function getCountByServiceName(string $serviceName): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.service_name = :serviceName')
            ->setParameter('serviceName', $serviceName)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get total visitors count (all services)
     *
     * @return int The total visitors count
     */
    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
