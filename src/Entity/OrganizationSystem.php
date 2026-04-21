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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\OutreachBundle\Entity\Enum\OrganizationSystemType;
use Survos\OutreachBundle\Entity\Traits\TaggableTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [new Get(), new GetCollection(), new Post(), new Patch(), new Delete()],
    normalizationContext: ['groups' => ['organization_system:read']],
    denormalizationContext: ['groups' => ['organization_system:write']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'organization.id' => 'exact',
    'system' => 'exact',
    'externalId' => 'partial',
    'url' => 'partial',
])]
#[ApiFilter(OrderFilter::class, properties: ['system', 'isPrimary', 'id'])]
#[ORM\Entity]
#[ORM\Table(name: 'outreach_organization_system')]
#[ORM\Index(fields: ['system'], name: 'outreach_org_system_type_idx')]
class OrganizationSystem
{
    use TaggableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['organization_system:read', 'organization:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'systems')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['organization_system:read', 'organization_system:write'])]
    private ?Organization $organization = null;

    #[ORM\Column(enumType: OrganizationSystemType::class, length: 32)]
    #[Groups(['organization_system:read', 'organization_system:write', 'organization:read'])]
    private OrganizationSystemType $system = OrganizationSystemType::UNKNOWN;

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['organization_system:read', 'organization_system:write'])]
    private ?string $url = null;

    #[ORM\Column(length: 191, nullable: true)]
    #[Groups(['organization_system:read', 'organization_system:write'])]
    private ?string $externalId = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['organization_system:read', 'organization_system:write', 'organization:read'])]
    private bool $isPrimary = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['organization_system:read', 'organization_system:write'])]
    private ?string $notes = null;

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

    public function getSystem(): OrganizationSystemType
    {
        return $this->system;
    }

    public function setSystem(OrganizationSystemType $system): void
    {
        $this->system = $system;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url !== null ? trim($url) : null;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): void
    {
        $this->externalId = $externalId !== null ? trim($externalId) : null;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): void
    {
        $this->isPrimary = $isPrimary;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes !== null ? trim($notes) : null;
    }
}
