<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity\Enum;

enum ContactEmailStatus: string
{
    case UNKNOWN = 'unknown';
    case OK_TO_EMAIL = 'ok_to_email';
    case EMAILED_ONCE = 'emailed_once';
    case DO_NOT_EMAIL = 'do_not_email';
    case BOUNCED = 'bounced';
}
