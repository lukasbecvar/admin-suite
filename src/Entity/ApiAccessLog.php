<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ApiAccessLogRepository;

/**
 * Class ApiAccessLog
 *
 * Entity object for mapping database table
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'api_access_logs')]
#[ORM\Index(name: 'time_idx', columns: ['time'])]
#[ORM\Index(name: 'user_id_idx', columns: ['user_id'])]
#[ORM\Entity(repositoryClass: ApiAccessLogRepository::class)]
class ApiAccessLog
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column(length: 255)]
    private ?string $method = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $time = null;

    #[ORM\ManyToOne(inversedBy: 'apiAccessLogs')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * Get id of the api access log (database ID)
     *
     * @return int|null The database ID of the api access log or null if not found
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get request url
     *
     * @return string|null The request url or null if not found
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set request url
     *
     * @param string $url The request url
     *
     * @return static The api access log object
     */
    public function setUrl(string $url): static
    {
        // prevent maximal length
        if (strlen($url) > 255) {
            $url = substr($url, 0, 250) . "...";
        }

        $this->url = $url;

        return $this;
    }

    /**
     * Get method of the request
     *
     * @return string|null The method of the request or null if not found
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Set method of the request
     *
     * @param string $method The method of the request
     *
     * @return static The api access log object
     */
    public function setMethod(string $method): static
    {
        // prevent maximal length
        if (strlen($method) > 255) {
            $method = substr($method, 0, 250) . "...";
        }

        $this->method = $method;

        return $this;
    }

    /**
     * Get time of the request
     *
     * @return DateTimeInterface|null The time of the request or null if not found
     */
    public function getTime(): ?DateTimeInterface
    {
        return $this->time;
    }

    /**
     * Set time of the request
     *
     * @param DateTimeInterface $time The time of the request
     *
     * @return static The api access log object
     */
    public function setTime(DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get user who made the request
     *
     * @return User|null The user who made the request or null if not found
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set user who made the request
     *
     * @param User $user The user who made the request
     *
     * @return static The api access log object
     */
    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
