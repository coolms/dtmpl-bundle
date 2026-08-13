<?php

declare(strict_types=1);

namespace CoolMS\DtmplBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Template Configuration.
 *
 * Defines configuration structure for template.yaml
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dtmpl');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('template_base_path')
            ->defaultValue('%kernel.project_dir%/templates')
            ->cannotBeEmpty()
            ->end()
            ->arrayNode('extensions')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->children()
            ->scalarNode('mime_type')
            ->isRequired()
            ->cannotBeEmpty()
            ->end()
            ->booleanNode('enabled')
            ->defaultTrue()
            ->end()
            ->end()
            ->end()
            ->end()
            // Theme-partial path per widget key (and sub-key, e.g. `embed.audio`).
            // The single place to repoint a widget's template; a renderer falls
            // back to its built-in default for any key omitted here.
            ->arrayNode('widget_templates')
            ->useAttributeAsKey('key')
            ->scalarPrototype()->cannotBeEmpty()->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
