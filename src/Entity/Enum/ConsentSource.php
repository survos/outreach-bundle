<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity\Enum;

/**
 * Where a subscription change came from. Kept with the evidence so every state is explainable.
 */
enum ConsentSource: string
{
    case IMPORT = 'import';
    case SIGNUP_FORM = 'signup_form';
    case CONFIRMATION_LINK = 'confirmation_link';
    case UNSUBSCRIBE_LINK = 'unsubscribe_link';
    case BREVO_WEBHOOK = 'brevo_webhook';
    case MANUAL = 'manual';
}
