# survos/outreach-bundle

Small outreach CRM bundle for conference and prospect management.

It is intentionally narrow:

- organizations
- contacts
- activities
- external system footprints

This is not a general CRM and it does not try to manage tenants, invoicing, or full marketing automation.

## Use Cases

- pre-load conference attendee lists
- track one-time outreach permission and follow-up status
- record booth conversations and demos
- track whether an organization already uses PastPerfect, Omeka, or another system
- attach lightweight tags such as `marac-2026` or `ssai-tenant:middleburg-historical-society`

## Model

The bundle ships four Doctrine entities and exposes them as ApiPlatform resources:

- `Organization`
- `Contact`
- `Activity`
- `OrganizationSystem`

## Personal Email Heuristic

The included `ConferenceRegistrantUpserter` service can infer an organization key from a CSV row:

- if the email domain is not personal, use the domain as the organization key
- if the email domain looks personal (`gmail.com`, `yahoo.com`, etc.), fall back to the provided organization name

Examples:

- `snallen@vuu.edu` => `domain:vuu.edu`
- `fall@loc.gov` => `domain:loc.gov`
- `archival2010@gmail.com` + `New Brunswick Free Public Library Archive` => `org:new-brunswick-free-public-library-archive`

This is intentionally heuristic, not canonical truth.

## Installation

```bash
composer require survos/outreach-bundle:@dev
```

Then link it in local development using your normal monorepo flow.

## Configuration

The bundle is self-registering:

- Doctrine attribute mappings are registered automatically
- ApiPlatform mapping paths are registered automatically

Optional configuration:

```yaml
survos_outreach:
  personal_email_domains:
    - gmail.com
    - yahoo.com
```

## Services

### `OrganizationKeyGuesser`

Builds stable organization keys from email and/or organization name.

### `ConferenceRegistrantUpserter`

Upserts an `Organization` and `Contact` from an attendee row such as:

```php
[
    'First Name' => 'Selicia',
    'Last Name' => 'Allen',
    'Organization' => 'Virginia Union University',
    'Email' => 'snallen@vuu.edu',
]
```

Optional event tag example:

```php
$upserter->upsert($row, ['marac-2026']);
```

That tag is added to both the organization and the contact.

## Workflow

Recommended flow for conference rosters:

```bash
bin/console import:convert registrants.csv --tags=outreach,marac-2026
bin/console outreach:import:jsonl var/data/registrants.jsonl --tag=marac-2026
```

When `import:convert` sees the `outreach` tag, the bundle listens to row events and maps fields like:

- `First Name` -> `first_name`
- `Last Name` -> `last_name`
- `Organization` -> `organization`
- `Email` -> `email`

The second command imports the normalized JSONL into `Organization` and `Contact`, deduplicating organizations by the configured email-domain heuristic.
