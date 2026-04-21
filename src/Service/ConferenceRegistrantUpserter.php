<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Survos\OutreachBundle\Entity\Contact;
use Survos\OutreachBundle\Entity\Organization;
use Survos\OutreachBundle\Model\ConferenceRegistrantUpsertResult;

final class ConferenceRegistrantUpserter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrganizationKeyGuesser $organizationKeyGuesser,
        private readonly OutreachRowMapper $rowMapper,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string>|string|null $tags
     */
    public function upsert(array $row, array|string|null $tags = null, bool $flush = true, bool $dryRun = false): ConferenceRegistrantUpsertResult
    {
        $normalized = $this->rowMapper->normalizeRow($row, $this->normalizeTags($tags));

        $firstName = $this->nullableString($normalized['first_name'] ?? null);
        $lastName = $this->nullableString($normalized['last_name'] ?? null);
        $organizationName = $this->nullableString($normalized['organization'] ?? null);
        $email = $this->nullableString($normalized['email'] ?? null);

        if ($organizationName === null && $email === null) {
            throw new \InvalidArgumentException('Each registrant row needs at least an organization or an email.');
        }

        [$organization, $organizationCreated] = $this->resolveOrganization($organizationName, $email);
        [$contact, $contactCreated] = $this->resolveContact($organization, $firstName, $lastName, $email);

        if ($organizationName !== null && $organization->getName() === '') {
            $organization->setName($organizationName);
        }

        if ($organizationName !== null && $organization->getName() !== $organizationName) {
            $organization->setName($organizationName);
        }

        $contact->setOrganization($organization);
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
        $contact->setEmail($email);

        foreach ($this->normalizeTags($normalized['tags'] ?? []) as $tag) {
            $organization->addTag($tag);
            $contact->addTag($tag);
        }

        if (!$dryRun) {
            $this->entityManager->persist($organization);
            $this->entityManager->persist($contact);

            if ($flush) {
                $this->entityManager->flush();
            }
        }

        return new ConferenceRegistrantUpsertResult($organization, $contact, $organizationCreated, $contactCreated);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    /**
     * @return array{0: Organization, 1: bool}
     */
    private function resolveOrganization(?string $organizationName, ?string $email): array
    {
        $key = $this->organizationKeyGuesser->guess($email, $organizationName);

        $organization = $this->entityManager->getRepository(Organization::class)->findOneBy(['key' => $key]);
        if ($organization instanceof Organization) {
            return [$organization, false];
        }

        $organization = new Organization();
        $organization->setKey($key);
        $organization->setName($organizationName ?? $key);

        $domain = $email !== null ? $this->organizationKeyGuesser->extractDomain($email) : null;
        if ($domain !== null && !$this->organizationKeyGuesser->isPersonalDomain($domain)) {
            $organization->setWebsite('https://' . $domain);
        }

        return [$organization, true];
    }

    /**
     * @return array{0: Contact, 1: bool}
     */
    private function resolveContact(Organization $organization, ?string $firstName, ?string $lastName, ?string $email): array
    {
        $repository = $this->entityManager->getRepository(Contact::class);

        if ($email !== null) {
            $existing = $repository->findOneBy(['email' => strtolower($email)]);
            if ($existing instanceof Contact) {
                return [$existing, false];
            }
        }

        $criteria = [
            'organization' => $organization,
            'firstName' => $firstName,
            'lastName' => $lastName,
        ];

        $existing = $repository->findOneBy($criteria);
        if ($existing instanceof Contact) {
            return [$existing, false];
        }

        return [new Contact(), true];
    }

    /**
     * @param array<int, string>|string|null $tags
     * @return array<int, string>
     */
    private function normalizeTags(array|string|null $tags): array
    {
        if ($tags === null) {
            return [];
        }

        if (is_string($tags)) {
            $tags = [$tags];
        }

        $normalized = [];
        foreach ($tags as $tag) {
            $tag = trim((string) $tag);
            if ($tag !== '') {
                $normalized[] = $tag;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
