<?php

namespace  Drupal\wmdummy_data\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\wmcontent\Entity\WmContentContainer;
use Drupal\wmcontent\WmContentManager;
use Drupal\wmdummy_data\DummyDataFactory;
use Drupal\wmmodel\Factory\ModelFactoryInterface;
use Drupal\wmmodel_factory\EntityFactoryPluginManager;
use Drush\Commands\DrushCommands;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DummyCreateCommands extends DrushCommands
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EntityTypeBundleInfo */
    protected $entityTypeBundleInfo;
    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var EntityFactoryPluginManager */
    protected $entityFactoryManager;
    /** @var ModelFactoryInterface */
    protected $modelFactory;
    /** @var DummyDataFactory */
    protected $factory;

    /** @var WmContentManager */
    protected $wmContentManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityTypeBundleInfo $entityTypeBundleInfo,
        LanguageManagerInterface $languageManager,
        ModelFactoryInterface $modelFactory,
        EntityFactoryPluginManager $factoryPluginManager,
        DummyDataFactory $factory
    ) {
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
        $this->entityTypeManager = $entityTypeManager;
        $this->languageManager = $languageManager;
        $this->modelFactory = $modelFactory;
        $this->entityFactoryManager = $factoryPluginManager;
        $this->factory = $factory;
    }

    public function setWmContentManager(WmContentManager $wmContentManager): void
    {
        $this->wmContentManager = $wmContentManager;
    }

    /**
     * Command to generate entities.
     *
     * @command wmdummy-data:generate
     * @aliases dummy
     *
     * @param string $entityType
     *      Name of bundle to attach fields to.
     * @param string $bundle
     *      Type of entity (e.g. node, user, comment).
     * @param string $factory
     *      Factory used to generate the content.
     * @option count
     *      Amount of entities that should be made.
     * @option langcode
     *      Language the entity should be made in. [default: site-default]
     * @option states
     *      The states to use when generating the entity
     *
     * @usage drush wmdummy-data:generate entity-type
     * @usage drush dummy entity-type bundle name
     * @usage drush dummy entity-type bundle name --count=2 --langcode=nl
     */
    public function generate(
        string $entityType,
        string $bundle,
        string $factory = 'default',
        array $options = ['count' => '1', 'langcode' => '', 'states' => []]
    ): void {
    }

    /** @hook interact wmdummy-data:generate */
    public function interact(InputInterface $input, OutputInterface $output, AnnotationData $annotationData): void
    {
        $entityType = $this->input->getArgument('entityType');
        $bundle = $this->input->getArgument('bundle');
        $factory = $this->input->getArgument('factory');

        if (!$entityType) {
            return;
        }

        if (!$bundle) {
            $bundle = $this->askBundle();
            $this->input->setArgument('bundle', $bundle);
        }

        if (!$factory) {
            $this->input->setArgument('factory', $this->askFactory($entityType, $bundle));
        }
    }

    /** @hook validate wmdummy-data:generate */
    public function validateEntityType(CommandData $commandData): void
    {
        $entityTypeId = $this->input->getArgument('entityType');
        $bundle = $this->input->getArgument('bundle');
        $factory = $this->input->getArgument('factory');
        $count = $this->input->getOption('count');
        $langcode = $this->input->getOption('langcode');

        if (!$this->bundleExists($entityTypeId, $bundle)) {
            throw new InvalidArgumentException(
                t('Bundle type with id \':bundle\' does not exist in the entity type \':entityType\'.', [':bundle' => $bundle, ':entityType' => $entityType])
            );
        }

        if (!is_numeric($count) || (float) $count <= 0) {
            throw new InvalidArgumentException(
                t('\':count\' is not a valid count.', [':count' => $count])
            );
        }

        if (!$this->entityFactoryManager->getPluginIdByEntityType($entityTypeId, $bundle, $factory)) {
            throw new InvalidArgumentException(
                t('Factory with name \':name\' does not exist.', [':name' => $factory])
            );
        }

        if ($langcode && !$this->languageManager->getLanguage($langcode)) {
            throw new InvalidArgumentException(
                t('\':langcode\' is not a valid langcode.', [':langcode' => $langcode])
            );
        }
    }

    /** @hook process wmdummy-data:generate */
    public function process($result, CommandData $commandData): void
    {
        $entityTypeId = $this->input->getArgument('entityType');
        $bundle = $this->input->getArgument('bundle');
        $count = (int) $this->input->getOption('count');
        $states = $this->input->getOption('states');
        $langcode = $this->input->getOption('langcode');
        $factory = $this->input->getArgument('factory');

        $this->logger()->success('Generating...');

        for ($x = 0; $x < $count; $x++) {
            $entity = $this->factory
                ->ofType($entityTypeId, $bundle, $factory)
                ->langcode($langcode)
                ->states($states)
                ->create();

            if ($entity instanceof ContentEntityInterface) {
                $this->logResult($entity);
            }
        }

        $this->logger()->success(
            "Successfully made {$count} dummies for {$entityTypeId} {$bundle} with factory {$factory}."
        );
    }

    protected function askBundle()
    {
        $entityType = $this->input->getArgument('entityType');
        $bundleInfo = $this->entityTypeBundleInfo->getBundleInfo($entityType);
        $choices = [];

        foreach ($bundleInfo as $bundle => $data) {
            $label = $data['label'];
            $choices[$bundle] = $label;
        }

        return $this->io()->choice('Bundle', $choices);
    }

    protected function askFactory(string $entityType, string $bundle)
    {
        $names = $this->entityFactoryManager->getNamesByEntityType($entityType, $bundle);
        $choices = array_combine($names, $names);

        if (count($choices) === 1) {
            return reset($choices);
        }

        return $this->io()->choice('Factory', $choices);
    }

    protected function bundleExists(string $entityType, string $bundleName): bool
    {
        return isset($this->entityTypeBundleInfo->getBundleInfo($entityType)[$bundleName]);
    }

    protected function logResult(ContentEntityInterface $entity): void
    {
        if (isset($this->wmContentManager)) {
            $createdContent = array_reduce(
                $this->wmContentManager->getHostContainers($entity),
                function (int $count, WmContentContainer $container) use ($entity) {
                    return $count + count($this->wmContentManager->getContent($entity, $container->id()));
                },
                0
            );

            $message = sprintf(
                'Generated entity %s with id %s and %s content blocks.%s',
                $entity->bundle(),
                $entity->id(),
                $createdContent,
                PHP_EOL
            );
        } else {
            $message = sprintf(
                'Generated entity %s with id %s.%s',
                $entity->bundle(),
                $entity->id(),
                PHP_EOL
            );
        }

        if ($entity->hasLinkTemplate('edit-form')) {
            $message .= 'Further customisation can be done at the following url:';
            $message .= PHP_EOL;
            $message .= $entity->toUrl('edit-form')->setAbsolute(true)->toString();
        }

        $this->logger()->success($message);
    }
}
