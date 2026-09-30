<?php

declare(strict_types=1);

namespace Survos\OutreachBundle;

use Survos\Kit\AbstractSurvosBundle;
use Survos\Kit\Traits\HasConfigurableRoutes;
use Survos\Kit\Traits\HasDoctrineEntities;
use Survos\OutreachBundle\Service\ConsentLinks;
use Survos\OutreachBundle\Service\ConsentManager;
use Survos\OutreachBundle\EventListener\OutreachImportConvertListener;
use Survos\OutreachBundle\Service\ConferenceRegistrantUpserter;
use Survos\OutreachBundle\Service\OrganizationKeyGuesser;
use Survos\OutreachBundle\Service\OutreachRowMapper;
use Survos\OutreachBundle\Service\SendPolicy;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;


// Symfony\Component\HttpKernel\Bundle\Bundle <-- Flex auto-registration marker (see Survos\Kit\AbstractSurvosBundle)
final class SurvosOutreachBundle extends AbstractSurvosBundle
{
    use HasConfigurableRoutes;
    use HasDoctrineEntities;

    protected function doctrineAlias(): string { return 'Outreach'; }

    protected string $extensionAlias = 'survos_outreach';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $this->addRouteOptions($definition->rootNode()->children(), '/outreach', false);
        $definition->rootNode()
            ->children()
                ->scalarNode('signing_secret')->defaultValue('%kernel.secret%')->end()
                ->scalarNode('public_base_url')->defaultValue('')->end()
                ->arrayNode('personal_email_domains')
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        'aol.com',
                        'comcast.net',
                        'gmail.com',
                        'hotmail.com',
                        'icloud.com',
                        'mac.com',
                        'me.com',
                        'msn.com',
                        'outlook.com',
                        'proton.me',
                        'protonmail.com',
                        'verizon.net',
                        'yahoo.com',
                    ])
                ->end()
                ->scalarNode('domain_key_prefix')->defaultValue('domain')->end()
                ->scalarNode('organization_key_prefix')->defaultValue('org')->end()
            ->end();
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $entityDir = \dirname(__DIR__) . '/src/Entity';

        parent::prependExtension($container, $builder);

        if ($builder->hasExtension('api_platform')) {
            $builder->prependExtensionConfig('api_platform', [
                'mapping' => [
                    'paths' => [$entityDir],
                ],
            ]);
        }
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);
        $services = $container->services()->defaults()->autowire()->autoconfigure();

        $services->set(OrganizationKeyGuesser::class)
            ->arg('$personalEmailDomains', $config['personal_email_domains'])
            ->arg('$domainKeyPrefix', $config['domain_key_prefix'])
            ->arg('$organizationKeyPrefix', $config['organization_key_prefix'])
            ->public();

        $services->set(OutreachRowMapper::class)
            ->public();

        $services->set(ConferenceRegistrantUpserter::class)
            ->public();

        $services->set(SendPolicy::class)
            ->public();

        $services->set(OutreachImportConvertListener::class)
            ->public();

        $services->set(ConsentManager::class);
        $services->set(ConsentLinks::class)
            ->arg('$secret', $config['signing_secret'])
            ->arg('$publicBaseUrl', $config['public_base_url'])
            ->arg('$routePrefix', $config['route_prefix']);

        if (class_exists(\Survos\TablerBundle\Event\MenuEvent::class)) {
            $services->set(\Survos\OutreachBundle\Menu\OutreachMenuSubscriber::class)
                ->autowire()
                ->autoconfigure();
        }
    }
}
