<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Model;

use Survos\OutreachBundle\Entity\Contact;
use Survos\OutreachBundle\Entity\Organization;

final readonly class ConferenceRegistrantUpsertResult
{
    public function __construct(
        public Organization $organization,
        public Contact $contact,
        public bool $organizationCreated,
        public bool $contactCreated,
    ) {
    }
}
