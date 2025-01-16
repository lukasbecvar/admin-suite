<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\SLAHistoryRepository;

/**
 * Class SLAHistory
 *
 * The SLAHistory entity database table mapping class
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'sla_history')]
#[ORM\Index(name: 'sla_history_service_name_idx', columns: ['service_name'])]
#[ORM\Index(name: 'sla_history_sla_timeframe_idx', columns: ['sla_timeframe'])]
#[ORM\Entity(repositoryClass: SLAHistoryRepository::class)]
class SLAHistory
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $service_name = null;

    #[ORM\Column(length: 255)]
    private ?string $sla_timeframe = null;

    #[ORM\Column(length: 255)]
    private ?float $sla_value = null;

    /**
     * Get SLA id
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get service name
     *
     * @return string|null
     */
    public function getServiceName(): ?string
    {
        return $this->service_name;
    }

    /**
     * Set service name
     *
     * @param string $service_name The service name of the SLA
     *
     * @return static The current object
     */
    public function setServiceName(string $service_name): static
    {
        $this->service_name = $service_name;

        return $this;
    }

    /**
     * Get timeframe of the SLA
     *
     * @return string|null
     */
    public function getSlaTimeframe(): ?string
    {
        return $this->sla_timeframe;
    }

    /**
     * Set timeframe of the SLA
     *
     * @param string $sla_timeframe The timeframe of the SLA
     *
     * @return static The current object
     */
    public function setSlaTimeframe(string $sla_timeframe): static
    {
        $this->sla_timeframe = $sla_timeframe;

        return $this;
    }

    /**
     * Get SLA value
     *
     * @return float|null
     */
    public function getSlaValue(): ?float
    {
        return $this->sla_value;
    }

    /**
     * Set SLA value
     *
     * @param float $sla_value The SLA value
     *
     * @return static The current object
     */
    public function setSlaValue(float $sla_value): static
    {
        $this->sla_value = $sla_value;

        return $this;
    }
}
