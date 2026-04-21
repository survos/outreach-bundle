<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity\Enum;

enum OrganizationSystemType: string
{
    case PASTPERFECT = 'pastperfect';
    case OMEKA_S = 'omeka_s';
    case OMEKA_CLASSIC = 'omeka_classic';
    case COLLECTIVE_ACCESS = 'collective_access';
    case ARCHIVESSPACE = 'archivesspace';
    case CUSTOM = 'custom';
    case UNKNOWN = 'unknown';
}
