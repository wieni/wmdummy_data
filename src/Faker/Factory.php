<?php

namespace Drupal\wmdummy_data\Faker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\wmdummy_data\Faker\Provider\DrupalEntity;
use Drupal\wmdummy_data\Faker\Provider\Html;
use Drupal\wmdummy_data\Faker\Provider\RandomElementWeight;
use Drupal\wmdummy_data\Faker\Provider\VimeoVideo;
use Drupal\wmdummy_data\Faker\Provider\YouTubeVideo;
use Faker\Factory as FactoryBase;
use Faker\Generator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Factory implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var ImmutableConfig */
    protected $config;

    public function __construct(
        ConfigFactoryInterface $configFactory
    ) {
        $this->config = $configFactory->get('wmdummy_data.settings');
    }

    public function create(): Generator
    {
        $locale = $this->config->get('faker.locale') ?? FactoryBase::DEFAULT_LOCALE;
        $generator = FactoryBase::create($locale);

        $generator->addProvider(
            new DrupalEntity(
                $generator,
                $this->container,
                $this->container->get('entity_type.manager'),
                $this->container->get('entity_type.repository')
            )
        );

        $generator->addProvider(
            new Html(
                $generator
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
