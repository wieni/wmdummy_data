<?php

namespace Drupal\wmdummy_data;

use Drupal\Component\Plugin\PluginBase;

abstract class DummyDataBase extends PluginBase implements DummyDataInterface
{
    abstract public function generate(): array;

    public function getKey(): string
    {
        return $this->pluginDefinition['id'];
    }

    public function getLangcode(): string
    {
        return $this->pluginDefinition['langcode'];
    }
}