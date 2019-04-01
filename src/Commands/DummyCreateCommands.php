<?php

namespace  Drupal\wmdummy_data\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\wmdummy_data\Service\Generator\DummyDataGenerator;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\wmsingles\Service\WmSingles;

class DummyCreateCommands extends DrushCommands
{
    /** @var EntityTypeBundleInfo */
    protected $entityTypeBundleInfo;
    /** @var DummyDataGenerator */
    protected  $dummyDataGenerator;
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var LanguageManagerInterface */
    protected $languageManagerInterface;
    /** @var wmSingles */
    protected $wmSingles;

    public function __construct(
        EntityTypeBundleInfo $entityTypeBundleInfo,
        DummyDataGenerator $dummyDataGenerator,
        EntityTypeManagerInterface $entityTypeManager,
        LanguageManagerInterface $languageManagerInterface
    ) {
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
        $this->dummyDataGenerator = $dummyDataGenerator;
        $this->entityTypeManager = $entityTypeManager;
        $this->languageManagerInterface = $languageManagerInterface;
    }

    /** Set wmsingle manager
     * @param \Drupal\\wmsingles\Service\WmSingles $wmsinglesManager
     */
    public function setWmsinglesManager(wmSingles $wmSingles): void
    {
        $this->wmSingles = $wmSingles;
    }

    /**
     * Command to generate entities by <entity-type> <bundle>. // does not make translatable yet
     *
     * @command wmdummy-data:generate
     * @aliases dummy
     *
     * @param string $entityType
     *      Name of bundle to attach fields to.
     * @param string $bundle
     *      Type of entity (e.g. node, user, comment).
     *
     * @option count
     *      Amount of entities that should be made.
     * @option langcode
     *      Language the entity should be made in. [default: site-default]
     *
     * @usage drush wmdummy-data:generate entity-type
     * @usage drush dummy entity-type bundle
     * @usage drush dummy entity-type bundle --count=2 --langcode=nl
     *
     */
    public function generate($entityType, $bundle, $options = [
        'count' => '1',
        'langcode' => '',
    ]): void {
    }

    /**
     * @hook interact wmdummy-data:generate
     */
    public function interact(InputInterface $input, OutputInterface $output, AnnotationData $annotationData): void
    {
        $entityType = $this->input->getArgument('entityType');
        $bundle = $this->input->getArgument('bundle');

        if (!$entityType) {
            return; // returns if no entity type is given
        }

        if (!$bundle || !$this->entityTypeBundleExists($entityType, $bundle)) { // ask bundle if bundle does not exist or not given.
            $this->input->setArgument('bundle', $this->askBundle());
        }
    }
    /**
     * @hook validate wmdummy-data:generate
     */
    public function validateEntityType(CommandData $commandData): void
    {
        $entityType = $this->input->getArgument('entityType');
        $bundle = $this->input->getArgument('bundle');
        $count = $this->input->getOption('count');
        $langcode = $this->input->getOption('langcode');

        // check if entity type exists
        if (!$this->entityTypeManager->hasDefinition($entityType)) {
            throw new \InvalidArgumentException(
                t('Entity type with id \':entityType\' does not exist.', [':entityType' => $entityType])
            );
        }

        // check if bundle exists in entity
        if (!isset($this->entityTypeBundleInfo->getBundleInfo($entityType)[$bundle])) {
            throw new \InvalidArgumentException(
                t('Bundle type with id \':bundleType\' does not exist in the entity type \':entityType\'.', [':bundleType' => $bundle, ':entityType' => $entityType])
            );
        }

        if (
            !is_numeric($count) ||
            (float)$count <= 0
        ) { // check if count given is a whole count !! decimals still unhandled
            throw new \InvalidArgumentException(
                t('\':count\' is not a valid count.', [':count' => $count])
            );
        }

        if (isset($this->wmSingles)) {
            $bundleTypeId = $this->entityTypeManager->getDefinition($entityType)->getBundleEntityType();
            $type = $this->entityTypeManager->getStorage($bundleTypeId)->load($bundle);
            if ($entityType === 'node') {
                if ($this->wmSingles->isSingle($type)) {
                    throw new \Exception('This entity is a Single.');
                }
            }
        }

        if (!empty($langcode)) {
            $validLanguages = $this->languageManagerInterface->getLanguages();
            if (!array_key_exists($langcode, $validLanguages)) {
                throw new \InvalidArgumentException(
                    t('\':langcode\' is not a valid langcode.', [':langcode' => $langcode])
                );
            }
        }
    }

    /**
     * @hook process wmdummy-data:generate
     */
    public function process($result, CommandData $commandData): void
    {
        $entityType = $this->input->getArgument('entityType');
        $bundle = $this->input->getArgument('bundle');
        $count = $this->input->getOption('count');
        $langcode = $this->input->getOption('langcode');

        $count = (int)$count; // typing $count into an int

        if (empty($langcode)) {
            $langcode = $this->languageManagerInterface->getDefaultLanguage()->getId();
        }

        $created = $this->dummyDataGenerator->generateDummyData($entityType, $bundle, $count, $langcode);

        $this->logResult($created, $count, $entityType, $bundle);
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

        try {
            return $this->io()->choice('Bundle', $choices);
        } catch (UserAbortException $e) {
            throw new \InvalidArgumentException(
                $e
            );
        }
    }

    protected function entityTypeBundleExists(string $entityType, string $bundleName): bool
    {
        return isset($this->entityTypeBundleInfo->getBundleInfo($entityType)[$bundleName]);
    }

    private function logResult( array $created, int $count, string $entityType, string $bundle): void
    {
        $stringCreated = 'Successfully made '.$count.' dummies for '.$entityType.' '.$bundle.': ';
        $this->logger()->success(
            $stringCreated
        );
        foreach ($created as $key => $bundlesAmount) {
            $entity = $this->entityTypeManager->getStorage($entityType)->load($key);
            $stringCreated = 'Generated entity with id '.$key.' and '.$bundlesAmount.' content blocks. '
                . PHP_EOL
                .'Further customisation can be done at the following url:'
                . PHP_EOL
                . $entity->toUrl()
                    ->setAbsolute(true)
                    ->toString();
            $this->logger()->success(
                $stringCreated
            );
        }
    }
}