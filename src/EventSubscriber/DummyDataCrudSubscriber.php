<?php

namespace Drupal\wmdummy_data\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\hook_event_dispatcher\Event\Entity\EntityDeleteEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
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

    public static function getSubscribedEvents()
    {
        $events[DummyDataEvents::CREATE][] = ['onCreate'];
        $events[HookEventDispatcherInterface::ENTITY_DELETE][] = ['onDelete'];

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

    public function onDelete(EntityDeleteEvent $event): void
    {
        $entity = $event->getEntity();

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
