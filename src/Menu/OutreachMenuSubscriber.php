<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Menu;

use Survos\OutreachBundle\Entity\Activity;
use Survos\OutreachBundle\Entity\Contact;
use Survos\OutreachBundle\Entity\Organization;
use Survos\TablerBundle\Event\MenuEvent;
use Survos\TablerBundle\Menu\AbstractAdminMenuSubscriber;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class OutreachMenuSubscriber extends AbstractAdminMenuSubscriber
{
    protected function getLabel(): string { return 'Outreach'; }

    protected function getResourceClasses(): array
    {
        return [
            'Organizations' => Organization::class,
            'Contacts'      => Contact::class,
            'Activities'    => Activity::class,
        ];
    }

    #[AsEventListener(event: MenuEvent::ADMIN_NAVBAR_MENU)]
    public function onAdminNavbarMenu(MenuEvent $event): void
    {
        $this->buildAdminMenu($event);
    }
}
