<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity\Enum;

enum ActivityType: string
{
    case EMAIL = 'email';
    case CALL = 'call';
    case NOTE = 'note';
    case CONFERENCE = 'conference';
    case DEMO = 'demo';
    case VISIT = 'visit';
    // consent history: one Activity per Subscription or ContactEmailStatus change
    case CONSENT = 'consent';
    case UNSUBSCRIBE = 'unsubscribe';
    case BOUNCE = 'bounce';
    case COMPLAINT = 'complaint';
}
