<?php

namespace Drupal\wmdummy_data;

use Drupal\wmmodel_factory\Factory;
use Drupal\wmmodel_factory\FactoryBuilder;

class DummyDataFactory extends Factory
{
    public function ofType(string $entityTypeId, string $bundle, string $name = 'default'): FactoryBuilder
    {
        return DummyDataFactoryBuilder::createInstance(
            $this->container,
            $entityTypeId,
            $bundle,
            $name,
            $this->langcode,
            $this->afterMaking,
            $this->afterCreating
        );
    }
}
