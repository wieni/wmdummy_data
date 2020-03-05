<?php

namespace Drupal\wmdummy_data\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\wmmodel_factory\EntityFactoryInterface;
use Symfony\Component\EventDispatcher\Event;

class BaseDummyDataEvent extends Event
{
    /** @var ContentEntityInterface */
    protected $entity;
    /** @var EntityFactoryInterface */
    protected $factory;
    /** @var string[] */
    protected $states;

    public function __construct(
        ContentEntityInterface $entity,
        EntityFactoryInterface $factory,
        array $states
    ) {
        $this->entity = $entity;
        $this->factory = $factory;
        $this->states = $states;
    }

    public function getEntity(): ContentEntityInterface
    {
        return $this->entity;
    }

    public function getFactory(): EntityFactoryInterface
    {
        return $this->factory;
    }

    public function getStates(): array
    {
        return $this->states;
    }
}
