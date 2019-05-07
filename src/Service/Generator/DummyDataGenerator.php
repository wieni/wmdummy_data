<?php

namespace Drupal\wmdummy_data\Service\Generator;

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

    public function generateDummyData(string $entityType, string $bundle, string $preset, string $langcode): array
    {
        $createdEntities = [];

        $entityPreset = $this->getInstance($entityType, $bundle, $preset)->generate();

        $entityStorage = $this->entityTypeManager->getStorage($entityType);
        if ($entityStorage instanceof ContentEntityStorageInterface) {
            $values = $this->getSpecialFields($entityType, $bundle, $langcode, $entityPreset);
            $entity =  $entityStorage->createWithSampleValues($bundle, $values);
            $entity->save();
            $this->storeGeneratedEntityId($entityType, $entity);

            if (isset($this->wmContentManager)) {
                if ($preset !== 'basic' && $this->getInstance($entityType, $bundle, $preset) instanceof ContentGenerateInterface) {
                    $contentPreset = $this->getInstance($entityType, $bundle, $preset)->generateContent();
                    $createdChildren = $this->generateSpecificContentBlocks($entity, $langcode, $contentPreset, $preset);
                } else {
                    $createdChildren = $this->generateRandomContentBlocks($entity, $langcode, $preset);
                }
                $createdEntities[$entity->id()] = $createdChildren;
            }
        }

        return $createdEntities;
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

    private function getSpecialFields(string $entityType, string $bundle, string $langcode, array &$entityPreset): array
    {
        $entityDefinition = $this->entityTypeManager->getDefinition($entityType);
        $entityFieldDefinition = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);

        // langcode
        if ($entityDefinition->hasKey('langcode')) {
            $label = $entityDefinition->getKey('langcode');
            if (!empty($label)) {
                $entityPreset[$label] = $langcode ;
            }
        }

        /**
         * TODO: Remove this if issue is fixed
         * @see https://www.drupal.org/project/drupal/issues/2915034
         */
        if ($entityDefinition->hasKey('default_langcode')) {
            $label = $entityDefinition->getKey('default_langcode');
            if (!empty($label)) {
                $entityPreset[$label] = true ;
            }
        }
        if (isset($entityFieldDefinition['content_translation_source'])) {
            $entityPreset['content_translation_source'] = 'und';
        }

        // wmcontent stuff
        if (isset($this->wmContentManager)) {
            if (isset($entityFieldDefinition['wmcontent_parent'])) {
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
            if (isset($entityFieldDefinition['wmcontent_parent_type'], $host)) {
                $entityPreset['wmcontent_parent_type'] = $host;
            }
            if (isset($entityFieldDefinition['wmcontent_container'], $host)) {
                $hostEntity = $this->entityTypeManager->getStorage($host)->load($id);
                $possibleContainers = $this->wmContentManager->getHostContainers($hostEntity);
                $k = array_rand($possibleContainers);
                $hostContainer = $possibleContainers[$k];
                $containerId = $hostContainer->id();
                $entityPreset['wmcontent_container'] = $containerId;
            }
        }
        return $entityPreset;
    }

    public function presetExists(string $entityType, string $bundle, string $preset, string $langcode): bool
    {
        $presetIds = ["{$entityType}.{$bundle}.{$preset}"];
        if ($preset === 'default') {
            $presetIds[] = "{$entityType}.{$bundle}";
        }

        $entityPresets = $this->dummyDataManager->getDefinitions();

        foreach ($presetIds as $presetId) {
            if (
                isset($entityPresets[$presetId])
                && (
                    !isset($entityPresets[$presetId]['langcode']) ||
                    $entityPresets[$presetId]['langcode'] === $langcode
                )
            ) {
                return true;
            }
        }
        return false;
    }

    private function generateRandomContentBlocks(EntityInterface $entity, string $langcode, string $preset): int
    {
        $containers = $this->wmContentManager->getHostContainers($entity);
        $entityId = $entity->id();
        $entityType = $entity->getEntityType()->id();
        $createdContainers = 0;

        if (empty($containers)) {
            return $createdContainers;
        }

        foreach ($containers as $container) {
            $childBundles = $container->getChildBundles();
            $childEntityType = $container->getChildEntityType();
            $entityContainer = $container->id;

            foreach ($childBundles as $childBundle) {
                $make = random_int(0, 1);
                if ($make === 0) {
                    continue;
                }

                $count = random_int(1, 7);

                for ($x = 0; $x < $count; $x++) {
                    $entityPreset = $this->getChildPreset($childEntityType, $childBundle, $preset, $langcode);
                    $completedPreset = $this->childPresetHandler($entityPreset, $childEntityType, $childBundle, $langcode, $entityId, $entityType, $entityContainer);

                    $entityStorage = $this->entityTypeManager->getStorage($childEntityType);
                    $child = $entityStorage->createWithSampleValues($childBundle, $completedPreset);
                    $child->save();
                    $this->storeGeneratedEntityId($childEntityType, $child);
                    $createdContainers++;
                }
            }
        }
        return $createdContainers;
    }

    private function generateSpecificContentBlocks(EntityInterface $entity, string $langcode, array $contentPreset, string $hostPreset): int
    {
        $entityId = $entity->id();
        $entityType = $entity->getEntityType()->id();

        $createdContainers = 0;

        if (empty($contentPreset)) {
            return $createdContainers;
        }

        foreach ($contentPreset as $entityName => $entityPreset) {
            $possibleContainers = $this->wmContentManager->getHostContainers($entity);
            $k = array_rand($possibleContainers);
            $hostContainer = $possibleContainers[$k];
            $containerId = $hostContainer->id();

            foreach ($entityPreset as $bundleName => $bundlePreset) {
                if (empty($bundlePreset)) {
                    $bundlePreset = $this->getChildPreset($entityName, $bundleName, $hostPreset, $langcode);
                }
                $completedPreset = $this->childPresetHandler($bundlePreset, $entityName, $bundleName, $langcode, $entityId, $entityType, $containerId);
                $entityStorage = $this->entityTypeManager->getStorage($entityName);

                $child = $entityStorage->createWithSampleValues($bundleName, $completedPreset);
                $child->save();
                $this->storeGeneratedEntityId($entityName, $child);

                $createdContainers++;
            }
        }
        return $createdContainers;
    }

    private function getChildPreset (string $childEntityType, string $childBundle, string $parentPreset, string $langcode): array
    {
        // check if preset exists, if not use default, if not use basic
        $presetsExists = $this->presetExists($childEntityType, $childBundle, $parentPreset, $langcode);
        if ($presetsExists) {
            return $this->getInstance($childEntityType, $childBundle, $parentPreset)->generate();
        }
        $presetsExists = $this->presetExists($childEntityType, $childBundle, 'default', $langcode);
        if ($presetsExists) {
            return $this->getInstance($childEntityType, $childBundle, 'default')->generate();
        }
        return [];
    }

    private function childPresetHandler(array $bundlePreset, string $childType, string $childBundle, string $langcode, int $parentId, string $parentType, string $containerId): array
    {
        $entityDefinition = $this->entityTypeManager->getDefinition($childType);
        $entityFieldDefinition = $this->entityFieldManager->getFieldDefinitions($childType, $childBundle);
        if ($entityDefinition->hasKey('langcode')) {
            $label = $entityDefinition->getKey('langcode');
            if (!empty($label)) {
                if (isset($entityFieldDefinition[$label])) {
                    $field = $entityFieldDefinition[$label];
                }
                if (isset($field)) {
                    $bundlePreset[$label] = $langcode;
                }
            }
        }

        $bundlePreset['wmcontent_parent'] = $parentId;
        $bundlePreset['wmcontent_parent_type'] = $parentType;
        $bundlePreset['wmcontent_container'] = $containerId;

        return $bundlePreset;
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

    public function generateReferencedEntity(string $entityType, string $bundle, string $preset = 'default', string $langcode = null): array
    {
        if (!$langcode) {
            $langcode = $this->languageManager->getDefaultLanguage()->getId();
        }

        if (!$this->presetExists($entityType, $bundle, $preset, $langcode)) {
            throw new \InvalidArgumentException(
                t('The preset \':preset\' for the referenced entity \':entityType\' \':bundleType\' does not exist.', [':preset' => $preset, ':entityType' => $entityType, ':bundleType' => $bundle])
            );
        }

        $entityPreset = $this->getInstance($entityType, $bundle, $preset)->generate();

        $entityStorage = $this->entityTypeManager->getStorage($entityType);
        if ($entityStorage instanceof ContentEntityStorageInterface) {
            $values = $this->getSpecialFields($entityType, $bundle, $langcode, $entityPreset);
            $entity =  $entityStorage->createWithSampleValues($bundle, $values);
            $entity->save();
            $this->storeGeneratedEntityId($entityType, $entity);
            return [
                'entity' => $entity,
            ];
        }

        return [];
    }
}
