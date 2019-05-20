<?php

namespace Drupal\wmdummy_data;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\wmdummy_data\Faker\Provider\DrupalEntity;
use Drupal\wmdummy_data\Faker\Provider\RandomElementWeight;
use Drupal\wmdummy_data\Faker\Provider\VimeoVideo;
use Drupal\wmdummy_data\Faker\Provider\YouTubeVideo;
use Faker\Generator as Faker;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class DummyDataBase extends PluginBase implements DummyDataInterface, ContainerFactoryPluginInterface
{
    /** @var Faker|DrupalEntity|RandomElementWeight|YouTubeVideo|VimeoVideo */
    protected $faker;

    public function __construct(
        array $configuration,
        string $pluginId,
        $pluginDefinition,
        Faker $faker
    ) {
        parent::__construct($configuration, $pluginId, $pluginDefinition);
        $this->faker = $faker;
    }

    public static function create(
        ContainerInterface $container,
        array $configuration,
        $pluginId,
        $pluginDefinition
    ) {
        return new static(
            $configuration,
            $pluginId,
            $pluginDefinition,
            $container->get('wmdummy_data.faker.generator')
        );
    }

    abstract public function generate(): array;

    public function getKey(): string
    {
        return $this->pluginDefinition['id'];
    }

    public function getLangcode(): string
    {
        return $this->pluginDefinition['langcode'];
    }
}
