<?php

namespace Drupal\wmdummy_data\Faker\Provider;

use Drupal\wmdummy_data\DummyDataFactoryBuilder;
use Drupal\wmmodel_factory\FactoryBuilder;
use Drupal\wmmodel_factory\Faker\Provider\DrupalEntity as BaseDrupalEntity;

class DrupalEntity extends BaseDrupalEntity
{
    public function entityWithType(string $entityType, ?string $bundle = null, ?string $name = null): FactoryBuilder
    {
        return DummyDataFactoryBuilder::createInstance(
            $this->container,
            $entityType,
            $bundle ?? $entityType,
            $name
        );
    }
}
