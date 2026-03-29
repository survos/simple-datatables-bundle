<?php

namespace Survos\SimpleDatatables;

use Survos\CoreBundle\Traits\HasAssetMapperTrait;
use Survos\SimpleDatatables\Components\SimpleDatatablesComponent;
use Survos\SimpleDatatables\Components\ItemGridComponent;
use Survos\SimpleDatatables\Twig\TwigExtension;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\StimulusBundle\Twig\StimulusTwigExtension;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Twig\Environment;

class SurvosSimpleDatatablesBundle extends AbstractBundle
{
    use HasAssetMapperTrait;

    public const ASSET_NAMESPACE = '@survos/simple-datatables';

    /**
     * @param array<mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {

        if (class_exists(Environment::class) && class_exists(StimulusTwigExtension::class)) {
            $builder
                ->setDefinition('survos.simple_datatables_bundle', new Definition(TwigExtension::class))
                ->addTag('twig.extension')
                ->setPublic(false)
            ;
        }

        $builder->register(SimpleDatatablesComponent::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->setArgument('$stimulusController', $config['stimulus_controller'])
//            ->setArgument('$registry', new Reference('doctrine')) // should be optional
        ;

        $builder->register(ItemGridComponent::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        // since the configuration is short, we can add it here
        $definition->rootNode()
            ->children()
            ->scalarNode('stimulus_controller')->defaultValue('@survos/simple-datatables-bundle/table')->end()
            ->booleanNode('per_page')->defaultValue(10)->end()
            ->booleanNode('searchable')->defaultValue(true)->end()
            ->scalarNode('fixed_height')->defaultValue(true)->end()
            ->end();

        ;
    }
}
