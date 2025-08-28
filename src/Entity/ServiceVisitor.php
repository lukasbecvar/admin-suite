<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ServiceVisitorRepository;

/**
 * Class ServiceVisitor
 *
 * Entity object for mapping database table
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'services_visitors')]
#[ORM\Index(name: 'referer_idx', columns: ['referer'])]
#[ORM\Index(name: 'location_idx', columns: ['location'])]
#[ORM\Index(name: 'ip_address_idx', columns: ['ip_address'])]
#[ORM\Index(name: 'service_name_idx', columns: ['service_name'])]
#[ORM\Entity(repositoryClass: ServiceVisitorRepository::class)]
class ServiceVisitor
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $last_visit_time = null;

    #[ORM\Column(length: 255)]
    private ?string $user_agent = null;

    #[ORM\Column(length: 255)]
    private ?string $referer = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column(length: 255)]
    private ?string $ip_address = null;

    #[ORM\Column(length: 255)]
    private ?string $service_name = null;

    /**
     * Get database ID of the service visitor
     *
     * @return int|null The database ID of the service visitor or null if not found
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get last visit time of the service visitor
     *
     * @return DateTimeInterface|null The last visit time of the service visitor or null if not found
     */
    public function getLastVisitTime(): ?DateTimeInterface
    {
        return $this->last_visit_time;
    }

    /**
     * Set last visit time of the service visitor
     *
     * @param DateTimeInterface $last_visit_time The last visit time of the service visitor
     *
     * @return static The service visitor object
     */
    public function setLastVisitTime(DateTimeInterface $last_visit_time): static
    {
        $this->last_visit_time = $last_visit_time;

        return $this;
    }

    /**
     * Get user agent of the service visitor
     *
     * @return string|null The user agent of the service visitor or null if not found
     */
    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    /**
     * Set user agent of the service visitor
     *
     * @param string $user_agent The user agent of the service visitor
     *
     * @return static The service visitor object
     */
    public function setUserAgent(string $user_agent): static
    {
        $this->user_agent = $user_agent;

        return $this;
    }

    /**
     * Get referer of the service visitor
     *
     * @return string|null The referer of the service visitor or null if not found
     */
    public function getReferer(): ?string
    {
        return $this->referer;
    }

    /**
     * Set referer of the service visitor
     *
     * @param string $referer The referer of the service visitor
     *
     * @return static The service visitor object
     */
    public function setReferer(string $referer): static
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * Get location of the service visitor
     *
     * @return string|null The location of the service visitor or null if not found
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * Set location of the service visitor
     *
     * @param string $location The location of the service visitor
     *
     * @return static The service visitor object
     */
    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get ip address of the service visitor
     *
     * @return string|null The ip address of the service visitor or null if not found
     */
    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    /**
     * Set ip address of the service visitor
     *
     * @param string $ip_address The ip address of the service visitor
     *
     * @return static The service visitor object
     */
    public function setIpAddress(string $ip_address): static
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    /**
     * Get service name of the service visitor
     *
     * @return string|null The service name of the service visitor or null if not found
     */
    public function getServiceName(): ?string
    {
        return $this->service_name;
    }

    /**
     * Set service name of the service visitor
     *
     * @param string $service_name The service name of the service visitor
     *
     * @return static The service visitor object
     */
    public function setServiceName(string $service_name): static
    {
        $this->service_name = $service_name;

        return $this;
    }
}
