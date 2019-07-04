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

    /**
     * @deprecated in wmdummy_data:1.3.0 and is removed from wmdummy_data:2.0.0.
     *   Use DummyDataBase::getEntityType(), DummyDataBase::getBundle()
     *   or DummyDataBase::getPreset() instead.
     */
    public function getKey(): string
    {
        @trigger_error('DummyDataBase::getKey() is @deprecated in wmdummy_data:1.3.0 and is removed from wmdummy_data:2.0.0. Use DummyDataBase::getEntityType(), DummyDataBase::getBundle() or DummyDataBase::getPreset() instead.', E_USER_DEPRECATED);

        if (isset($this->definition['entity_type'], $this->definition['bundle'])) {
            return implode('.', [
                $this->pluginDefinition['entity_type'],
                $this->pluginDefinition['bundle'],
                $this->pluginDefinition['preset'],
            ]);
        }

        return $this->pluginDefinition['id'];
    }

    public function getLangcode(): string
    {
        @trigger_error('DummyDataBase::getLangcode() is @deprecated in wmdummy_data:1.3.0 and is removed from wmdummy_data:2.0.0.', E_USER_DEPRECATED);

        return $this->pluginDefinition['langcode'] ?? null;
    }
}
