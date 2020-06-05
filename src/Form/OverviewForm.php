<?php

namespace Drupal\wmdummy_data\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\wmdummy_data\DummyDataFactory;
use Drupal\wmmodel_factory\EntityFactoryPluginManager;
use Drupal\wmmodel_factory\EntityStatePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OverviewForm implements FormInterface, ContainerInjectionInterface
{
    use DependencySerializationTrait;
    use StringTranslationTrait;

    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var AccountProxyInterface */
    protected $currentUser;
    /** @var MessengerInterface */
    protected $messenger;
    /** @var EntityFactoryPluginManager */
    protected $entityFactoryManager;
    /** @var EntityStatePluginManager */
    protected $entityStateManager;
    /** @var DummyDataFactory */
    protected $factory;

    public static function create(ContainerInterface $container)
    {
        $instance = new static;
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->currentUser = $container->get('current_user');
        $instance->messenger = $container->get('messenger');
        $instance->entityFactoryManager = $container->get('plugin.manager.wmmodel_factory.factory');
        $instance->entityStateManager = $container->get('plugin.manager.wmmodel_factory.state');
        $instance->factory = $container->get('wmdummy_data.factory');

        return $instance;
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
            '#access' => $this->currentUser->hasPermission('generate dummy data'),
        ];

        $this->buildGenerateForm($form, $form_state);

        $form['delete'] = [
            '#type' => 'details',
            '#group' => 'tabs',
            '#title' => $this->t('Delete'),
            '#access' => $this->currentUser->hasPermission('delete dummy data'),
        ];

        $this->buildDeleteForm($form);

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $formState)
    {
        $name = $formState->getTriggeringElement()['#name'];

        if ($name === 'generate') {
            if ($formState->getValue('amount') === '') {
                $formState->setErrorByName('amount', 'You have to specify an amount.');
            }

            if ($formState->getValue('factory') === '') {
                $formState->setErrorByName('factory', 'You have to specify a factory.');
            }
        }

        if ($name === 'delete' && $formState->getValue('entity_type') === '') {
            $formState->setErrorByName('entity_type', 'You have to specify an entity type.');
        }
    }

    public function submitForm(array &$form, FormStateInterface $formState)
    {
    }

    public function generate(array &$form, FormStateInterface $formState): void
    {
        [$entityTypeId, $bundle, $name] = explode('.', $formState->getValue('factory'));

        $states = array_filter($formState->getValue('states'));
        $amount = $formState->getValue('amount');
        $generated = 0;

        while ($generated < $amount) {
            try {
                $this->factory
                    ->ofType($entityTypeId, $bundle, $name)
                    ->states($states)
                    ->create();
            } catch (\Exception $e) {
                $this->messenger->addError("An error occurred while generating: {$e->getMessage()}");
                break;
            }
            $generated++;
        }

        if ($generated > 0) {
            $entityType = $this->entityTypeManager
                ->getDefinition($entityTypeId);

            $this->messenger->addStatus($this->t('Successfully created @amount @entityTypeLabel.', [
                '@amount' => $generated,
                '@entityTypeLabel' => $generated > 1
                    ? $entityType->getPluralLabel()
                    : $entityType->getSingularLabel(),
            ]));
        }
    }

    public function delete(array &$form, FormStateInterface $formState): void
    {
        $entityTypeId = $formState->getValue('entity_type');
        $storage = $this->entityTypeManager->getStorage('dummy_entity');
        $ids = $this->getGeneratedEntityIds($entityTypeId);

        if (empty($ids)) {
            return;
        }

        $entities = $storage->loadMultiple($ids);
        $storage->delete($entities);

        $entityType = $this->entityTypeManager
            ->getDefinition($entityTypeId);

        $this->messenger->addStatus($this->t('Successfully deleted @amount @entityTypeLabel.', [
            '@amount' => count($ids),
            '@entityTypeLabel' => count($ids) > 1
                ? $entityType->getPluralLabel()
                : $entityType->getSingularLabel(),
        ]));
    }

    public function updateStates($form, FormStateInterface $formState)
    {
        $formState->setRebuild(true);

        return $form['generate']['wrapper'];
    }

    protected function buildGenerateForm(array &$form, FormStateInterface $formState): void
    {
        $form['generate']['wrapper'] = [
            '#type' => 'container',
            '#prefix' => '<div id="generate-wrapper">',
            '#suffix' => '</div>',
        ];

        $form['generate']['wrapper']['factory'] = [
            '#type' => 'select',
            '#title' => $this->t('Factory'),
            '#options' => $this->getFactoryOptions(),
            '#ajax' => [
                'callback' => '::updateStates',
                'wrapper' => 'generate-wrapper',
                'progress' => [
                    'type' => 'throbber',
                    'message' => $this->t('Updating states...'),
                ],
            ],
        ];

        $states = [];
        $factory = $formState->getUserInput()['factory'] ?? null;

        if ($factory) {
            $states = $this->getStateOptions($factory);
        }

        $form['generate']['wrapper']['states'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('States'),
            '#options' => $states,
            '#access' => !empty($states),
        ];

        $form['generate']['wrapper']['amount'] = [
            '#type' => 'number',
            '#title' => $this->t('Amount'),
        ];

        $form['generate']['wrapper']['actions']['generate'] = [
            '#type' => 'submit',
            '#value' => $this->t('Generate'),
            '#name' => 'generate',
            '#button_type' => 'primary',
            '#submit' => [
                [$this, 'generate'],
            ],
        ];
    }

    protected function buildDeleteForm(array &$form): void
    {
        $form['delete']['entity_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Entity type'),
            '#options' => $this->getEntityTypeOptions(),
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

    protected function getBundleEntity(array $definition): ?EntityInterface
    {
        $entityType = $this->entityTypeManager
            ->getDefinition($definition['entity_type']);

        if (!$entityType) {
            return null;
        }

        if (!$bundleEntityType = $entityType->getBundleEntityType()) {
            return null;
        }

        return $this->entityTypeManager
            ->getStorage($bundleEntityType)
            ->load($definition['bundle']);
    }

    protected function getGeneratedEntityIds(?string $entityTypeId = null): array
    {
        $storage = $this->entityTypeManager->getStorage('dummy_entity');
        $query = $storage->getQuery();

        if ($entityTypeId && $entityTypeId !== 'all') {
            $query->condition('entity_type', $entityTypeId);
        }

        return $query->execute();
    }

    protected function getFactoryOptions(): array
    {
        return array_reduce(
            $this->entityFactoryManager->getDefinitions(),
            function (array $options, array $definition): array {
                $entityType = $this->entityTypeManager
                    ->getDefinition($definition['entity_type']);
                $storage = $this->entityTypeManager
                    ->getStorage($definition['entity_type']);

                if ($bundle = $this->getBundleEntity($definition)) {
                    $id = implode('.', [
                        $definition['entity_type'],
                        $definition['bundle'],
                        $definition['name'],
                    ]);

                    $entity = $storage->create([
                        $entityType->getKey('bundle') => $definition['bundle'],
                    ]);

                    if ($definition['name'] === 'default') {
                        $label = '@entityType of type %bundle';
                    } else {
                        $label = '@entityType of type %bundle (@preset)';
                    }

                    if ($entity->access('create')) {
                        $options[$id] = new FormattableMarkup($label, [
                            '@entityType' => $entityType->getLabel(),
                            '%bundle' => $bundle->label(),
                            '@preset' => $definition['label'] ?? $definition['name'],
                        ]);
                    }

                    return $options;
                }

                $id = implode('.', [
                    $definition['entity_type'],
                    $definition['entity_type'],
                    $definition['name'],
                ]);

                try {
                    $entity = $storage->create();
                } catch (\Exception $e) {
                    // Sometimes, in case of a broken bundle definition,
                    // this breaks. Just skip the broken bundle.
                    return $options;
                }

                if ($definition['name'] === 'default') {
                    $label = '@entityType';
                } else {
                    $label = '@entityType (@preset)';
                }

                if ($entity->access('create')) {
                    $options[$id] = new FormattableMarkup($label, [
                        '@entityType' => $entityType->getLabel(),
                        '@preset' => $definition['label'] ?? $definition['name'],
                    ]);
                }

                return $options;
            },
            []
        );
    }

    protected function getStateOptions(string $factoryId): array
    {
        [$entityTypeId, $bundle] = explode('.', $factoryId);
        $names = $this->entityStateManager->getDefinitionsByEntityType($entityTypeId, $bundle);

        $names = array_map(static function (array $definition) {
            return $definition['label'] ?? $definition['name'];
        }, $names);

        return array_combine($names, $names);
    }

    protected function getEntityTypeOptions()
    {
        return array_reduce(
            $this->entityTypeManager->getDefinitions(),
            function (array $options, EntityTypeInterface $entityType): array {
                $ids = $this->getGeneratedEntityIds($entityType->id());

                if (!empty($ids)) {
                    $options[$entityType->id()] = $this->formatPlural(
                        count($ids),
                        '@entityType (1 entity)',
                        '@entityType (@count entities)',
                        ['@entityType' => $entityType->getLabel()]
                    );
                }

                return $options;
            },
            [
                'all' => $this->formatPlural(
                    count($this->getGeneratedEntityIds()),
                    'All (1 entity)',
                    'All (@count entities)'
                ),
            ]
        );
    }
}
