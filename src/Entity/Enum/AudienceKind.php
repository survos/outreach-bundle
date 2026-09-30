<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity\Enum;

/**
 * What kind of permission membership in an audience represents.
 */
enum AudienceKind: string
{
    /** A single permitted message, e.g. the MARC conference introduction. Consumed on send. */
    case ONE_TIME = 'one_time';
    /** Ongoing direct correspondence with a partner or prospect. */
    case RELATIONSHIP = 'relationship';
    /** Bulk mail the contact opted into, e.g. the Museado Foundation newsletter. Requires confirmation. */
    case NEWSLETTER = 'newsletter';
    /** Ongoing product/site news; consent is independent of site follows. */
    case ANNOUNCEMENTS = 'announcements';
}
