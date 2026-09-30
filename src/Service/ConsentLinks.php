<?php

declare(strict_types=1);
namespace Survos\OutreachBundle\Service;

use Survos\OutreachBundle\Entity\Subscription;

/** Purpose-bound bearer links: unsubscribe never expires; confirmation expires in seven days. */
final readonly class ConsentLinks
{
    public function __construct(private string $secret, private string $publicBaseUrl, private string $routePrefix = '/outreach') {}

    public function token(Subscription $subscription, string $action, ?int $now = null): string
    {
        if ($subscription->id === null || !in_array($action, ['confirm', 'unsubscribe', 'stop'], true)) {
            throw new \InvalidArgumentException('Persist the subscription and use a supported consent action.');
        }
        if (strlen($this->secret) < 32) { throw new \LogicException('Configure an outreach signing secret of at least 32 characters.'); }
        $payload = implode('.', [$subscription->id, $action, $action === 'confirm' ? ($now ?? time()) + 604800 : 0, $action === 'confirm' ? $subscription->consentVersion : '-']);
        return $payload.'.'.$this->signature($subscription, $payload);
    }

    public function valid(Subscription $subscription, string $action, string $token, ?int $now = null): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 5 || strlen($this->secret) < 32 || !in_array($action, ['confirm', 'unsubscribe', 'stop'], true)) { return false; }
        [$id, $purpose, $expires, $version, $signature] = $parts;
        if ((string) $subscription->id !== $id || $purpose !== $action || !ctype_digit($expires)) { return false; }
        if ($action === 'confirm' && ((int) $expires <= ($now ?? time()) || $version !== $subscription->consentVersion)) { return false; }
        return hash_equals($this->signature($subscription, implode('.', array_slice($parts, 0, 4))), $signature);
    }

    public function url(Subscription $subscription, string $action): string
    {
        if (!str_starts_with($this->publicBaseUrl, 'https://') || !parse_url($this->publicBaseUrl, PHP_URL_HOST)) {
            throw new \LogicException('Configure public_base_url with the canonical HTTPS origin.');
        }
        return rtrim($this->publicBaseUrl, '/').'/'.trim($this->routePrefix, '/').'/'.$action.'/'.$this->token($subscription, $action);
    }

    private function signature(Subscription $subscription, string $payload): string
    {
        // Changing the destination email invalidates all old links.
        return hash_hmac('sha256', 'outreach:v1:'.$payload.':'.$subscription->contact->getEmail(), $this->secret);
    }
}
