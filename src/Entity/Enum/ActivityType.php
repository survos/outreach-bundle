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
}
