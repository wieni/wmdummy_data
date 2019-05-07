<?php

namespace  Drupal\wmdummy_data\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\wmdummy_data\DummyDataManager;
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
    protected $dummyDataGenerator;
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var wmSingles */
    protected $wmSingles;
    /** @var DummyDataManager */
    protected $dummyDataManager;

    public function __construct(
        EntityTypeBundleInfo $entityTypeBundleInfo,
        DummyDataGenerator $dummyDataGenerator,
        EntityTypeManagerInterface $entityTypeManager,
        LanguageManagerInterface $languageManager,
        DummyDataManager $dummyDataManager
    ) {
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
        $this->dummyDataGenerator = $dummyDataGenerator;
        $this->entityTypeManager = $entityTypeManager;
        $this->languageManager = $languageManager;
        $this->dummyDataManager = $dummyDataManager;
    }

    public function setWmSinglesManager(wmSingles $wmSingles): void
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
     * @param string $preset
     *      Preset used to generate the content.
     * @param array $options
     * @option count
     *      Amount of entities that should be made.
     * @option langcode
     *      Language the entity should be made in. [default: site-default]
     *
     * @usage drush wmdummy-data:generate entity-type
     * @usage drush dummy entity-type bundle preset
     * @usage drush dummy entity-type bundle preset --count=2 --langcode=nl
     */
    public function generate(string $entityType, string $bundle, string $preset, array $options = [
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
        $preset = $this->input->getArgument('preset');
        $langcode = $this->getLangcode();


        if (!$entityType) {
            return; // returns if no entity type is given
        }

        if (!$bundle || !$this->entityTypeBundleExists($entityType, $bundle)) { // ask bundle if bundle does not exist or not given.
            $this->input->setArgument('bundle', $this->askBundle());
            $bundle = $this->input->getArgument('bundle');
        }

        if (!$preset && $this->dummyDataGenerator->presetExists($entityType, $bundle, 'default', $langcode)) { // if no preset is given and a default preset exists then use default
            $this->input->setArgument('preset', 'default');
        } elseif ($preset !== 'basic' && !$this->dummyDataGenerator->presetExists($entityType, $bundle, $preset, $langcode)) {
            $this->input->setArgument('preset', $this->askPreset($entityType, $bundle));
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
            if ($bundleTypeId !== null) {
                $type = $this->entityTypeManager->getStorage($bundleTypeId)->load($bundle);
                if ($entityType === 'node' && $this->wmSingles->isSingle($type)) {
                    throw new \Exception('This entity is a Single.');
                }
            }
        }

        if (!empty($langcode)) {
            $validLanguages = $this->languageManager->getLanguages();
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
        $langcode = $this->getLangcode();
        $preset = $this->input->getArgument('preset');

        $count = (int)$count; // typing $count into an int

        $this->logger()->success('Generating...');

        for ($x = 0; $x < $count; $x++) {
            $created = $this->dummyDataGenerator->generateDummyData($entityType, $bundle, $preset,  $langcode);
            $this->logResult($created, $entityType, $bundle);
        }

        $this->logFinalResult($count, $entityType, $bundle, $preset);
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

    protected function askPreset(string $entityType, string $bundle)
    {
        $choices = ['basic' => 'basic'];

        $entityPresets = $this->dummyDataManager->getDefinitions();
        $langcode = $this->getLangcode();

        foreach ($entityPresets as $entityPreset) {
            $presetArray = explode('.', $entityPreset['id']);
            if ($presetArray[0] === $entityType && $presetArray[1] === $bundle) {
                if (
                    !isset($entityPreset['langcode']) ||
                    $entityPreset['langcode'] === $langcode
                ) {
                    if ($presetArray[2]) {
                        $choices[$presetArray[2]] = $presetArray[2];
                    } else {
                        $choices['default'] = 'default';
                    }
                }
            }
        }

        try {
            return $this->io()->choice('Preset', $choices);
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

    private function logResult( array $created, string $entityType, string $bundle): void
    {
        foreach ($created as $key => $bundlesAmount) {
            $entity = $this->entityTypeManager->getStorage($entityType)->load($key);
            $stringCreated = 'Generated entity '.$bundle.' with id '.$key.' and '.$bundlesAmount.' content blocks. '
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

    private function logFinalResult(int $count, string $entityType, string $bundle, string $preset): void
    {
        $stringCreated = "Successfully made {$count} dummies for {$entityType} {$bundle} with preset {$preset}.";
        $this->logger()->success(
            $stringCreated
        );
    }

    private function getLangcode(): string
    {
        $langcode = $this->input->getOption('langcode');
        if (empty($langcode)) {
            $langcode = $this->languageManager->getDefaultLanguage()->getId();
        }

        return $langcode;
    }

}
