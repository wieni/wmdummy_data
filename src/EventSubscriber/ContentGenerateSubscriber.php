<?php

namespace Drupal\wmdummy_data\EventSubscriber;

use Drupal\wmdummy_data\ContentGenerateInterface;
use Drupal\wmdummy_data\DummyDataEvents;
use Drupal\wmdummy_data\Event\DummyDataCreateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContentGenerateSubscriber implements EventSubscriberInterface
{
    /** @var \Drupal\wmcontent\WmContentManager */
    protected $wmContentManager;

    public static function getSubscribedEvents(): array
    {
        $events[DummyDataEvents::CREATE][] = ['onCreate'];

        return $events;
    }

    public function setWmContentManager($wmContentManager): void
    {
        $this->wmContentManager = $wmContentManager;
    }

    public function onCreate(DummyDataCreateEvent $event): void
    {
        $factory = $event->getFactory();

        if (
            !isset($this->wmContentManager)
            || !$factory instanceof ContentGenerateInterface
        ) {
            return;
        }

        $entity = $event->getEntity();
        $definition = $factory->getPluginDefinition();
        $containers = $this->wmContentManager->getHostContainers($entity);

        if (isset($definition['wmcontent_containers'])) {
            $containers = array_intersect_key(
                $containers,
                array_flip($definition['wmcontent_containers'])
            );
        }

        foreach ($containers as $container) {
            foreach ($factory->generateContent($container) as $builder) {
                $builder->create([
                    'wmcontent_parent' => $entity->id(),
                    'wmcontent_parent_type' => $entity->getEntityTypeId(),
                    'wmcontent_container' => $container->id(),
                ]);
            }
        }
    }
}
