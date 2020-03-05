<?php

namespace Drupal\wmdummy_data;

use Drupal\wmdummy_data\Faker\Provider\DrupalEntity;
use Drupal\wmdummy_data\Faker\Provider\RandomElementWeight;
use Drupal\wmdummy_data\Faker\Provider\VimeoVideo;
use Drupal\wmdummy_data\Faker\Provider\YouTubeVideo;
use Drupal\wmmodel_factory\EntityFactoryBase;
use Faker\Generator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @property Generator|DrupalEntity|RandomElementWeight|VimeoVideo|YouTubeVideo faker
 */
abstract class DummyEntityFactoryBase extends EntityFactoryBase
{
    public static function create(
        ContainerInterface $container,
        array $configuration,
        $pluginId,
        $pluginDefinition
    ) {
        $instance = new static($configuration, $pluginId, $pluginDefinition);
        $instance->faker = $container->get('wmdummy_data.faker.generator');

        return $instance;
    }
}
