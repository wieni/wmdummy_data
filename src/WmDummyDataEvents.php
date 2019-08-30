<?php

namespace Drupal\wmdummy_data;

final class WmDummyDataEvents
{
    /**
     * Will be triggered after an entity is generated.
     *
     * The event object is an instance of
     * @uses \Drupal\wmdummy_data\Event\DummyDataCreateEvent
     */
    public const DUMMY_DATA_CREATE = 'wmdummy_data.dummy_data.create';
}
