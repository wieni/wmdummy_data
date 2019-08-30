<?php

namespace Drupal\wmdummy_data\EventSubscriber;

use Drupal\wmcontent\WmContentManager;
use Drupal\wmdummy_data\ContentGenerateInterface;
use Drupal\wmdummy_data\Event\DummyDataCreateEvent;
use Drupal\wmdummy_data\WmDummyDataEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContentGenerateSubscriber implements EventSubscriberInterface
{
    /** @var WmContentManager */
    protected $wmContentManager;

    public static function getSubscribedEvents()
    {
        $events[WmDummyDataEvents::DUMMY_DATA_CREATE][] = ['onCreate'];

        return $events;
    }

    public function setWmContentManager(WmContentManager $wmContentManager): void
    {
        $this->wmContentManager = $wmContentManager;
    }

    public function onCreate(DummyDataCreateEvent $event)
    {
        $generator = $event->getGenerator();

        if (
            !isset($this->wmContentManager)
            || !$generator instanceof ContentGenerateInterface
        ) {
            return;
        }

        $entity = $event->getEntity();
        $definition = $generator->getPluginDefinition();
        $containers = $this->wmContentManager->getHostContainers($entity);

        if (isset($definition['wmcontent_containers'])) {
            $containers = array_intersect_key(
                $containers,
                array_flip($definition['wmcontent_containers'])
            );
        }

        foreach ($containers as $container) {
            foreach ($generator->generateContent($container) as $child) {
                $child->set('wmcontent_parent', $entity->id());
                $child->set('wmcontent_parent_type', $entity->getEntityTypeId());
                $child->set('wmcontent_container', $container->id());
                $child->save();
            }
        }
    }
}
