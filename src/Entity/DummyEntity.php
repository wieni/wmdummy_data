<?php

namespace Drupal\wmdummy_data\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\wmdummy_data\DummyEntityInterface;

/**
 * @ContentEntityType(
 *     id = "dummy_entity",
 *     label = @Translation("Dummy entity"),
 *     base_table = "dummy_entity",
 *     translatable = FALSE,
 *     entity_keys = {
 *         "id" : "did",
 *     },
 * )
 */
class DummyEntity extends ContentEntityBase implements DummyEntityInterface
{
    public static function baseFieldDefinitions(EntityTypeInterface $entityType): array
    {
        $fields['did'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Dummy entity ID'))
            ->setDescription(t('The dummy entity ID.'))
            ->setReadOnly(true);

        $fields['entity_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Generated entity ID'))
            ->setDescription(t('The ID of the generated entity.'));

        $fields['entity_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Generated entity type'))
            ->setDescription(t('The type of the generated entity.'));

        $fields['entity_bundle'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Generated entity bundle'))
            ->setDescription(t('The bundle of the generated entity.'));

        $fields['entity_language'] = BaseFieldDefinition::create('language')
            ->setLabel(t('Generated entity language'))
            ->setDescription(t('The language of the generated entity.'))
            ->setDisplayOptions('form', [
                'type' => 'language_select',
                'weight' => 2,
            ]);

        $fields['factory_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Factory name'))
            ->setDescription(t('The name of the factory that was used to generate this entity.'));

        $fields['states'] = BaseFieldDefinition::create('string')
            ->setLabel(t('States'))
            ->setDescription(t('The plugin IDs of the states that were used to generate this entity.'))
            ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

        return $fields;
    }

    public function getGeneratedEntity(): ?EntityInterface
    {
        $langcode = $this->get('entity_language')->value;

        $entity = $this->entityTypeManager()
            ->getStorage($this->get('entity_type')->value)
            ->load($this->get('entity_id')->value);

        if (!$entity instanceof TranslatableInterface) {
            return $entity;
        }

        if (!$entity->hasTranslation($langcode)) {
            return $entity;
        }

        return $entity->getTranslation($langcode);
    }

    public function getFactoryPluginId(): string
    {
        return implode('.', [
            $this->get('entity_type')->value,
            $this->get('entity_bundle')->value,
            $this->get('factory_name')->value,
        ]);
    }

    public function getStateNames(): array
    {
        return $this->get('dependency_value')->value;
    }
}
