<?php

namespace App\Entity;

use App\Repository\BusyAppointmentsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BusyAppointmentsRepository::class)]
class BusyAppointments
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $time = null;

    /*
     * @var Collection<int, TennisGround>
     */
    //#[ORM\OneToMany(targetEntity: TennisGround::class, mappedBy: 'appointment')]
    //private Collection $ground;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TennisGround $ground = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /*
     * @var Collection<int, User>
     */
    //#[ORM\OneToMany(targetEntity: User::class, mappedBy: 'appointments')]
    //private Collection $user;

    public function __construct(User $user, TennisGround $ground)
    {
        $this->user = $user;
        $this->ground = $ground;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    public function setTime(int $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getGround(): ?TennisGround
    {
        return $this->ground;
    }

    public function setGround(?TennisGround $ground): static
    {
        $this->ground = $ground;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
