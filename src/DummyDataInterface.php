<?php

namespace Drupal\wmdummy_data;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface DummyDataInterface extends PluginInspectionInterface
{
    /**
     * A preset using FieldItemInterface::generateSampleValue to generate random values.
     * @see FieldItemInterface
     */
    public const PRESET_BASIC = 'basic';

    /**
     * The default preset, used if the preset part of the plugin ID is left out.
     */
    public const PRESET_DEFAULT = 'default';

    public function generate(): array;

    /**
     * @deprecated in wmdummy_data:1.3.0 and is removed from wmdummy_data:2.0.0.
     */
    public function getLangcode(): string;

    /**
     * @deprecated in wmdummy_data:1.3.0 and is removed from wmdummy_data:2.0.0.
     *   Use DummyDataBase::getEntityType(), DummyDataBase::getBundle()
     *   or DummyDataBase::getPreset() instead.
     */
    public function getKey(): string;
}
