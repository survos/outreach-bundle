<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\FieldBundle\Attribute\EntityMeta;
use Survos\OutreachBundle\Entity\Enum\AudienceKind;
use Survos\OutreachBundle\Entity\Enum\ConsentSource;
use Survos\OutreachBundle\Entity\Enum\SubscriptionStatus;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Current permission of one contact for one audience.
 *
 * Read-only over the API on purpose: state changes go through the transition methods below,
 * and the caller records each one as an Activity (CONSENT / UNSUBSCRIBE / BOUNCE / COMPLAINT).
 * An UNSUBSCRIBED row is never deleted, which is what stops an import or Brevo sync
 * from re-adding the address.
 */
#[EntityMeta(icon: 'mdi:email-check-outline', group: 'Outreach')]
#[ApiResource(
    security: "is_granted('ROLE_ADMIN')",
    operations: [
        new Get(),
        new GetCollection(parameters: [
            'contact.id' => new QueryParameter(filter: new ExactFilter(), property: 'contact.id'),
            'audience.code' => new QueryParameter(filter: new ExactFilter(), property: 'audience.code'),
            'status' => new QueryParameter(filter: new ExactFilter(), property: 'status'),
        ]),
    ],
    normalizationContext: ['groups' => ['subscription:read']],
)]
#[ORM\Entity]
#[ORM\Table(name: 'outreach_subscription')]
#[ORM\UniqueConstraint(name: 'outreach_subscription_uq', columns: ['contact_id', 'audience_code'])]
#[ORM\Index(fields: ['status'], name: 'outreach_subscription_status_idx')]
final class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['subscription:read'])]
    public private(set) ?int $id = null;

    #[ORM\Column(enumType: SubscriptionStatus::class, length: 16)]
    #[Groups(['subscription:read'])]
    public private(set) SubscriptionStatus $status;

    /** What granted the permission: the import file and row, the form submission, etc. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['subscription:read'])]
    public ?string $evidence = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['subscription:read'])]
    public readonly \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['subscription:read'])]
    public private(set) ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['subscription:read'])]
    public private(set) ?\DateTimeImmutable $lastSentAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['subscription:read'])]
    public private(set) ?\DateTimeImmutable $unsubscribedAt = null;

    #[ORM\Column(enumType: ConsentSource::class, length: 32, nullable: true)]
    #[Groups(['subscription:read'])]
    public private(set) ?ConsentSource $unsubscribeSource = null;

    /** Changes on a fresh opt-in so old confirmation links cannot renew permission. */
    #[ORM\Column(length: 32)]
    public private(set) string $consentVersion;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Contact::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        #[Groups(['subscription:read'])]
        public readonly Contact $contact,

        #[ORM\ManyToOne(targetEntity: Audience::class)]
        #[ORM\JoinColumn(name: 'audience_code', referencedColumnName: 'code', nullable: false)]
        #[Groups(['subscription:read'])]
        public readonly Audience $audience,

        #[ORM\Column(enumType: ConsentSource::class, length: 32)]
        #[Groups(['subscription:read'])]
        public readonly ConsentSource $source,
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->consentVersion = bin2hex(random_bytes(16));
        $this->status = $audience->requiresConfirmation ? SubscriptionStatus::PENDING : SubscriptionStatus::ACTIVE;
    }

    public bool $isSendable {
        get => $this->status === SubscriptionStatus::ACTIVE;
    }

    /** Idempotent: confirming twice (link scanners, retries) is a no-op. */
    public function confirm(): bool
    {
        if ($this->status !== SubscriptionStatus::PENDING) {
            return false;
        }
        $this->status = SubscriptionStatus::ACTIVE;
        $this->confirmedAt = new \DateTimeImmutable();

        return true;
    }

    public function recordSend(): void
    {
        if (!$this->isSendable) { throw new \LogicException("Permission is not active."); }
        $this->lastSentAt = new \DateTimeImmutable();
        if ($this->audience->kind === AudienceKind::ONE_TIME) {
            $this->status = SubscriptionStatus::USED;
        }
    }

    /** Idempotent. Returns false when already unsubscribed, so callers log one Activity, not one per click. */
    public function unsubscribe(ConsentSource $source): bool
    {
        if ($this->status === SubscriptionStatus::UNSUBSCRIBED) {
            return false;
        }
        $this->status = SubscriptionStatus::UNSUBSCRIBED;
        $this->unsubscribedAt = new \DateTimeImmutable();
        $this->unsubscribeSource = $source;

        return true;
    }

    /**
     * Only a fresh, explicit opt-in may reverse an unsubscribe; imports and Brevo syncs never do.
     * Goes back through confirmation where the audience requires it.
     */
    public function resubscribe(ConsentSource $source, ?string $evidence): void
    {
        if (!in_array($source, [ConsentSource::SIGNUP_FORM, ConsentSource::MANUAL], true)) {
            throw new \LogicException(sprintf('%s cannot resubscribe %s to %s', $source->value, $this->contact, $this->audience->code));
        }
        $this->status = $this->audience->requiresConfirmation ? SubscriptionStatus::PENDING : SubscriptionStatus::ACTIVE;
        $this->evidence = $evidence;
        $this->consentVersion = bin2hex(random_bytes(16));
        $this->confirmedAt = null;
        $this->unsubscribedAt = null;
        $this->unsubscribeSource = null;
    }
}
