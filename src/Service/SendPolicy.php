<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Survos\OutreachBundle\Entity\Audience;
use Survos\OutreachBundle\Entity\Contact;
use Survos\OutreachBundle\Entity\Subscription;

/**
 * The one place that decides whether a contact may be emailed for an audience.
 * Every send path (Symfony Mailer, Brevo list push, bulk commands) asks here at send time,
 * never from a cached list.
 */
final class SendPolicy
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function canSend(Contact $contact, Audience $audience): bool
    {
        return $this->whyNot($contact, $audience) === null;
    }

    /** Null when sending is allowed, otherwise a reason fit for logs and the admin UI. */
    public function whyNot(Contact $contact, Audience $audience): ?string
    {
        if ($contact->getEmail() === null) {
            return 'no email address';
        }
        if ($contact->getEmailStatus()->suppressesAll()) {
            return sprintf('contact is %s', $contact->getEmailStatus()->value);
        }
        $subscription = $this->subscription($contact, $audience);
        if ($subscription === null) {
            return sprintf('no permission for %s', $audience->code);
        }
        if (!$subscription->isSendable) {
            return sprintf('%s subscription is %s', $audience->code, $subscription->status->value);
        }

        return null;
    }

    public function subscription(Contact $contact, Audience $audience): ?Subscription
    {
        return $this->em->getRepository(Subscription::class)->findOneBy([
            'contact' => $contact,
            'audience' => $audience,
        ]);
    }
}
