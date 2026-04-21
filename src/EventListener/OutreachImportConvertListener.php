<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\EventListener;

use Survos\ImportBundle\Event\ImportConvertRowEvent;
use Survos\OutreachBundle\Service\OutreachRowMapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class OutreachImportConvertListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly OutreachRowMapper $rowMapper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ImportConvertRowEvent::class => 'onImportConvertRow',
        ];
    }

    public function onImportConvertRow(ImportConvertRowEvent $event): void
    {
        if ($event->row === null || !$this->rowMapper->shouldNormalizeForTags($event->tags)) {
            return;
        }

        $event->row = $this->rowMapper->normalizeRow($event->row, $event->tags);
    }
}
