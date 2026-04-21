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
use Survos\OutreachBundle\Entity\Enum\OrganizationStatus;
use Survos\OutreachBundle\Entity\Traits\TaggableTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [new Get(), new GetCollection(), new Post(), new Patch(), new Delete()],
    normalizationContext: ['groups' => ['organization:read']],
    denormalizationContext: ['groups' => ['organization:write']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'key' => 'exact',
    'name' => 'partial',
    'status' => 'exact',
    'systems.system' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['key', 'name', 'createdAt', 'updatedAt'])]
#[ORM\Entity]
#[ORM\Table(name: 'outreach_organization')]
#[ORM\UniqueConstraint(name: 'outreach_organization_key_uq', columns: ['org_key'])]
#[ORM\HasLifecycleCallbacks]
class Organization
{
    use TaggableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['organization:read', 'contact:read', 'activity:read', 'organization_system:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'org_key', length: 191)]
    #[Groups(['organization:read', 'organization:write', 'contact:read'])]
    private string $key = '';

    #[ORM\Column(length: 255)]
    #[Groups(['organization:read', 'organization:write', 'contact:read', 'activity:read', 'organization_system:read'])]
    private string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    private ?string $website = null;

    #[ORM\Column(length: 128, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    private ?string $city = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    private ?string $stateProvince = null;

    #[ORM\Column(length: 3, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    private ?string $countryCode = null;

    #[ORM\Column(enumType: OrganizationStatus::class, length: 32, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    private ?OrganizationStatus $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['organization:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['organization:read'])]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, Contact> */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: Contact::class, cascade: ['persist'])]
    private Collection $contacts;

    /** @var Collection<int, Activity> */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: Activity::class, cascade: ['persist'])]
    private Collection $activities;

    /** @var Collection<int, OrganizationSystem> */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: OrganizationSystem::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $systems;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->activities = new ArrayCollection();
        $this->systems = new ArrayCollection();
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

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = trim($key);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): void
    {
        $this->website = $website !== null ? trim($website) : null;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city !== null ? trim($city) : null;
    }

    public function getStateProvince(): ?string
    {
        return $this->stateProvince;
    }

    public function setStateProvince(?string $stateProvince): void
    {
        $this->stateProvince = $stateProvince !== null ? trim($stateProvince) : null;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): void
    {
        $this->countryCode = $countryCode !== null ? strtoupper(trim($countryCode)) : null;
    }

    public function getStatus(): ?OrganizationStatus
    {
        return $this->status;
    }

    public function setStatus(?OrganizationStatus $status): void
    {
        $this->status = $status;
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

    /** @return Collection<int, Contact> */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): void
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
            $contact->setOrganization($this);
        }
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
            $activity->setOrganization($this);
        }
    }

    /** @return Collection<int, OrganizationSystem> */
    public function getSystems(): Collection
    {
        return $this->systems;
    }

    public function addSystem(OrganizationSystem $system): void
    {
        if (!$this->systems->contains($system)) {
            if ($system->isPrimary()) {
                foreach ($this->systems as $existing) {
                    $existing->setIsPrimary(false);
                }
            }
            $this->systems->add($system);
            $system->setOrganization($this);
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
