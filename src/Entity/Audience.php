<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\FieldBundle\Attribute\EntityMeta;
use Survos\OutreachBundle\Entity\Enum\AudienceKind;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * A purpose we email people for: the MARC introduction, partner follow-up, the Foundation newsletter.
 * Permission is granted per audience (see Subscription), never globally.
 */
#[EntityMeta(icon: 'mdi:account-group-outline', group: 'Outreach')]
#[ApiResource(
    security: "is_granted('ROLE_ADMIN')",
    operations: [new Get(), new GetCollection(), new Post(), new Patch()],
    normalizationContext: ['groups' => ['audience:read']],
    denormalizationContext: ['groups' => ['audience:write']],
)]
#[ORM\Entity]
#[ORM\Table(name: 'outreach_audience')]
final class Audience
{
    #[ORM\Column(length: 255)]
    #[Groups(['audience:read', 'audience:write', 'subscription:read'])]
    public string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['audience:read', 'audience:write'])]
    public ?string $description = null;

    /** Sender for mail to this audience, e.g. news@museado.org. Null = the app's default sender. */
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['audience:read', 'audience:write'])]
    public ?string $fromEmail = null;

    /** Brevo list this audience is pushed to. Brevo membership is a copy; consent lives here. */
    #[ORM\Column(nullable: true)]
    #[Groups(['audience:read', 'audience:write'])]
    public ?int $brevoListId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['audience:read'])]
    public readonly \DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 64)]
        #[Groups(['audience:read', 'subscription:read'])]
        public readonly string $code,

        #[ORM\Column(enumType: AudienceKind::class, length: 16)]
        #[Groups(['audience:read', 'subscription:read'])]
        public readonly AudienceKind $kind,

        ?string $name = null,
    ) {
        $this->name = $name ?? $code;
        $this->createdAt = new \DateTimeImmutable();
    }

    public bool $requiresConfirmation {
        get => in_array($this->kind, [AudienceKind::NEWSLETTER, AudienceKind::ANNOUNCEMENTS], true);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
