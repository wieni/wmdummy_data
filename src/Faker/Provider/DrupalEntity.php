<?php

namespace Drupal\wmdummy_data\Faker\Provider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\wmdummy_data\DummyDataInterface;
use Drupal\wmdummy_data\Service\Generator\DummyDataGenerator;
use Faker\Generator;
use Faker\Provider\Base;

class DrupalEntity extends Base
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var DummyDataGenerator */
    protected $dummyDataGenerator;

    public function __construct(
        Generator $generator,
        EntityTypeManagerInterface $entityTypeManager,
        LanguageManagerInterface $languageManager,
        DummyDataGenerator $dummyDataGenerator
    ) {
        parent::__construct($generator);
        $this->entityTypeManager = $entityTypeManager;
        $this->languageManager = $languageManager;
        $this->dummyDataGenerator = $dummyDataGenerator;
    }

    public function drupalEntity(string $entityType, string $bundle, int $chanceOfCreatingNewEntity = 0, string $preset = DummyDataInterface::PRESET_DEFAULT, ?string $langcode = null): ?EntityInterface
    {
        $entities = $this->drupalEntities(
            $entityType,
            $bundle,
            1,
            $chanceOfCreatingNewEntity,
            $preset,
            $langcode
        );

        if (empty($entities)) {
            return null;
        }

        return reset($entities);
    }

    public function drupalEntities(string $entityType, string $bundle, int $amount, int $chanceOfCreatingNewEntity = 0, string $preset = DummyDataInterface::PRESET_DEFAULT, ?string $langcode = null): array
    {
        if ($amount === 0) {
            return [];
        }

        if ($this->generator->boolean($chanceOfCreatingNewEntity)) {
            return $this->getNewEntities($amount, $entityType, $bundle, $preset, $langcode);
        }

        $entities = $this->getExistingEntities($amount, $entityType, $bundle, $langcode);
        $amountShort = $amount - count($entities);

        if ($amountShort === 0 || $chanceOfCreatingNewEntity === 0) {
            return $entities;
        }

        return array_merge(
            $entities,
            $this->getNewEntities($amountShort, $entityType, $bundle, $preset, $langcode)
        );
    }

    protected function getNewEntities(int $amount, string $entityType, string $bundle, string $preset = DummyDataInterface::PRESET_DEFAULT, ?string $langcode = null): array
    {
        if ($amount === 0) {
            return [];
        }
        return array_map(
            function () use ($entityType, $bundle, $preset, $langcode) {
                return $this->dummyDataGenerator->generateDummyData(
                    $entityType,
                    $bundle,
                    $preset,
                    $langcode
                );
            },
            range(0, $amount)
        );
    }

    protected function getExistingEntities(int $amount, string $entityType, string $bundle, ?string $langcode = null): array
    {
        if ($amount === 0) {
            return [];
        }

        $langcode = $langcode ?? $this->languageManager->getDefaultLanguage()->getId();
        $typeDefinition = $this->entityTypeManager->getDefinition($entityType);
        $storage = $this->entityTypeManager->getStorage($entityType);

        $query = $storage->getQuery()
            ->sort($typeDefinition->getKey('id'), 'DESC')
            ->condition($typeDefinition->getKey('bundle'), $bundle)
            ->condition($typeDefinition->getKey('langcode'), $langcode)
            ->range(0, 100);

        $ids = $query->execute();
        $ids = array_combine($ids, $ids);

        if (empty($ids)) {
            return [];
        }

        $amount = min(count($ids), $amount);
        $random = array_rand($ids, $amount);
        if (!is_array($random)) {
            $random = [$random];
        }

        return array_map(
            function (string $id) use ($storage) {
                return $storage->load($id);
            },
            $random
        );
    }
}
