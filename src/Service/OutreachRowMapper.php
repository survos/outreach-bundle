<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Service;

final class OutreachRowMapper
{
    private const TRIGGER_TAGS = ['outreach'];

    /**
     * @var array<string, array<int, string>>
     */
    private const FIELD_ALIASES = [
        'first_name' => ['first_name', 'firstname', 'first', 'given_name', 'givenname'],
        'last_name' => ['last_name', 'lastname', 'last', 'surname', 'family_name', 'familyname'],
        'organization' => ['organization', 'org', 'institution', 'company', 'affiliation'],
        'email' => ['email', 'email_address', 'e_mail', 'mail'],
        'phone' => ['phone', 'telephone', 'phone_number', 'contact_phone'],
        'website' => ['website', 'url', 'site', 'web'],
        'job_title' => ['job_title', 'title', 'position', 'role'],
        'notes' => ['notes', 'note', 'comments', 'comment'],
    ];

    /**
     * @param array<int, string> $tags
     */
    public function shouldNormalizeForTags(array $tags): bool
    {
        foreach ($tags as $tag) {
            $tag = trim(strtolower($tag));
            if (in_array($tag, self::TRIGGER_TAGS, true) || str_starts_with($tag, 'outreach:')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $tags
     * @return array<string, mixed>
     */
    public function normalizeRow(array $row, array $tags = []): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$this->normalizeKey((string) $key)] = $value;
        }

        $mapped = [];
        foreach (self::FIELD_ALIASES as $target => $aliases) {
            foreach ($aliases as $alias) {
                if (!array_key_exists($alias, $normalized)) {
                    continue;
                }

                $value = $this->normalizeValue($normalized[$alias]);
                if ($value !== null) {
                    $mapped[$target] = $value;
                    break;
                }
            }
        }

        $rowTags = $this->extractTags($normalized['tags'] ?? null);
        $eventTags = $this->filterTags($tags);
        $mergedTags = array_values(array_unique(array_merge($rowTags, $eventTags)));
        if ($mergedTags !== []) {
            sort($mergedTags);
            $mapped['tags'] = $mergedTags;
        }

        return $mapped;
    }

    private function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? $key;

        return trim($key, '_');
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
            return $value !== '' ? $value : null;
        }

        if (is_scalar($value)) {
            $string = trim((string) $value);
            return $string !== '' ? $string : null;
        }

        if (is_array($value)) {
            return $value;
        }

        return null;
    }

    /**
     * @param array<int, string> $tags
     * @return array<int, string>
     */
    private function filterTags(array $tags): array
    {
        $filtered = [];
        foreach ($tags as $tag) {
            $tag = trim((string) $tag);
            if ($tag === '' || $tag === 'outreach' || str_starts_with($tag, 'format:') || str_starts_with($tag, 'source:')) {
                continue;
            }
            $filtered[] = $tag;
        }

        return array_values(array_unique($filtered));
    }

    /**
     * @return array<int, string>
     */
    private function extractTags(mixed $value): array
    {
        if (is_string($value)) {
            $parts = array_map('trim', explode(',', $value));
            return array_values(array_filter($parts, static fn (string $tag): bool => $tag !== ''));
        }

        if (is_array($value)) {
            $tags = [];
            foreach ($value as $tag) {
                $tag = trim((string) $tag);
                if ($tag !== '') {
                    $tags[] = $tag;
                }
            }

            return array_values(array_unique($tags));
        }

        return [];
    }
}
