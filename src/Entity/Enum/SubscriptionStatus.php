<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity\Enum;

enum SubscriptionStatus: string
{
    /** Signed up, confirmation not yet received (newsletter double opt-in). */
    case PENDING = 'pending';
    case ACTIVE = 'active';
    /** One-time permission has been used; nothing further may be sent to this audience. */
    case USED = 'used';
    case UNSUBSCRIBED = 'unsubscribed';
}
