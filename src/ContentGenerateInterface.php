<?php

namespace Drupal\wmdummy_data;

use Drupal\wmmodel_factory\FactoryBuilder;

interface ContentGenerateInterface
{
    /**
     * Generate content for a wmcontent container. This method will be called for
     * every parent container of this entity. If no children should be generated,
     * just return an empty array.
     *
     * @return FactoryBuilder[]
     */
    public function generateContent(\Drupal\wmcontent\Entity\WmContentContainer $container): array;
}
