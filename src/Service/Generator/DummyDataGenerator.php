<?php

namespace Drupal\wmdummy_data\Service\Generator;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\wmdummy_data\DummyDataInterface;
use Drupal\wmdummy_data\DummyDataManager;
use Drupal\wmdummy_data\Event\DummyDataCreateEvent;
use Drupal\wmdummy_data\WmDummyDataEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DummyDataGenerator
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EntityFieldManagerInterface */
    protected $entityFieldManager;
    /** @var DummyDataManager */
    protected $dummyDataManager;
    /** @var StateInterface */
    protected $state;
    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManagerInterface $entityFieldManager,
        DummyDataManager $dummyDataManager,
        StateInterface $state,
        LanguageManagerInterface $languageManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFieldManager = $entityFieldManager;
        $this->dummyDataManager = $dummyDataManager;
        $this->state = $state;
        $this->languageManager = $languageManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function generateDummyData(string $entityType, string $bundle, string $preset = DummyDataInterface::PRESET_DEFAULT, string $langcode = null, array &$createdContent = []): ?ContentEntityInterface
    {
        $langcode = $langcode ?? $this->languageManager->getDefaultLanguage()->getId();
        $entityStorage = $this->entityTypeManager->getStorage($entityType);

        if (!$entityStorage instanceof ContentEntityStorageInterface) {
            return null;
        }

        $generator = $this->dummyDataManager->createInstance("{$entityType}.{$bundle}.{$preset}");
        $entityPreset = $generator->generate();
        $entityPreset = $this->addBaseFields($entityPreset, $entityType, $bundle, $langcode);
        $entity = $entityStorage->createWithSampleValues($bundle, $entityPreset);
        $entity->save();
        $this->storeGeneratedEntityId($entityType, $entity);

        $this->eventDispatcher->dispatch(
            WmDummyDataEvents::DUMMY_DATA_CREATE,
            new DummyDataCreateEvent($entity, $generator)
        );

        return $entity;
    }

    private function addBaseFields(array $entityPreset, string $entityType, string $bundle, string $langcode): array
    {
        $entityDefinition = $this->entityTypeManager->getDefinition($entityType);
        $entityFieldDefinition = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);

        if (
            $entityDefinition->hasKey('langcode')
            && ($key = $entityDefinition->getKey('langcode'))
            && isset($entityFieldDefinition[$key])
            && !isset($entityPreset[$key])
        ) {
            $entityPreset[$key] = $langcode;
        }

        /**
         * TODO: Remove this if issue is fixed
         * @see https://www.drupal.org/project/drupal/issues/2915034
         */
        if (
            $entityDefinition->hasKey('default_langcode')
            && ($key = $entityDefinition->getKey('default_langcode'))
            && isset($entityFieldDefinition[$key])
            && !isset($entityPreset[$key])
        ) {
            $entityPreset[$key] = true;
        }

        if (
            isset($entityFieldDefinition['content_translation_source'])
            && !isset($entityPreset['content_translation_source'])
        ) {
            $entityPreset['content_translation_source'] = 'und';
        }

        return $entityPreset;
    }

    public function presetExists(string $entityType, string $bundle, string $presetId, string $langcode): bool
    {
        if ($presetId === DummyDataInterface::PRESET_BASIC) {
            return true;
        }

        foreach ($this->getPresets() as $preset) {
            if (
                $preset['entity_type'] === $entityType
                && $preset['bundle'] === $bundle
                && $preset['langcode'] === $langcode
                && $preset['preset'] === $presetId
            ) {
                return true;
            }
        }

        return false;
    }

    public function getPresets(): array
    {
        $presets = $this->dummyDataManager->getDefinitions();

        foreach ($presets as &$preset) {
            if (isset($preset['id'])) {
                @trigger_error('The DummyData plugin id attribute is @deprecated in wmdummy_data:1.3.0 and is removed from wmdummy_data:2.0.0. Use the entity_type, bundle and preset attributes instead.', E_USER_DEPRECATED);

                $parts = explode('.', $preset['id']);
                unset($preset['id']);

                foreach (['entity_type', 'bundle', 'preset'] as $i => $partName) {
                    if (!isset($preset[$partName])) {
                        $preset[$partName] = $parts[$i];
                    }
                }
            }

            if (!isset($preset['langcode'])) {
                $preset['langcode'] = $this->languageManager->getDefaultLanguage()->getId();
            }
        }

        return array_values($presets);
    }

    private function storeGeneratedEntityId(string $entityType, $entity): void
    {
        $key = "wmdummy_data.{$entityType}";
        $package = $this->state->get($key, []);

        $package[$entity->id()] = $entity->id();
        $this->state->set($key, $package);
        $this->setStateKey($key);
    }

    private function setStateKey(string $key): void
    {
        $keys = $this->state->get('wmdummy_data_keys', []);

        if (!isset($keys[$key])) {
            $keys[$key] = $key;
        }

        $this->state->set('wmdummy_data_keys', $keys);
    }
}
