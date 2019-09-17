<?php

namespace Drupal\wmdummy_data\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\wmdummy_data\DummyDataInterface;
use Drupal\wmdummy_data\DummyDataManager;
use Drupal\wmdummy_data\Service\Generator\DummyDataGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OverviewForm extends FormBase
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var MessengerInterface */
    protected $messenger;
    /** @var DummyDataManager */
    protected $manager;
    /** @var DummyDataGenerator */
    protected $generator;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        MessengerInterface $messenger,
        DummyDataManager $manager,
        DummyDataGenerator $generator
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->messenger = $messenger;
        $this->manager = $manager;
        $this->generator = $generator;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('messenger'),
            $container->get('plugin.manager.dummy_data'),
            $container->get('wmdummy_data.dummy_data_generator')
        );
    }

    public function getFormId()
    {
        return 'wmdummy_data_overview';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['tabs'] = [
            '#type' => 'vertical_tabs',
        ];

        $form['generate'] = [
            '#type' => 'details',
            '#group' => 'tabs',
            '#title' => $this->t('Generate'),
            '#access' => $this->currentUser()->hasPermission('generate dummy data'),
        ];

        $this->buildGenerateForm($form);

        $form['delete'] = [
            '#type' => 'details',
            '#group' => 'tabs',
            '#title' => $this->t('Delete'),
            '#access' => $this->currentUser()->hasPermission('delete dummy data'),
        ];

        $this->buildDeleteForm($form);

        return $form;
    }

    protected function buildGenerateForm(array &$form)
    {
        $options = array_reduce(
            $this->manager->getDefinitions(),
            function (array $options, array $definition) {
                $entityType = $this->entityTypeManager
                    ->getDefinition($definition['entity_type']);
                $bundle = $this->entityTypeManager
                    ->getStorage($entityType->getBundleEntityType())
                    ->load($definition['bundle']);
                $storage = $this->entityTypeManager
                    ->getStorage($definition['entity_type']);

                $id = implode('.', [
                    $definition['entity_type'],
                    $definition['bundle'],
                    $definition['preset'],
                ]);

                if ($definition['preset'] === DummyDataInterface::PRESET_DEFAULT) {
                    $label = '@entityType %bundle';
                } else {
                    $label = '@entityType %bundle (@preset)';
                }

                $entity = $storage->create([$entityType->getKey('bundle') => $definition['bundle']]);

                if ($entity->access('create')) {
                    $options[$id] = new FormattableMarkup($label, [
                        '@entityType' => $entityType->getLabel(),
                        '%bundle' => $bundle->label(),
                        '@preset' => $definition['preset'],
                    ]);
                }

                return $options;
            },
            []
        );

        $form['generate']['generator'] = [
            '#type' => 'select',
            '#title' => $this->t('Generator'),
            '#options' => $options,
        ];

        $form['generate']['amount'] = [
            '#type' => 'number',
            '#title' => $this->t('Amount'),
        ];

        $form['generate']['actions']['generate'] = [
            '#type' => 'submit',
            '#value' => $this->t('Generate'),
            '#name' => 'generate',
            '#button_type' => 'primary',
            '#submit' => [
                [$this, 'generate'],
            ],
        ];
    }

    protected function buildDeleteForm(array &$form)
    {
        $form['delete']['entity_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Entity type'),
            '#options' => array_reduce(
                $this->generator->getGeneratedEntityTypes(),
                function (array $options, string $entityTypeId) {
                    $entityType = $this->entityTypeManager->getDefinition($entityTypeId);

                    return $options + [
                            $entityTypeId => $this->formatPlural(
                                count($this->generator->getGeneratedEntityIds($entityTypeId)),
                                '@entityType (1 entity)',
                                '@entityType (@count entities)',
                                ['@entityType' => $entityType->getLabel()]
                            )
                        ];
                },
                [
                    'all' => $this->formatPlural(
                        count($this->generator->getGeneratedEntityIds()),
                        'All (1 entity)',
                        'All (@count entities)'
                    )
                ]
            ),
        ];

        $form['delete']['actions']['delete'] = [
            '#type' => 'submit',
            '#value' => $this->t('Delete'),
            '#name' => 'delete',
            '#button_type' => 'danger',
            '#submit' => [
                [$this, 'delete'],
            ],
        ];
    }

    public function submitForm(array &$form, FormStateInterface $formState)
    {
    }

    public function generate(array &$form, FormStateInterface $formState): void
    {
        [$entityType, $bundle, $preset] = explode('.', $formState->getValue('generator'));
        $amount = $formState->getValue('amount');
        $generated = 0;

        while ($generated < $amount) {
            try {
                $this->generator->generateDummyData($entityType, $bundle, $preset);
            } catch (\Exception $e) {
                $this->messenger->addError("An error occurred while generating: {$e->getMessage()}");
                break;
            }
            $generated++;
        }

        $this->messenger->addStatus("Successfully made {$generated} dummies for {$entityType} {$bundle} with preset {$preset}.");
    }

    public function delete(array &$form, FormStateInterface $formState): void
    {
        $entityType = $formState->getValue('entity_type');

        if ($entityType === 'all') {
            $entityTypes = $this->generator->getGeneratedEntityTypes();
        } else {
            $entityTypes = [$entityType];
        }

        foreach ($entityTypes as $entityType) {
            $count = $this->generator->deleteGeneratedEntities($entityType);

            $this->messenger->addStatus(
                "Successfully destroyed {$count} dummies for entity type {$entityType}."
            );
        }
    }
}
