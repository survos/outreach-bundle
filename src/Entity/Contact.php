<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\OutreachBundle\Entity\Enum\ContactEmailStatus;
use Survos\OutreachBundle\Entity\Traits\TaggableTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [new Get(), new GetCollection(), new Post(), new Patch(), new Delete()],
    normalizationContext: ['groups' => ['contact:read']],
    denormalizationContext: ['groups' => ['contact:write']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'organization.id' => 'exact',
    'firstName' => 'partial',
    'lastName' => 'partial',
    'email' => 'partial',
    'emailStatus' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['lastName', 'firstName', 'email', 'createdAt', 'updatedAt'])]
#[ORM\Entity]
#[ORM\Table(name: 'outreach_contact')]
#[ORM\UniqueConstraint(name: 'outreach_contact_email_uq', columns: ['email'])]
#[ORM\Index(fields: ['lastName', 'firstName'], name: 'outreach_contact_name_idx')]
#[ORM\HasLifecycleCallbacks]
class Contact
{
    use TaggableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['contact:read', 'organization:read', 'activity:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'contacts')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['contact:read', 'contact:write'])]
    private ?Organization $organization = null;

    #[ORM\Column(length: 128, nullable: true)]
    #[Groups(['contact:read', 'contact:write', 'activity:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 128, nullable: true)]
    #[Groups(['contact:read', 'contact:write', 'activity:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $jobTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $email = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $phone = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $preferredContact = null;

    #[ORM\Column(enumType: ContactEmailStatus::class, length: 32)]
    #[Groups(['contact:read', 'contact:write'])]
    private ContactEmailStatus $emailStatus = ContactEmailStatus::UNKNOWN;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['contact:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['contact:read'])]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, Activity> */
    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: Activity::class, cascade: ['persist'])]
    private Collection $activities;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName !== null ? trim($firstName) : null;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName !== null ? trim($lastName) : null;
    }

    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->firstName ?? '', $this->lastName ?? ''));
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): void
    {
        $this->jobTitle = $jobTitle !== null ? trim($jobTitle) : null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email !== null ? strtolower(trim($email)) : null;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone !== null ? trim($phone) : null;
    }

    public function getPreferredContact(): ?string
    {
        return $this->preferredContact;
    }

    public function setPreferredContact(?string $preferredContact): void
    {
        $this->preferredContact = $preferredContact !== null ? trim($preferredContact) : null;
    }

    public function getEmailStatus(): ContactEmailStatus
    {
        return $this->emailStatus;
    }

    public function setEmailStatus(ContactEmailStatus $emailStatus): void
    {
        $this->emailStatus = $emailStatus;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes !== null ? trim($notes) : null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return Collection<int, Activity> */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): void
    {
        if (!$this->activities->contains($activity)) {
            $this->activities->add($activity);
            $activity->setContact($this);
        }
    }

    public function __toString(): string
    {
        $fullName = $this->getFullName();

        return $fullName !== '' ? $fullName : (string) ($this->email ?? 'contact');
    }
}
