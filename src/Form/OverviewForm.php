<?php

namespace Drupal\wmdummy_data\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\wmdummy_data\DummyDataFactory;
use Drupal\wmmodel_factory\EntityFactoryPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OverviewForm implements FormInterface, ContainerInjectionInterface
{
    use StringTranslationTrait;

    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var AccountProxyInterface */
    protected $currentUser;
    /** @var MessengerInterface */
    protected $messenger;
    /** @var EntityFactoryPluginManager */
    protected $entityFactoryManager;
    /** @var DummyDataFactory */
    protected $factory;

    public static function create(ContainerInterface $container)
    {
        $instance = new static;
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->currentUser = $container->get('current_user');
        $instance->messenger = $container->get('messenger');
        $instance->entityFactoryManager = $container->get('plugin.manager.wmmodel_factory.factory');
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

        $this->buildGenerateForm($form);

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
        $amount = $formState->getValue('amount');
        $generated = 0;

        while ($generated < $amount) {
            try {
                $this->factory
                    ->ofType($entityTypeId, $bundle, $name)
                    ->create();
            } catch (\Exception $e) {
                $this->messenger->addError("An error occurred while generating: {$e->getMessage()}");
                break;
            }
            $generated++;
        }

        $this->messenger->addStatus("Successfully created {$generated} entities for {$entityTypeId} {$bundle} with factory {$name}.");
    }

    public function delete(array &$form, FormStateInterface $formState): void
    {
        $entityTypeId = $formState->getValue('entity_type');
        $storage = $this->entityTypeManager->getStorage('dummy_entity');
        $ids = $this->getGeneratedEntityIds($entityTypeId);
        $count = count($ids);

        if (!empty($ids)) {
            $entities = $storage->loadMultiple($ids);
            $storage->delete($entities);
        }

        $this->messenger->addStatus(
            "Successfully destroyed {$count} entities for entity type {$entityTypeId}."
        );
    }

    protected function buildGenerateForm(array &$form)
    {
        $options = array_reduce(
            $this->entityFactoryManager->getDefinitions(),
            function (array $options, array $definition): array {
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
                    $definition['name'],
                ]);

                if ($definition['name'] === 'default') {
                    $label = '@entityType %bundle';
                } else {
                    $label = '@entityType %bundle (@preset)';
                }

                $entity = $storage->create([$entityType->getKey('bundle') => $definition['bundle']]);

                if ($entity->access('create')) {
                    $options[$id] = new FormattableMarkup($label, [
                        '@entityType' => $entityType->getLabel(),
                        '%bundle' => $bundle->label(),
                        '@preset' => $definition['name'],
                    ]);
                }

                return $options;
            },
            []
        );

        $form['generate']['factory'] = [
            '#type' => 'select',
            '#title' => $this->t('Factory'),
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
                $this->entityTypeManager->getDefinitions(),
                function (array $options, EntityTypeInterface $entityType): array {
                    $ids = $this->getGeneratedEntityIds($entityType->id());

                    if (empty($ids)) {
                        return $options;
                    }

                    return $options + [
                            $entityType->id() => $this->formatPlural(
                                count($ids),
                                '@entityType (1 entity)',
                                '@entityType (@count entities)',
                                ['@entityType' => $entityType->getLabel()]
                            ),
                        ];
                },
                [
                    'all' => $this->formatPlural(
                        count($this->getGeneratedEntityIds()),
                        'All (1 entity)',
                        'All (@count entities)'
                    ),
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

    protected function getGeneratedEntityIds(?string $entityTypeId = null): array
    {
        $storage = $this->entityTypeManager->getStorage('dummy_entity');
        $query = $storage->getQuery();

        if ($entityTypeId && $entityTypeId !== 'all') {
            $query->condition('entity_type', $entityTypeId);
        }

        return $query->execute();
    }
}
