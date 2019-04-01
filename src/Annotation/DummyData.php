<?php
namespace Drupal\wmdummy_data\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class DummyData extends Plugin
{
    /** @var string */
    public $langcode;
}