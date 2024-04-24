<?php

namespace App\Entity;

use App\Repository\TennisGroundRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TennisGroundRepository::class)]
class TennisGround
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, BusyAppointments>
     */
    #[ORM\OneToMany(targetEntity: BusyAppointments::class, mappedBy: 'ground')]
    private Collection $appointments;

    public function __construct()
    {
        $this->appointments = new ArrayCollection();
    }

    //#[ORM\ManyToOne(inversedBy: 'ground')] //mozemo da imamo vise termina zakazanih za ovaj teren
   // private ?BusyAppointments $appointment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, BusyAppointments>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(BusyAppointments $appointment): static
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->setGround($this);
        }

        return $this;
    }

    public function removeAppointment(BusyAppointments $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getGround() === $this) {
                $appointment->setGround(null);
            }
        }

        return $this;
    }

}
