<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait TaggableTrait
{
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $tags = null;

    public function getTags(): array
    {
        return $this->tags ?? [];
    }

    public function setTags(array $tags): void
    {
        $tags = array_values(array_unique(array_filter(array_map(
            static fn (mixed $tag): string => trim((string) $tag),
            $tags,
        ))));

        $this->tags = $tags === [] ? null : $tags;
    }

    public function addTag(string $tag): void
    {
        $tag = trim($tag);
        if ($tag === '') {
            return;
        }

        $tags = $this->getTags();
        if (!in_array($tag, $tags, true)) {
            $tags[] = $tag;
            sort($tags);
            $this->tags = $tags;
        }
    }

    public function hasTag(string $tag): bool
    {
        return in_array(trim($tag), $this->getTags(), true);
    }
}
