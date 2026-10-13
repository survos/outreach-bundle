<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Survos\OutreachBundle\Entity\Activity;
use Survos\OutreachBundle\Entity\Audience;
use Survos\OutreachBundle\Entity\Contact;
use Survos\OutreachBundle\Entity\Enum\ActivityType;
use Survos\OutreachBundle\Entity\Enum\ConsentSource;
use Survos\OutreachBundle\Entity\Enum\ContactEmailStatus;
use Survos\OutreachBundle\Entity\Subscription;

final readonly class ConsentManager
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /** Imports can create contacts; they cannot use this opt-in API to grant ongoing consent. */
    public function request(Contact $contact, Audience $audience, ConsentSource $source, string $evidence): Subscription
    {
        if (!in_array($source, [ConsentSource::SIGNUP_FORM, ConsentSource::MANUAL], true) || '' === trim($evidence)) {
            throw new \InvalidArgumentException('An explicit opt-in and evidence are required.');
        }
        if ($contact->getEmailStatus()->suppressesAll()) {
            throw new \LogicException('Global suppression must be reviewed separately; an audience opt-in cannot clear it.');
        }
        $subscription = $this->em->getRepository(Subscription::class)->findOneBy(['contact' => $contact, 'audience' => $audience]);
        if ($subscription?->isSendable) {
            return $subscription;
        }
        if (null === $subscription) {
            $subscription = new Subscription($contact, $audience, $source);
            $subscription->evidence = $evidence;
            $this->em->persist($subscription);
        } else {
            $subscription->resubscribe($source, $evidence);
        }
        $this->audit($subscription, ActivityType::CONSENT, 'requested', $source);
        $this->em->flush();

        return $subscription;
    }

    public function confirm(Subscription $subscription): void
    {
        if (!$subscription->contact->getEmailStatus()->suppressesAll() && $subscription->confirm()) {
            $this->audit($subscription, ActivityType::CONSENT, 'confirmed', ConsentSource::CONFIRMATION_LINK);
            $this->em->flush();
        }
    }

    public function unsubscribe(Subscription $subscription, ConsentSource $source = ConsentSource::UNSUBSCRIBE_LINK): void
    {
        if ($subscription->unsubscribe($source)) {
            $this->audit($subscription, ActivityType::UNSUBSCRIBE, 'audience', $source);
            $this->em->flush();
        }
    }

    public function suppress(Contact $contact, ContactEmailStatus $status, ActivityType $type, ConsentSource $source): void
    {
        if (!$status->suppressesAll()) {
            throw new \InvalidArgumentException('Not a suppression status.');
        }
        // Never downgrade an explicit global opt-out to a bounce status.
        if (ContactEmailStatus::DO_NOT_EMAIL === $contact->getEmailStatus() || $contact->getEmailStatus() === $status) {
            return;
        }
        $contact->setEmailStatus($status);
        $activity = new Activity();
        $activity->setContact($contact);
        $activity->setType($type);
        $activity->setSubject('All outreach email');
        $activity->setOutcome($status->value);
        $activity->setPerformedBy($source->value);
        $this->em->persist($activity);
        $this->em->flush();
    }

    private function audit(Subscription $subscription, ActivityType $type, string $outcome, ConsentSource $source): void
    {
        $activity = new Activity();
        $activity->setContact($subscription->contact);
        $activity->setType($type);
        $activity->setSubject($subscription->audience->code);
        $activity->setOutcome($outcome);
        $activity->setPerformedBy($source->value);
        $this->em->persist($activity);
    }
}
