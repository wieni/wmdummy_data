<?php

namespace Drupal\wmdummy_data;

final class WmDummyDataEvents
{
    /**
     * Will be triggered after an entity is generated, but before it is saved.
     *
     * The event object is an instance of
     * @uses \Drupal\wmdummy_data\Event\DummyDataCreateEvent
     */
    public const DUMMY_DATA_PRE_SAVE = 'wmdummy_data.dummy_data.pre_save';

    /**
     * Will be triggered after an entity is generated and saved.
     *
     * The event object is an instance of
     * @uses \Drupal\wmdummy_data\Event\DummyDataCreateEvent
     */
    public const DUMMY_DATA_CREATE = 'wmdummy_data.dummy_data.create';
}
