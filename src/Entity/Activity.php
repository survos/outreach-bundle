<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\OutreachBundle\Entity\Enum\ActivityDirection;
use Survos\OutreachBundle\Entity\Enum\ActivityType;
use Survos\OutreachBundle\Entity\Traits\TaggableTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Survos\FieldBundle\Attribute\EntityMeta;

#[EntityMeta(icon: 'mdi:timeline-outline', group: 'Outreach')]
#[ApiResource(
    operations: [new Get(), new GetCollection(), new Post(), new Patch(), new Delete()],
    normalizationContext: ['groups' => ['activity:read']],
    denormalizationContext: ['groups' => ['activity:write']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'organization.id' => 'exact',
    'contact.id' => 'exact',
    'type' => 'exact',
    'direction' => 'exact',
    'performedBy' => 'exact',
    'subject' => 'partial',
])]
#[ApiFilter(OrderFilter::class, properties: ['occurredAt', 'createdAt'])]
#[ApiFilter(DateFilter::class, properties: ['occurredAt', 'createdAt'])]
#[ORM\Entity]
#[ORM\Table(name: 'outreach_activity')]
#[ORM\Index(fields: ['type'], name: 'outreach_activity_type_idx')]
#[ORM\Index(fields: ['occurredAt'], name: 'outreach_activity_occurred_idx')]
class Activity
{
    use TaggableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['activity:read', 'organization:read', 'contact:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'activities')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['activity:read', 'activity:write'])]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(targetEntity: Contact::class, inversedBy: 'activities')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['activity:read', 'activity:write'])]
    private ?Contact $contact = null;

    #[ORM\Column(enumType: ActivityType::class, length: 32)]
    #[Groups(['activity:read', 'activity:write'])]
    private ActivityType $type = ActivityType::NOTE;

    #[ORM\Column(enumType: ActivityDirection::class, length: 16, nullable: true)]
    #[Groups(['activity:read', 'activity:write'])]
    private ?ActivityDirection $direction = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['activity:read', 'activity:write'])]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['activity:read', 'activity:write'])]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['activity:read', 'activity:write'])]
    private ?string $body = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['activity:read', 'activity:write'])]
    private ?string $outcome = null;

    #[ORM\Column(length: 128, nullable: true)]
    #[Groups(['activity:read', 'activity:write'])]
    private ?string $performedBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['activity:read', 'activity:write'])]
    private ?string $externalId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['activity:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): void
    {
        $this->contact = $contact;
    }

    public function getType(): ActivityType
    {
        return $this->type;
    }

    public function setType(ActivityType $type): void
    {
        $this->type = $type;
    }

    public function getDirection(): ?ActivityDirection
    {
        return $this->direction;
    }

    public function setDirection(?ActivityDirection $direction): void
    {
        $this->direction = $direction;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(\DateTimeImmutable $occurredAt): void
    {
        $this->occurredAt = $occurredAt;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): void
    {
        $this->subject = $subject !== null ? trim($subject) : null;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body !== null ? trim($body) : null;
    }

    public function getOutcome(): ?string
    {
        return $this->outcome;
    }

    public function setOutcome(?string $outcome): void
    {
        $this->outcome = $outcome !== null ? trim($outcome) : null;
    }

    public function getPerformedBy(): ?string
    {
        return $this->performedBy;
    }

    public function setPerformedBy(?string $performedBy): void
    {
        $this->performedBy = $performedBy !== null ? trim($performedBy) : null;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): void
    {
        $this->externalId = $externalId !== null ? trim($externalId) : null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
