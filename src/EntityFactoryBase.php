<?php

namespace Drupal\wmdummy_data;

use Drupal\wmdummy_data\Faker\Provider\DrupalEntity;
use Drupal\wmdummy_data\Faker\Provider\Html;
use Drupal\wmdummy_data\Faker\Provider\RandomElementWeight;
use Drupal\wmdummy_data\Faker\Provider\VimeoVideo;
use Drupal\wmdummy_data\Faker\Provider\YouTubeVideo;
use Drupal\wmmodel_factory\EntityFactoryBase as EntityFactoryBaseBase;
use Faker\Generator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @property Generator|DrupalEntity|Html|RandomElementWeight|VimeoVideo|YouTubeVideo faker
 */
abstract class EntityFactoryBase extends EntityFactoryBaseBase
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
