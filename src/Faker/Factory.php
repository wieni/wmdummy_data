<?php

namespace Drupal\wmdummy_data\Faker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\wmdummy_data\Faker\Provider\DrupalEntity;
use Drupal\wmdummy_data\Faker\Provider\RandomElementWeight;
use Drupal\wmdummy_data\Faker\Provider\VimeoVideo;
use Drupal\wmdummy_data\Faker\Provider\YouTubeVideo;
use Drupal\wmdummy_data\Service\Generator\DummyDataGenerator;
use Faker\Generator;
use Faker\Factory as FactoryBase;

class Factory
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var DummyDataGenerator */
    protected $dummyDataGenerator;
    /** @var ImmutableConfig */
    protected $config;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        LanguageManagerInterface $languageManager,
        ConfigFactoryInterface $configFactory,
        DummyDataGenerator $dummyDataGenerator
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->languageManager = $languageManager;
        $this->dummyDataGenerator = $dummyDataGenerator;
        $this->config = $configFactory->get('wmdummy_data.settings');
    }

    public function create(): Generator
    {
        $locale = $this->config->get('faker.locale') ?? FactoryBase::DEFAULT_LOCALE;
        $generator = FactoryBase::create($locale);

        $generator->addProvider(
            new DrupalEntity(
                $generator,
                $this->entityTypeManager,
                $this->languageManager,
                $this->dummyDataGenerator
            )
        );

        $generator->addProvider(
            new RandomElementWeight(
                $generator
            )
        );

        $generator->addProvider(
            new VimeoVideo(
                $generator
            )
        );

        $generator->addProvider(
            new YouTubeVideo(
                $generator
            )
        );

        return $generator;
    }
}
