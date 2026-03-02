<?php

namespace App\Entity;

use App\Enum\RentalStatus;
use App\Repository\RentalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RentalRepository::class)]
class Rental
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'rentals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vehicle $vehicle = null;

    #[ORM\Column]
    private ?\DateTime $startdate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $end_date = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $daily_rate = null;

    #[ORM\Column(enumType: RentalStatus::class)]
    private RentalStatus $status = RentalStatus::CREATED;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): static
    {
        $this->vehicle = $vehicle;

        return $this;
    }

    public function getStartdate(): ?\DateTime
    {
        return $this->startdate;
    }

    public function setStartdate(\DateTime $startdate): static
    {
        $this->startdate = $startdate;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->end_date;
    }

    public function setEndDate(?\DateTime $end_date): static
    {
        $this->end_date = $end_date;

        return $this;
    }

    public function getDailyRate(): ?string
    {
        return $this->daily_rate;
    }

    public function setDailyRate(string $daily_rate): static
    {
        $this->daily_rate = $daily_rate;

        return $this;
    }

    /**
     * Get the value of status
     */
    public function getStatus(): RentalStatus
    {
        return $this->status;
    }

    /**
     * Set the value of status
     */
    public function setStatus(RentalStatus $status): self
    {
        $this->status = $status;

        return $this;
    }
}
