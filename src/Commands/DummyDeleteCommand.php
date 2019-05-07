<?php

namespace  Drupal\wmdummy_data\Commands;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drush\Commands\DrushCommands;

class DummyDeleteCommand extends DrushCommands
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var StateInterface */
    protected $state;

    public function __construct(
        EntityTypeManager $entityTypeManager,
        StateInterface $state
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->state = $state;
    }

    /**
     * @command wmdummy-data:delete
     * @aliases kill-dummies
     */
    public function delete(): void
    {
        $keys = $this->state->get('wmdummy_data_keys', []);

        foreach ($keys as $k) {
            if (!$this->state->get($k)) {
                continue;
            }

            $ids = $this->state->get($k);
            $idsCount = count($ids);

            [, $entityType] = explode('.', $k);

            $storage = $this->entityTypeManager->getStorage($entityType);

            $toDeleteEntities = $storage->loadMultiple($ids);
            $storage->delete($toDeleteEntities);

            $this->logger()->success(
                "Successfully destroyed {$idsCount} dummies for entity {$entityType}."
            );

            $this->state->delete($k);
        }

        $this->logger()->success(sprintf('%d dummy entities have been annihilated.', count($keys)));
    }
}
