<?php

namespace Drupal\wmdummy_data;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\wmdummy_data\Annotation\DummyData;

class DummyDataManager extends DefaultPluginManager
{
    public function __construct(
        \Traversable $namespaces,
        CacheBackendInterface $cacheBackend,
        ModuleHandlerInterface $moduleHandler
    ) {
        parent::__construct(
            'Generator',
            $namespaces,
            $moduleHandler,
            DummyDataInterface::class,
            DummyData::class
        );
        $this->alterInfo('wmdummy_data_info');
        $this->setCacheBackend($cacheBackend, 'wmdummy_data_info_plugins');
    }
}
