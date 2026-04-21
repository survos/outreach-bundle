<?php

declare(strict_types=1);

namespace Survos\OutreachBundle;

use Survos\OutreachBundle\Command\OutreachImportJsonlCommand;
use Survos\OutreachBundle\EventListener\OutreachImportConvertListener;
use Survos\OutreachBundle\Service\ConferenceRegistrantUpserter;
use Survos\OutreachBundle\Service\OrganizationKeyGuesser;
use Survos\OutreachBundle\Service\OutreachRowMapper;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SurvosOutreachBundle extends AbstractBundle
{
    protected string $extensionAlias = 'survos_outreach';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
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

        if ($builder->hasExtension('doctrine')) {
            $builder->prependExtensionConfig('doctrine', [
                'orm' => [
                    'mappings' => [
                        'SurvosOutreachBundle' => [
                            'is_bundle' => false,
                            'type' => 'attribute',
                            'dir' => $entityDir,
                            'prefix' => 'Survos\\OutreachBundle\\Entity',
                            'alias' => 'Outreach',
                        ],
                    ],
                ],
            ]);
        }

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

        $services->set(OutreachImportConvertListener::class)
            ->public();

        $services->set(OutreachImportJsonlCommand::class)
            ->tag('console.command')
            ->public();
    }
}
