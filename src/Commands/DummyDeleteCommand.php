<?php

namespace Drupal\wmdummy_data\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\wmmodel_factory\EntityFactoryPluginManager;
use Drush\Commands\DrushCommands;

class DummyDeleteCommand extends DrushCommands
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EntityFactoryPluginManager */
    protected $entityFactoryManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityFactoryPluginManager $entityFactoryManager
    ) {
        parent::__construct();
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFactoryManager = $entityFactoryManager;
    }

    /**
     * Command to delete generated entities
     *
     * @command wmdummy-data:delete
     * @aliases kill-dummies
     *
     * @param string $entityType
     *      Name of bundle to attach fields to.
     * @param string $bundle
     *      Type of entity (e.g. node, user, comment).
     * @param string $factory
     *      Factory used to generate the content.
     *
     * @option langcode
     *      Language the entity should be made in. [default: site-default]
     *
     * @usage drush wmdummy-data:delete --all
     * @usage drush wmdummy-data:delete node page
     */
    public function delete(
        ?string $entityType = null,
        ?string $bundle = null,
        ?string $factory = null,
        array $options = ['langcode' => '']
    ): void {
        $storage = $this->entityTypeManager->getStorage('dummy_entity');
        $query = $storage->getQuery();

        if (
            !$entityType && !$bundle
            && !$this->confirm('Are you sure you want to delete all dummy content?')
        ) {
            return;
        }

        if ($entityType && $bundle) {
            $query->condition('entity_type', $entityType);
            $query->condition('entity_bundle', $bundle);

            if ($factory) {
                $query->condition('factory_name', $factory);
            } else {
                $factories = $this->entityFactoryManager->getNamesByEntityType($entityType, $bundle);
                $query->condition('factory_name', array_keys($factories), 'IN');
            }
        }

        if ($langcode = $this->input->getOption('langcode')) {
            $query->condition('entity_language', $langcode);
        }

        $query->accessCheck(false);

        $totalCount = (clone $query)->count()->execute();
        $ids = $query->execute();

        if (!empty($ids)) {
            $entities = $storage->loadMultiple($ids);
            $storage->delete($entities);
        }

        $this->logger()->success(
            sprintf('%s dummy entities have been annihilated.', $totalCount)
        );
    }
}
