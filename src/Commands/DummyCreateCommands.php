<?php

namespace  Drupal\wmdummy_data\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\wmcontent\Entity\WmContentContainer;
use Drupal\wmcontent\WmContentManager;
use Drupal\wmdummy_data\DummyDataInterface;
use Drupal\wmdummy_data\Service\Generator\DummyDataGenerator;
use Drupal\wmsingles\Service\WmSingles;
use Drush\Commands\DrushCommands;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
    /** @var WmContentManager */
    protected $wmContentManager;
    /** @var wmSingles */
    protected $wmSingles;

    public function __construct(
        EntityTypeBundleInfo $entityTypeBundleInfo,
        DummyDataGenerator $dummyDataGenerator,
        EntityTypeManagerInterface $entityTypeManager,
        LanguageManagerInterface $languageManager
    ) {
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
        $this->dummyDataGenerator = $dummyDataGenerator;
        $this->entityTypeManager = $entityTypeManager;
        $this->languageManager = $languageManager;
    }

    public function setWmContentManager(WmContentManager $wmContentManager): void
    {
        $this->wmContentManager = $wmContentManager;
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
     * @option count
     *      Amount of entities that should be made.
     * @option langcode
     *      Language the entity should be made in. [default: site-default]
     *
     * @usage drush wmdummy-data:generate entity-type
     * @usage drush dummy entity-type bundle preset
     * @usage drush dummy entity-type bundle preset --count=2 --langcode=nl
     */
    public function generate(string $entityType, string $bundle, string $preset = DummyDataInterface::PRESET_BASIC, array $options = [
        'count' => '1',
        'langcode' => '',
    ]): void
    {
    }

    /** @hook interact wmdummy-data:generate */
    public function interact(InputInterface $input, OutputInterface $output, AnnotationData $annotationData): void
    {
        $entityType = $this->input->getArgument('entityType');
        $bundle = $this->input->getArgument('bundle');
        $preset = $this->input->getArgument('preset');
        $langcode = $this->getLangcode();

        if (!$entityType) {
            return;
        }

        if (!$bundle) {
            $bundle = $this->askBundle();
            $this->input->setArgument('bundle', $bundle);
        }

        if (!$preset || $preset === DummyDataInterface::PRESET_BASIC) {
            $this->input->setArgument('preset', $this->askPreset($entityType, $bundle, $langcode));
        }
    }

    /** @hook validate wmdummy-data:generate */
    public function validateEntityType(CommandData $commandData): void
    {
        $entityType = $this->input->getArgument('entityType');
        $bundle = $this->input->getArgument('bundle');
        $preset = $this->input->getArgument('preset');
        $count = $this->input->getOption('count');
        $langcode = $this->getLangcode();

        if (!$this->entityTypeExists($entityType)) {
            throw new InvalidArgumentException(
                t('Entity type with id \':entityType\' does not exist.', [':entityType' => $entityType])
            );
        }

        if (!$this->bundleExists($entityType, $bundle)) {
            throw new InvalidArgumentException(
                t('Bundle type with id \':bundle\' does not exist in the entity type \':entityType\'.', [':bundle' => $bundle, ':entityType' => $entityType])
            );
        }

        if (!is_numeric($count) || (float) $count <= 0) {
            throw new InvalidArgumentException(
                t('\':count\' is not a valid count.', [':count' => $count])
            );
        }

        if (!$this->dummyDataGenerator->presetExists($entityType, $bundle, $preset, $langcode)) {
            throw new InvalidArgumentException(
                t('Preset \':preset\' does not exist.', [':preset' => $preset])
            );
        }

        // TODO: Do general entity access check instead
        if (
            isset($this->wmSingles)
            && $entityType === 'node'
            && ($nodeType = $this->entityTypeManager->getStorage('node_type')->load($bundle))
            && $this->wmSingles->isSingle($nodeType)
        ) {
            throw new InvalidArgumentException('Cannot generate wmsingles.');
        }

        if (!$this->languageManager->getLanguage($langcode)) {
            throw new InvalidArgumentException(
                t('\':langcode\' is not a valid langcode.', [':langcode' => $langcode])
            );
        }
    }

    /** @hook process wmdummy-data:generate */
    public function process($result, CommandData $commandData): void
    {
        $entityType = $this->input->getArgument('entityType');
        $bundle = $this->input->getArgument('bundle');
        $count = (int) $this->input->getOption('count');
        $langcode = $this->getLangcode();
        $preset = $this->input->getArgument('preset');

        $this->logger()->success('Generating...');

        for ($x = 0; $x < $count; $x++) {
            $entity = $this->dummyDataGenerator->generateDummyData($entityType, $bundle, $preset, $langcode);

            if ($entity instanceof ContentEntityInterface) {
                $this->logResult($entity);
            }
        }

        $this->logger()->success(
            "Successfully made {$count} dummies for {$entityType} {$bundle} with preset {$preset}."
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

    protected function askPreset(string $entityType, string $bundle, string $langcode)
    {
        $choices = [];

        $presets = array_filter(
            $this->dummyDataGenerator->getPresets(),
            function (array $preset) use ($entityType, $bundle, $langcode) {
                return $preset['entity_type'] === $entityType
                    && $preset['bundle'] === $bundle
                    && $preset['langcode'] === $langcode;
            }
        );

        if (empty($presets)) {
            $choices[DummyDataInterface::PRESET_BASIC] = DummyDataInterface::PRESET_BASIC;
        }

        foreach ($presets as $preset) {
            $choices[$preset['preset']] = $preset['preset'];
        }

        if (count($choices) === 1) {
            return reset($choices);
        }

        return $this->io()->choice('Preset', $choices);
    }

    protected function bundleExists(string $entityType, string $bundleName): bool
    {
        return isset($this->entityTypeBundleInfo->getBundleInfo($entityType)[$bundleName]);
    }

    protected function entityTypeExists(string $id): bool
    {
        try {
            $this->entityTypeManager->getDefinition($id);
        } catch (PluginNotFoundException $e) {
            return false;
        }

        return true;
    }

    protected function logResult(ContentEntityInterface $entity): void
    {
        $createdContent = array_reduce(
            $this->wmContentManager->getHostContainers($entity),
            function (int $count, WmContentContainer $container) use ($entity) {
                return $count + count($this->wmContentManager->getContent($entity, $container->id()));
            },
            0
        );

        $this->logger()->success(
            'Generated entity ' . $entity->bundle() . ' with id ' . $entity->id() . ' and ' . $createdContent . ' content blocks. '
            . PHP_EOL
            . 'Further customisation can be done at the following url:'
            . PHP_EOL
            . $entity->toUrl('edit-form')
                ->setAbsolute(true)
                ->toString()
        );
    }

    protected function getLangcode(): string
    {
        $langcode = $this->input->getOption('langcode');
        if (empty($langcode)) {
            $langcode = $this->languageManager->getDefaultLanguage()->getId();
        }

        return $langcode;
    }
}
