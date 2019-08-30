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
}
