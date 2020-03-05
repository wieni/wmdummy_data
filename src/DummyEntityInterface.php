<?php

namespace Drupal\wmdummy_data;

use Drupal\Core\Entity\EntityInterface;

interface DummyEntityInterface
{
    public function getGeneratedEntity(): ?EntityInterface;

    public function getFactoryPluginId(): string;

    /** @return string[] */
    public function getStateNames(): array;
}
