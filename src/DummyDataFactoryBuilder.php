<?php

namespace Drupal\wmdummy_data;

use Drupal\wmdummy_data\Event\DummyDataCreateEvent;
use Drupal\wmdummy_data\Event\DummyDataMakeEvent;
use Drupal\wmmodel_factory\FactoryBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DummyDataFactoryBuilder extends FactoryBuilder
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var int */
    protected $chanceOfNew = 100;
    /** @var mixed|null */
    protected $default;
    /** @var bool */
    protected $useDefault = false;

    public static function createInstance(
        ContainerInterface $container,
        string $entityType,
        string $bundle,
        ?string $name = null,
        ?string $langcode = null,
        array $afterMaking = [],
        array $afterCreating = []
    ): FactoryBuilder {
        $instance = parent::createInstance(
            $container,
            $entityType,
            $bundle,
            $name,
            $langcode,
            $afterMaking,
            $afterCreating
        );
        $instance->faker = $container->get('wmdummy_data.faker.generator');
        $instance->eventDispatcher = $container->get('event_dispatcher');

        return $instance;
    }

    public function chanceOfNew(int $chanceOfNew): self
    {
        $this->chanceOfNew = $chanceOfNew;

        return $this;
    }

    public function onlyExisting(): self
    {
        return $this->chanceOfNew(0);
    }

    public function onlyNew(): self
    {
        return $this->chanceOfNew(100);
    }

    public function optional(float $weight = 50, $default = null): self
    {
        if (random_int(1, 100) > $weight) {
            $this->default = $default;
            $this->useDefault = true;
        }

        return $this;
    }

    public function create(array $attributes = [])
    {
        if ($this->useDefault) {
            return $this->default;
        }

        if ($this->amount === 0) {
            return null;
        }

        $isSingle = $this->amount === null;
        $this->isCreating = true;

        [$made, $loaded] = $this->doMake($attributes);

        if (!empty($made)) {
            $factory = $this->getFactory();

            $this->store($made);
            $this->callAfterCreating($made);

            foreach ($made as $instance) {
                $this->eventDispatcher->dispatch(
                    DummyDataEvents::MAKE,
                    new DummyDataMakeEvent($instance, $factory, $this->activeStates)
                );

                $this->eventDispatcher->dispatch(
                    DummyDataEvents::CREATE,
                    new DummyDataCreateEvent($instance, $factory, $this->activeStates)
                );
            }
        }

        unset($this->isCreating);

        $instances = array_merge($loaded, $made);

        return $isSingle ? reset($instances) : $instances;
    }

    public function make(array $attributes = [])
    {
        if ($this->useDefault) {
            return $this->default;
        }

        if ($this->amount === 0) {
            return null;
        }

        $isSingle = $this->amount === null;
        [$made, $loaded] = $this->doMake($attributes);
        $instances = array_merge($loaded, $made);
        $factory = $this->getFactory();

        foreach ($made as $instance) {
            $this->eventDispatcher->dispatch(
                DummyDataEvents::MAKE,
                new DummyDataMakeEvent($instance, $factory, $this->activeStates)
            );
        }

        if ($isSingle) {
            return reset($instances) ?: null;
        }

        return $instances;
    }

    public function load(): array
    {
        if ($this->amount === 0) {
            return [];
        }

        $factoryName = $this->getFactoryName(false);

        if (empty($factoryName) || $factoryName === 'default') {
            return $this->loadAny();
        }

        return $this->loadTracked();
    }

    protected function doMake(array $attributes = []): array
    {
        $made = [];
        $loaded = [];

        if ($this->faker->boolean($this->chanceOfNew)) {
            $made = parent::make($attributes);
        } else {
            $loaded = $this->load();
            $amountShort = ($this->amount ?? 1) - count($loaded);

            if ($amountShort > 0 && $this->chanceOfNew > 0) {
                $this->amount = $amountShort;
                $made = parent::make($attributes);
            }
        }

        return [is_array($made) ? $made : [$made], $loaded];
    }

    protected function loadTracked(): array
    {
        $storage = $this->entityTypeManager->getStorage($this->entityType);

        $query = $storage->getQuery();
        $query->condition('entity_type', $this->entityType);
        $query->condition('entity_bundle', $this->bundle);
        $query->sort('did', 'DESC');
        $query->range(0, 100);

        if ($this->name) {
            $query->condition('factory_name', $this->name);
        }

        if ($this->activeStates) {
            $query->condition('states', $this->activeStates);
        }

        if ($this->langcode) {
            $query->condition('langcode', $this->langcode);
        }

        $query->accessCheck(false);
        $ids = $query->execute();

        if (empty($ids)) {
            return [];
        }

        $ids = $this->getRandom($ids);

        return array_reduce(
            $storage->loadMultiple($ids),
            static function (array $entities, DummyEntityInterface $dummyEntity): array {
                $entity = $dummyEntity->getGeneratedEntity();
                $entities[$entity->id()] = $entity;

                return $entities;
            },
            []
        );
    }

    protected function loadAny(): array
    {
        $storage = $this->entityTypeManager->getStorage($this->entityType);
        $definition = $this->entityTypeManager->getDefinition($this->entityType);

        $query = $storage->getQuery();
        $query->sort($definition->getKey('id'), 'DESC');
        $query->range(0, 100);

        if ($key = $definition->getKey('bundle')) {
            $query->condition($key, $this->bundle);
        }

        if ($this->langcode && $key = $definition->getKey('langcode')) {
            $query->condition($key, $this->langcode);
        }

        $query->accessCheck(false);
        $ids = $query->execute();

        if (empty($ids)) {
            return [];
        }

        $ids = $this->getRandom($ids);

        return $storage->loadMultiple($ids);
    }

    protected function getRandom(array $ids): array
    {
        $amount = min(count($ids), $this->amount ?? 1);
        $random = array_rand($ids, $amount);

        if (!is_array($random)) {
            $random = [$random];
        }

        return $random;
    }
}
