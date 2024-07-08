<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ServiceMonitoringRepository;

/**
 * Class ServiceMonitoring
 *
 * The ServiceMonitoring entity database table mapping class
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'monitoring')]
#[ORM\Entity(repositoryClass: ServiceMonitoringRepository::class)]
class ServiceMonitoring
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $service_name = null;

    #[ORM\Column(length: 255)]
    private ?string $message = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $last_update_time = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServiceName(): ?string
    {
        return $this->service_name;
    }

    public function setServiceName(string $service_name): static
    {
        $this->service_name = $service_name;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getLastUpdateTime(): ?\DateTimeInterface
    {
        return $this->last_update_time;
    }

    public function setLastUpdateTime(\DateTimeInterface $last_update_time): static
    {
        $this->last_update_time = $last_update_time;

        return $this;
    }
}
