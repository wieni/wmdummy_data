<?php

namespace Drupal\wmdummy_data\Service\Generator;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\wmcontent\WmContentManager;
use Drupal\wmdummy_data\DummyDataManager;

class DummyDataGenerator
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EntityFieldManager */
    protected $entityFieldManager;
    /** @var DummyDataManager */
    protected $dummyDataManager;
    /** @var WmContentManager */
    protected $wmContentManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManager $entityFieldManager,
        DummyDataManager $dummyDataManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFieldManager = $entityFieldManager;
        $this->dummyDataManager = $dummyDataManager;
    }

    public function setContentManager(WmContentManager $wmContentManager): void
    {
        $this->wmContentManager = $wmContentManager;
    }

    public function generateDummyData(string $entityType, string $bundle, int $count, string $langcode): array
    {
        $createdEntities = [];
        $presetId = $entityType . '.' . $bundle;
        $presetsExists = $this->presetsExists($presetId);

        for ($x = 0; $x < $count; $x++) {
            $entityPreset = [];
            if ($presetsExists) {
                $entityPreset = $this->dummyDataManager->createInstance($presetId)->generate();
            }

            $entity_storage = $this->entityTypeManager->getStorage($entityType);
            if ($entity_storage instanceof ContentEntityStorageInterface) {
                $values = $this->getSpecialFields($entityType, $bundle, $langcode, $entityPreset);
                $entity =  $entity_storage->createWithSampleValues($bundle, $values);
                $entity->save();

                if (isset($this->wmContentManager)) {
                    $createdChildren = $this->generateRandomContentBlocks($entity, $langcode);
                    $createdEntities[$entity->id()] = $createdChildren;
                }
            }

        }
        return $createdEntities;
    }

    public function getSpecialFields(string $entityType, string $bundle, string $langcode, array &$entityPreset): array
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

        /** TODO: Remove this if issue is fixed
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

    public function presetsExists(string $presetId): bool
    {
        $entityPresets = $this->dummyDataManager->getDefinitions();
        $presetsExists = isset($entityPresets[$presetId]) ? true : false;

        return $presetsExists;
    }

    public function generateRandomContentBlocks(EntityInterface $entity, string $langcode): int
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
                $ran = random_int(0, 1);
                if ($entity instanceof Page){
                    $ran = 0;
                }
                if ($ran === 1) {
                    continue;
                }
                $presetId = $childEntityType . '.' . $childBundle;
                $presetsExists = $this->presetsExists($presetId);

                if ($presetsExists) {
                    $entityPreset = $this->dummyDataManager->createInstance($presetId)->generate();
                }

                $entityDefinition = $this->entityTypeManager->getDefinition($childEntityType);
                $entityFieldDefinition = $this->entityFieldManager->getFieldDefinitions($childEntityType, $childBundle);
                if ($entityDefinition->hasKey('langcode')) {
                    $label = $entityDefinition->getKey('langcode');
                    if (!empty($label)) {
                        if (isset($entityFieldDefinition[$label])) {
                            $field = $entityFieldDefinition[$label];
                        }
                        if (isset($field)) {
                            $entityPreset[$label] = $langcode;
                        }
                    }
                }
                $entityPreset['wmcontent_parent'] = $entityId;
                $entityPreset['wmcontent_parent_type'] = $entityType;
                $entityPreset['wmcontent_container'] = $entityContainer;

                $entity_storage = $this->entityTypeManager->getStorage($childEntityType);
                $child = $entity_storage->createWithSampleValues($childBundle, $entityPreset);
                $child->save();
                $createdContainers++;
            }
        }
        return $createdContainers;
    }
}