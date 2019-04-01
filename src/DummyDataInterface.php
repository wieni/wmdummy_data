<?php

namespace Drupal\wmdummy_data;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface DummyDataInterface extends PluginInspectionInterface
{
    public function generate(): array;

    public function getLangcode(): string;
    public function getKey(): string;

}