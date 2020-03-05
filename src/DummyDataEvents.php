<?php

namespace Drupal\wmdummy_data;

final class DummyDataEvents
{
    /**
     * Will be triggered after an entity is generated.
     *
     * The event object is an instance of
     * @uses \Drupal\wmdummy_data\Event\DummyDataMakeEvent
     */
    public const MAKE = 'wmdummy_data.make';

    /**
     * Will be triggered after an entity is generated
     * and persisted to the database.
     *
     * The event object is an instance of
     * @uses \Drupal\wmdummy_data\Event\DummyDataCreateEvent
     */
    public const CREATE = 'wmdummy_data.create';
}
