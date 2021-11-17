<?php

namespace Drupal\wmdummy_data\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\wmdummy_data\DummyDataEvents;
use Drupal\wmdummy_data\Event\DummyDataCreateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DummyDataCrudSubscriber implements EventSubscriberInterface
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
    }

    public static function getSubscribedEvents(): array
    {
        $events[DummyDataEvents::CREATE][] = ['onCreate'];

        return $events;
    }

    public function onCreate(DummyDataCreateEvent $event): void
    {
        $entity = $event->getEntity();
        $factory = $event->getFactory();
        $states = $event->getStates();

        $storage = $this->entityTypeManager
            ->getStorage('dummy_entity');

        $dummyEntity = $storage->create([
            'entity_id' => $entity->id(),
            'entity_type' => $entity->getEntityTypeId(),
            'entity_bundle' => $entity->bundle(),
            'entity_language' => $entity->language()->getId(),
            'factory_name' => $factory->getPluginId(),
            'states' => $states,
        ]);

        $dummyEntity->save();
    }

    public function onDelete(EntityInterface $entity): void
    {
        $storage = $this->entityTypeManager
            ->getStorage('dummy_entity');

        $entities = $storage->loadByProperties([
            'entity_id' => $entity->id(),
            'entity_type' => $entity->getEntityTypeId(),
            'entity_language' => $entity->language()->getId(),
        ]);

        if (empty($entities)) {
            return;
        }

        $storage->delete($entities);
    }
}
