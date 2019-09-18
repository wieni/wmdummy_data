<?php

namespace  Drupal\wmdummy_data\Commands;

use Drupal\wmdummy_data\Service\Generator\DummyDataGenerator;
use Drush\Commands\DrushCommands;

class DummyDeleteCommand extends DrushCommands
{
    /** @var DummyDataGenerator */
    protected $dummyDataGenerator;

    public function __construct(
        DummyDataGenerator $dummyDataGenerator
    ) {
        $this->dummyDataGenerator = $dummyDataGenerator;
    }

    /**
     * Command to delete generated entities
     *
     * @command wmdummy-data:delete
     * @aliases kill-dummies
     *
     * @param string $entityType
     *      Name of bundle to attach fields to.
     *
     * @usage drush wmdummy-data:delete all
     * @usage drush wmdummy-data:delete entity-type
     */
    public function delete(string $entityType): void
    {
        $totalCount = 0;

        if ($entityType === 'all') {
            $entityTypes = $this->dummyDataGenerator->getGeneratedEntityTypes();
        } else {
            $entityTypes = [$entityType];
        }

        foreach ($entityTypes as $entityType) {
            $count = $this->dummyDataGenerator->deleteGeneratedEntities($entityType);
            $totalCount += $count;

            $this->logger()->success(
                "Successfully destroyed {$count} dummies for entity {$entityType}."
            );
        }

        $this->logger()->success(
            "{$totalCount} dummy entities have been annihilated."
        );
    }
}
