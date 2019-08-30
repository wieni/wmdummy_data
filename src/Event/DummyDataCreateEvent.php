<?php

namespace Drupal\wmdummy_data\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\wmdummy_data\DummyDataInterface;
use Symfony\Component\EventDispatcher\Event;

class DummyDataCreateEvent extends Event
{
    /** @var ContentEntityInterface */
    protected $entity;
    /** @var DummyDataInterface */
    protected $generator;

    public function __construct(
        ContentEntityInterface $entity,
        DummyDataInterface $generator
    ) {
        $this->entity = $entity;
        $this->generator = $generator;
    }

    public function getEntity(): ContentEntityInterface
    {
        return $this->entity;
    }

    public function getGenerator(): DummyDataInterface
    {
        return $this->generator;
    }
}
