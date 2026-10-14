<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Menu;

use Survos\MenuBundle\Attribute\AsMenuListener;
use Survos\MenuBundle\Event\MenuEvent;
use Survos\MenuBundle\MenuSlot;
use Survos\OutreachBundle\Entity\Activity;
use Survos\OutreachBundle\Entity\Audience;
use Survos\OutreachBundle\Entity\Contact;
use Survos\OutreachBundle\Entity\Organization;
use Survos\OutreachBundle\Entity\Subscription;
use Survos\TablerBundle\Menu\AbstractAdminMenuSubscriber;

class OutreachMenuSubscriber extends AbstractAdminMenuSubscriber
{
    protected function getLabel(): string
    {
        return 'Outreach';
    }

    protected function getResourceClasses(): array
    {
        return [
            'Organizations' => Organization::class,
            'Contacts' => Contact::class,
            'Activities' => Activity::class,
            'Audiences' => Audience::class,
            'Subscriptions' => Subscription::class,
        ];
    }

    #[AsMenuListener(MenuSlot::ADMIN_NAVBAR_MENU)]
    public function onAdminNavbarMenu(MenuEvent $event): void
    {
        $this->buildAdminMenu($event);
    }
}
