<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity\Enum;

/**
 * Global, audience-independent email state for a contact.
 *
 * DO_NOT_EMAIL and BOUNCED suppress every send regardless of subscriptions.
 * Per-audience permission lives on Subscription.
 */
enum ContactEmailStatus: string
{
    case UNKNOWN = 'unknown';
    case OK_TO_EMAIL = 'ok_to_email';
    /** @deprecated one-time permission is per audience: Subscription with AudienceKind::ONE_TIME, status USED */
    case EMAILED_ONCE = 'emailed_once';
    case DO_NOT_EMAIL = 'do_not_email';
    case BOUNCED = 'bounced';

    public function suppressesAll(): bool
    {
        return $this === self::DO_NOT_EMAIL || $this === self::BOUNCED;
    }
}
