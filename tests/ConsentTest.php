<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Survos\OutreachBundle\Entity\Audience;
use Survos\OutreachBundle\Entity\Contact;
use Survos\OutreachBundle\Entity\Enum\AudienceKind;
use Survos\OutreachBundle\Entity\Enum\ConsentSource;
use Survos\OutreachBundle\Entity\Subscription;
use Survos\OutreachBundle\Service\ConsentLinks;

final class ConsentTest extends TestCase
{
    private function subscription(): Subscription
    {
        $contact = new Contact();
        $contact->setEmail('reader@example.org');
        $subscription = new Subscription($contact, new Audience('pressia-updates', AudienceKind::ANNOUNCEMENTS), ConsentSource::SIGNUP_FORM);
        (new ReflectionProperty(Subscription::class, 'id'))->setValue($subscription, 42);

        return $subscription;
    }

    public function testAnnouncementsRequireConfirmationAndUnsubscribeIsIdempotent(): void
    {
        $subscription = $this->subscription();
        self::assertFalse($subscription->isSendable);
        self::assertTrue($subscription->confirm());
        self::assertFalse($subscription->confirm());
        self::assertTrue($subscription->isSendable);
        self::assertTrue($subscription->unsubscribe(ConsentSource::UNSUBSCRIBE_LINK));
        self::assertFalse($subscription->unsubscribe(ConsentSource::UNSUBSCRIBE_LINK));
        self::assertFalse($subscription->confirm());
        self::assertFalse($subscription->isSendable);
        $this->expectException(LogicException::class);
        $subscription->recordSend();
    }

    public function testLinksBindPurposeExpiryAddressAndConsentGeneration(): void
    {
        $subscription = $this->subscription();
        $links = new ConsentLinks(str_repeat('x', 32), 'https://pressia.org');
        $confirm = $links->token($subscription, 'confirm', 100);
        $unsubscribe = $links->token($subscription, 'unsubscribe', 100);
        self::assertTrue($links->valid($subscription, 'confirm', $confirm, 101));
        self::assertFalse($links->valid($subscription, 'stop', $confirm, 101));
        self::assertFalse($links->valid($subscription, 'confirm', $confirm, 604900));
        self::assertTrue($links->valid($subscription, 'unsubscribe', $unsubscribe, PHP_INT_MAX));
        $subscription->resubscribe(ConsentSource::SIGNUP_FORM, 'New explicit request');
        self::assertFalse($links->valid($subscription, 'confirm', $confirm, 101));
        self::assertTrue($links->valid($subscription, 'unsubscribe', $unsubscribe, 101));
        $subscription->contact->setEmail('different@example.org');
        self::assertFalse($links->valid($subscription, 'unsubscribe', $unsubscribe, 101));
    }

    public function testImportCannotRestorePermission(): void
    {
        $subscription = $this->subscription();
        $subscription->unsubscribe(ConsentSource::UNSUBSCRIBE_LINK);
        $this->expectException(LogicException::class);
        $subscription->resubscribe(ConsentSource::IMPORT, 'CSV import');
    }
}
