<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Service;

use Symfony\Component\String\Slugger\SluggerInterface;

final class OrganizationKeyGuesser
{
    /**
     * @param array<int, string> $personalEmailDomains
     */
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly array $personalEmailDomains,
        private readonly string $domainKeyPrefix = 'domain',
        private readonly string $organizationKeyPrefix = 'org',
    ) {
    }

    public function guess(?string $email, ?string $organizationName): string
    {
        $email = $this->cleanString($email);
        $organizationName = $this->cleanString($organizationName);

        $domain = $email !== null ? $this->extractDomain($email) : null;
        if ($domain !== null && !$this->isPersonalDomain($domain)) {
            return sprintf('%s:%s', $this->domainKeyPrefix, $domain);
        }

        if ($organizationName !== null) {
            $slug = strtolower($this->slugger->slug($organizationName)->toString());
            if ($slug !== '') {
                return sprintf('%s:%s', $this->organizationKeyPrefix, $slug);
            }
        }

        if ($domain !== null) {
            return sprintf('%s:%s', $this->domainKeyPrefix, $domain);
        }

        throw new \InvalidArgumentException('Cannot infer an organization key without an email or organization name.');
    }

    public function isPersonalDomain(string $domain): bool
    {
        return in_array(strtolower(trim($domain)), $this->normalizedPersonalDomains(), true);
    }

    public function extractDomain(string $email): ?string
    {
        $email = strtolower(trim($email));
        if ($email === '' || !str_contains($email, '@')) {
            return null;
        }

        $domain = substr($email, (int) strrpos($email, '@') + 1);
        $domain = trim($domain);

        return $domain !== '' ? $domain : null;
    }

    /**
     * @return array<int, string>
     */
    private function normalizedPersonalDomains(): array
    {
        return array_values(array_unique(array_map(
            static fn (string $domain): string => strtolower(trim($domain)),
            $this->personalEmailDomains,
        )));
    }

    private function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
