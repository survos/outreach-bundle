<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Survos\OutreachBundle\Entity\Enum\ActivityType;
use Survos\OutreachBundle\Entity\Enum\ConsentSource;
use Survos\OutreachBundle\Entity\Enum\ContactEmailStatus;
use Survos\OutreachBundle\Entity\Subscription;
use Survos\OutreachBundle\Service\ConsentLinks;
use Survos\OutreachBundle\Service\ConsentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class ConsentController
{
    public function __construct(private EntityManagerInterface $em, private ConsentLinks $links, private ConsentManager $consent, private Environment $twig)
    {
    }

    #[Route('/{action}/{token}', name: 'outreach_consent', requirements: ['action' => 'confirm|unsubscribe|stop', 'token' => '[0-9]+\.[a-z]+\.[0-9]+\.[a-f0-9-]+\.[a-f0-9]{64}'], methods: ['GET', 'POST'])]
    public function __invoke(string $action, string $token, Request $request): Response
    {
        $subscription = $this->em->find(Subscription::class, (int) explode('.', $token)[0]);
        if (!$subscription || !$this->links->valid($subscription, $action, $token)) {
            return new Response('This link is invalid or has expired.', 400, ['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
        }
        // GET only renders a form: email scanners must not confirm or withdraw consent.
        $done = $request->isMethod('POST');
        if ($done) {
            match ($action) {
                'confirm' => $this->consent->confirm($subscription),
                'unsubscribe' => $this->consent->unsubscribe($subscription),
                'stop' => $this->consent->suppress($subscription->contact, ContactEmailStatus::DO_NOT_EMAIL, ActivityType::UNSUBSCRIBE, ConsentSource::UNSUBSCRIBE_LINK),
            };
        }

        return new Response($this->twig->render('@SurvosOutreach/consent.html.twig', [
            'action' => $action, 'audience' => $subscription->audience->name, 'done' => $done,
            'confirmed' => $subscription->isSendable,
            'suppressed' => $subscription->contact->getEmailStatus()->suppressesAll(),
        ]), 200, ['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer', 'X-Robots-Tag' => 'noindex, nofollow']);
    }
}
