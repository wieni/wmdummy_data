<?php

namespace Drupal\wmdummy_data\Service\Generator;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\wmcontent\WmContentManager;
use Drupal\wmdummy_data\ContentGenerateInterface;
use Drupal\wmdummy_data\DummyDataInterface;
use Drupal\wmdummy_data\DummyDataManager;

class DummyDataGenerator
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EntityFieldManagerInterface */
    protected $entityFieldManager;
    /** @var DummyDataManager */
    protected $dummyDataManager;
    /** @var WmContentManager */
    protected $wmContentManager;
    /** @var StateInterface */
    protected $state;
    /** @var LanguageManagerInterface */
    protected $languageManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManagerInterface $entityFieldManager,
        DummyDataManager $dummyDataManager,
        StateInterface $state,
        LanguageManagerInterface $languageManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFieldManager = $entityFieldManager;
        $this->dummyDataManager = $dummyDataManager;
        $this->state = $state;
        $this->languageManager = $languageManager;
    }

    public function setContentManager(WmContentManager $wmContentManager): void
    {
        $this->wmContentManager = $wmContentManager;
    }

    public function generateDummyData(string $entityType, string $bundle, string $preset = DummyDataInterface::PRESET_DEFAULT, string $langcode = null, array &$createdContent = []): ?ContentEntityInterface
    {
        $langcode = $langcode ?? $this->languageManager->getDefaultLanguage()->getId();
        $entityStorage = $this->entityTypeManager->getStorage($entityType);

        if (!$entityStorage instanceof ContentEntityStorageInterface) {
            return null;
        }

        $entityPreset = $this->getInstance($entityType, $bundle, $preset)->generate();
        $this->addBaseFields($entityPreset, $entityType, $bundle, $langcode);
        $entity =  $entityStorage->createWithSampleValues($bundle, $entityPreset);
        $entity->save();
        $this->storeGeneratedEntityId($entityType, $entity);

        if (isset($this->wmContentManager) && $preset !== DummyDataInterface::PRESET_BASIC) {
            $generator = $this->getInstance($entityType, $bundle, $preset);

            if ($generator instanceof ContentGenerateInterface) {
                $contentPreset = $generator->generateContent();
                $createdContent = $this->generateSpecificContentBlocks($entity, $langcode, $contentPreset, $preset);
            } else {
                $createdContent = $this->generateRandomContentBlocks($entity, $langcode, $preset);
            }
        }

        return $entity;
    }

    /* eerst checken of het bestaat met presetExists */
    private function getInstance(string $entityType, string $bundle, string $preset): DummyDataInterface
    {
        try {
            $instance = $this->dummyDataManager->createInstance("{$entityType}.{$bundle}.{$preset}");
        } catch (\Exception $exception) {
            $instance = $this->dummyDataManager->createInstance("{$entityType}.{$bundle}");
        }
        return $instance;
    }

    private function addBaseFields(array &$entityPreset, string $entityType, string $bundle, string $langcode): void
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

        // wmcontent stuff
        if (isset($this->wmContentManager)) {
            if (
                isset($entityFieldDefinition['wmcontent_parent'])
                && !isset($entityPreset['wmcontent_parent'])
            ) {
                $hostContainers = [];
                $containers = $this->wmContentManager->getContainers();
                foreach ($containers as $container) {
                    $childBundles = $container->getChildBundlesAll();
                    if (array_key_exists($bundle, $childBundles)) {
                        $hostContainers[] = $container;
                    }
                }
                if (!empty($hostContainers)) {
                    $key = array_rand($hostContainers);
                    $host = $hostContainers[$key]->getHostEntityType();

                    $entityDefinition = $this->entityTypeManager->getDefinition($host);
                    $label = $entityDefinition->getKey('id');

                    $query = $this->entityTypeManager->getStorage($host)->getQuery();
                    $query->sort($label, 'DESC');
                    $query->range(0, 10);
                    $ids = $query->execute();
                    $id = array_rand($ids);
                    $entityPreset['wmcontent_parent'] = $id;
                }
            }

            if (
                isset($entityFieldDefinition['wmcontent_parent_type'], $host)
                && !isset($entityPreset['wmcontent_parent_type'])
            ) {
                $entityPreset['wmcontent_parent_type'] = $host;
            }

            if (
                isset($entityFieldDefinition['wmcontent_container'], $host)
                && !isset($entityPreset['wmcontent_container'])
            ) {
                $hostEntity = $this->entityTypeManager->getStorage($host)->load($id);
                $possibleContainers = $this->wmContentManager->getHostContainers($hostEntity);
                $k = array_rand($possibleContainers);
                $hostContainer = $possibleContainers[$k];
                $containerId = $hostContainer->id();
                $entityPreset['wmcontent_container'] = $containerId;
            }
        }
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

    private function generateRandomContentBlocks(EntityInterface $entity, string $langcode, string $preset): array
    {
        $containers = $this->wmContentManager->getHostContainers($entity);
        $entityId = $entity->id();
        $entityType = $entity->getEntityType()->id();

        $createdContent = [];

        if (empty($containers)) {
            return $createdContent;
        }

        foreach ($containers as $container) {
            $childBundles = $container->getChildBundles();
            $childEntityType = $container->getChildEntityType();
            $entityContainer = $container->id;
            $entityStorage = $this->entityTypeManager->getStorage($childEntityType);

            foreach ($childBundles as $childBundle) {
                $make = random_int(0, 1);
                if ($make === 0) {
                    continue;
                }

                $count = random_int(1, 7);

                for ($x = 0; $x < $count; $x++) {
                    $entityPreset = $this->getChildPreset($childEntityType, $childBundle, $preset, $langcode);
                    $entityPreset['wmcontent_parent'] = $entityId;
                    $entityPreset['wmcontent_parent_type'] = $entityType;
                    $entityPreset['wmcontent_container'] = $entityContainer;
                    $this->addBaseFields($entityPreset, $childEntityType, $childBundle, $langcode);

                    $child = $entityStorage->createWithSampleValues($childBundle, $entityPreset);
                    $child->save();
                    $this->storeGeneratedEntityId($childEntityType, $child);

                    $createdContent[$child->id()] = $child;
                }
            }
        }

        return $createdContent;
    }

    private function generateSpecificContentBlocks(EntityInterface $entity, string $langcode, array $contentPreset, string $hostPreset): array
    {
        $entityId = $entity->id();
        $entityType = $entity->getEntityType()->id();

        $createdContent = [];

        if (empty($contentPreset)) {
            return $createdContent;
        }

        foreach ($contentPreset as $entityName => $entityPreset) {
            $possibleContainers = $this->wmContentManager->getHostContainers($entity);
            $k = array_rand($possibleContainers);
            $hostContainer = $possibleContainers[$k];
            $containerId = $hostContainer->id();
            $entityStorage = $this->entityTypeManager->getStorage($entityName);

            foreach ($entityPreset as $bundleName => $bundlePreset) {
                if (empty($bundlePreset)) {
                    $bundlePreset = $this->getChildPreset($entityName, $bundleName, $hostPreset, $langcode);
                }

                $bundlePreset['wmcontent_parent'] = $entityId;
                $bundlePreset['wmcontent_parent_type'] = $entityType;
                $bundlePreset['wmcontent_container'] = $containerId;
                $this->addBaseFields($bundlePreset, $entityName, $bundleName, $langcode);

                $child = $entityStorage->createWithSampleValues($bundleName, $bundlePreset);
                $child->save();
                $this->storeGeneratedEntityId($entityName, $child);

                $createdContent[$child->id()] = $child;
            }
        }

        return $createdContent;
    }

    private function getChildPreset(string $childEntityType, string $childBundle, string $parentPreset, string $langcode): array
    {
        // check if preset exists, if not use default, if not use basic
        $presetsExists = $this->presetExists($childEntityType, $childBundle, $parentPreset, $langcode);
        if ($presetsExists) {
            return $this->getInstance($childEntityType, $childBundle, $parentPreset)->generate();
        }
        $presetsExists = $this->presetExists($childEntityType, $childBundle, DummyDataInterface::PRESET_DEFAULT, $langcode);
        if ($presetsExists) {
            return $this->getInstance($childEntityType, $childBundle, DummyDataInterface::PRESET_DEFAULT)->generate();
        }
        return [];
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
