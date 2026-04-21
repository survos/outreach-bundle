<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity\Enum;

enum ActivityDirection: string
{
    case OUTBOUND = 'outbound';
    case INBOUND = 'inbound';
}
