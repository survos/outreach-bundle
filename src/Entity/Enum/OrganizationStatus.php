<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity\Enum;

enum OrganizationStatus: string
{
    case PROSPECT = 'prospect';
    case INVITED = 'invited';
    case ENGAGED = 'engaged';
    case DEMO_SCHEDULED = 'demo_scheduled';
    case CUSTOMER = 'customer';
    case NOT_A_FIT = 'not_a_fit';
}
