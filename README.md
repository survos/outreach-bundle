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

## Consent groundwork (implementation checkpoint)

Announcements are separate audiences, for example `survos-announcements` and
`pressia-updates`, both using `AudienceKind::ANNOUNCEMENTS`. Each requires email
confirmation. Following a site, bookmarking a story, or importing a contact does
not grant this permission. API resources require `ROLE_ADMIN`.

`ConsentManager` records requests, confirmation, audience unsubscribe and global
outreach suppression as activities. `ConsentLinks` generates signed, purpose-bound
links. Confirmation expires after seven days and is invalidated by a fresh opt-in
or an address change. Unsubscribe links do not expire. GET renders a form; POST
changes permission so email link scanners do not change it accidentally.

The bundle now uses kit-bundle conventions. Consent routes are **disabled by
default** while delivery/provider integration remains unfinished. To enable them
in a consuming application:

```yaml
survos_outreach:
    routes_enabled: true
    public_base_url: 'https://pressia.org'
    signing_secret: '%env(OUTREACH_SIGNING_SECRET)%'
```

Use a stable secret of at least 32 characters. Allow anonymous access to the
configured `/outreach` prefix in the host security configuration: the signed link
authorizes the action. Keep locale prefixes disabled for these generated URLs.

Existing databases need a migration for `Subscription::consentVersion`: add a
nullable 32-character column, backfill each existing row with an independently
generated 16-byte random hex string, then make it non-nullable. Generate the
migration in the consuming app using its naming strategy; this bundle does not
automatically alter existing databases.

This checkpoint does **not** implement confirmation email dispatch, a send worker,
provider webhook synchronization, or campaign delivery. Those must enforce
`SendPolicy` at send time, including in long-running workers. Provider selection
is still under review. No mail is sent by enabling these services.

Tests from the monorepo root:

```bash
vendor/bin/phpunit --no-configuration --bootstrap bu/outreach-bundle/tests/bootstrap.php bu/outreach-bundle/tests
```
