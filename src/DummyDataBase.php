<?php

namespace Drupal\wmdummy_data;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\wmdummy_data\Service\Generator\DummyDataGenerator;
use Faker\Generator as Faker;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class DummyDataBase extends PluginBase implements DummyDataInterface, ContainerFactoryPluginInterface
{
    /** @var Faker */
    protected $faker;
    /** @var DummyDataGenerator */
    protected $dummyDataGenerator;

    public function __construct(
        array $configuration,
        string $pluginId,
        $pluginDefinition,
        Faker $faker,
        DummyDataGenerator $dummyDataGenerator
    ) {
        parent::__construct($configuration, $pluginId, $pluginDefinition);
        $this->faker = $faker;
        $this->dummyDataGenerator = $dummyDataGenerator;
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
            $container->get('wmdummy_data.faker'),
            $container->get('wmdummy_data.dummy_data_generator')
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

    public function generateEntity(string $entityType, string $bundle, string $preset = DummyDataInterface::PRESET_DEFAULT, string $langcode = null): array
    {
        $entity = $this->dummyDataGenerator->generateDummyData($entityType, $bundle, $preset, $langcode);

        if ($entity instanceof ContentEntityInterface) {
            return [
                'entity' => $entity,
            ];
        }

        return [];
    }

    public function nullable($value, int $chanceOfGettingTrue = 50)
    {
        if ($this->faker->boolean($chanceOfGettingTrue)) {
            return $value;
        }

        return null;
    }
}
