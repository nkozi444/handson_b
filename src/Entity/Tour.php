<?php

namespace App\Entity;

use App\Entity\Exhibition;
use App\Entity\User;
use App\Repository\TourRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TourRepository::class)]
#[ORM\Table(name: 'tour')]
#[ORM\Index(name: 'idx_tour_date', columns: ['date'])]
#[ORM\Index(name: 'idx_tour_status', columns: ['status'])]
#[ORM\HasLifecycleCallbacks]
class Tour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ Owner of this booking (Option A)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    // Visitor display name
    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private ?string $name = null;

    // Contact email
    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    // Optional phone
    #[ORM\Column(length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    private ?string $phoneNumber = null;

    // Party size
    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Positive]
    private ?int $numberOfGuests = 1;

    // Requested visit datetime
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $date = null;

    // Optional notes
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    // Request status
    #[ORM\Column(length: 20, options: ['default' => 'pending'])]
    #[Assert\Choice(['pending', 'confirmed', 'cancelled'])]
    private string $status = 'pending';

    // Auditing
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    // --- Exhibition relation (optional) ---
    /**
     * Many Tours may optionally reference one Exhibition.
     * Column name will be `exhibition_id` in the `tour` table.
     */
    #[ORM\ManyToOne(targetEntity: Exhibition::class)]
    #[ORM\JoinColumn(name: 'exhibition_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Exhibition $exhibition = null;

    // --- Custom exhibition request (optional) ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $requestedExhibition = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->numberOfGuests = 1;
        $this->status = 'pending';
    }

    #[ORM\PrePersist]
    public function onCreate(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf(
            '#%d %s %s',
            $this->id ?? 0,
            $this->name ?? '',
            $this->date?->format('Y-m-d H:i') ?? ''
        );
    }

    // --- getters & setters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    // ✅ User relation getter/setter
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getNumberOfGuests(): ?int
    {
        return $this->numberOfGuests;
    }

    public function setNumberOfGuests(int $numberOfGuests): self
    {
        $this->numberOfGuests = $numberOfGuests;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // --- exhibition getter/setter ---
    public function getExhibition(): ?Exhibition
    {
        return $this->exhibition;
    }

    public function setExhibition(?Exhibition $exhibition): self
    {
        $this->exhibition = $exhibition;
        return $this;
    }

    // --- requested exhibition getter/setter ---
    public function getRequestedExhibition(): ?string
    {
        return $this->requestedExhibition;
    }

    public function setRequestedExhibition(?string $requestedExhibition): self
    {
        $this->requestedExhibition = $requestedExhibition;
        return $this;
    }
}
