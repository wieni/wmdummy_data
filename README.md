<a href="https://www.wieni.be">
    <img src="https://www.wieni.be/themes/custom/drupack/logo.svg" alt="Wieni logo" title="Wieni" align="right" height="60" />
</a>

wmdummy_data
======================

[![Latest Stable Version](https://poser.pugx.org/wieni/wmdummy_data/v/stable)](https://packagist.org/packages/wieni/wmdummy_data)
[![Total Downloads](https://poser.pugx.org/wieni/wmdummy_data/downloads)](https://packagist.org/packages/wieni/wmdummy_data)
[![License](https://poser.pugx.org/wieni/wmdummy_data/license)](https://packagist.org/packages/wieni/wmdummy_data)

> Provides Drupal services and Drush 9 commands for easy creation of dummy data.

## Installation
This package requires PHP 7.1 and Drupal 8.7.7 or higher. It can be
installed using Composer:

```bash
 composer require wieni/wmdummy_data
```

## How does it work?
### Creating factories and states
To learn more about how to define your factory and state classes, please refer to the 
[`wieni/wmmodel_factory`](https://github.com/wieni/wmmodel_factory) docs.

In order to have access to the following functionality, you need to extend 
[`EntityFactoryBase`](src/EntityFactoryBase.php) or [`EntityStateBase`](src/EntityStateBase.php) instead of the classes 
provided by `wmmodel_factory`.

#### Generate content using [`wieni/wmcontent`](https://github.com/wieni/wmcontent)
To generate content for an entity using the `wmcontent` module, make your generator implement 
[`ContentGenerateInterface`](src/ContentGenerateInterface.php).

##### Example
```php
<?php

namespace Drupal\my_module\Entity\ModelFactory\Factory\Node;

use Drupal\wmcontent\Entity\WmContentContainer;
use Drupal\wmdummy_data\EntityFactoryBase;

/**
 * @EntityFactory(
 *     entity_type = "node",
 *     bundle = "page",
 * )
 */
class PageFactory extends EntityFactoryBase implements ContentGenerateInterface
{
    public function make(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'menu_link' => null,
        ];
    }
    
    public function generateContent(WmContentContainer $container): array
    {
        $entityType = $container->getChildEntityType();
        $bundles = $container->getChildBundles() ?: $container->getChildBundlesAll();
        $amount = $this->faker->numberBetween(1, 10);

        return array_map(
            fn () => $this->faker->entityWithType($entityType, $this->faker->randomElement($bundles)),
            array_fill(0, $amount, null)
        );
    }
}
```

#### Generate entities to reference
To populate entity reference fields with new or existing entities, use the following methods in your generator:
- `$this->faker->entity()` (passing the class name of a [wmmodel](https://github.com/wieni/wmmodel) bundle class)
- `$this->faker->entityWithType()` (passing entity type ID and optionally bundle as strings)

##### Example
```php
<?php

namespace Drupal\my_module\Entity\ModelFactory\Factory\Node;

use Drupal\my_module\Entity\TaxonomyTerm\Tag;
use Drupal\wmdummy_data\EntityFactoryBase;

/**
 * @EntityFactory(
 *     entity_type = "node",
 *     bundle = "page",
 * )
 */
class PageFactory extends EntityFactoryBase
{
    public function make(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'menu_link' => null,
            'field_tag' => [
                'entity' => $this->faker->entity(Tag::class),
            ],
        ];
    }
}
```

#### Generate links to Vimeo and YouTube videos
To get access to existing links to Vimeo and YouTube videos in your generators, use the following methods:
- `$this->faker->vimeoUrl`
- `$this->faker->youTubeUrl`

##### Example
```php
<?php

namespace Drupal\my_module\Entity\ModelFactory\Factory\ContentBlock;

use Drupal\wmdummy_data\EntityFactoryBase;

/**
 * @EntityFactory(
 *     entity_type = "content_block",
 *     bundle = "video",
 * )
 */
class VideoFactory extends EntityFactoryBase
{
    public function make(): array
    {
        $data = [
            'field_video_title' => $this->faker->optional()->sentence($this->faker->numberBetween(4, 8)),
            'field_video_type' => $this->faker->randomElement(['youtube', 'vimeo']),
        ];

        switch ($data['field_video_type']) {
            case 'vimeo':
                $data['field_video_vimeo'] = $this->faker->vimeoUrl;
                break;
            case 'youtube':
                $data['field_video_youtube'] = $this->faker->youTubeUrl;
                break;
        }

        return $data;
    }
}
```

#### Pick a random element from an array using weights
To get a random element from an array with some elements having a bigger chance to be returned than others, 
use the following method in your generators:
```php
$this->faker->randomElementWithWeight()
```
passing an array with the values as keys and weights as values, in the form of integers.

##### Example
```php
<?php

namespace Drupal\my_module\Entity\ModelFactory\Factory\Node;

use Drupal\node\NodeInterface;
use Drupal\wmdummy_data\EntityFactoryBase;

/**
 * @EntityFactory(
 *     entity_type = "node",
 *     bundle = "page",
 * )
 */
class PageFactory extends EntityFactoryBase
{
    public function make(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'menu_link' => null,
            'status' => $this->faker->randomElementWithWeight([
                NodeInterface::NOT_PUBLISHED => 70,
                NodeInterface::PUBLISHED => 30,
            ]),
        ];
    }
}
```

#### Generate strings of HTML
To generate random strings of HTML, use one of the following methods in your generators:
- `$this->faker->htmlBlock` (generates a block of HTML with headings, paragraphs, lists and a table)
- `$this->faker->htmlHeading` (generates a random heading between levels H1 and H6, a number can also be passed to 
  choose the level yourself)
- `$this->faker->htmlParagraph` (a `p` tag with random text, also containing `strong` and `a` tags)
- `$this->faker->htmlOrdenedList` (a `ol` tag with random list items)
- `$this->faker->htmlUnordenedList` (a `ul` tag with random list items)
- `$this->faker->htmlEmbed` (an `iframe` tag with a random url, not necessarily a real one)
- `$this->faker->htmlAnchor` (an `a` tag with a random url, not necessarily a real one)
- `$this->faker->htmlTable` (a `table` tag with a heading and a couple of rows/columns)

### Drush commands
This package provides a couple of Drush commands for managing dummy data:
- `wmdummy-data:generate`: Generate entities
- `wmdummy-data:delete`: Delete generated entities

For more information about command aliases, arguments, options & usage
examples, call the command with the `-h` / `--help` argument

### User interface
If you prefer working with the Drupal administration interface over using the CLI, you can use the form at 
`/admin/config/development/wmdummy-data`. This page can also be found through the administration menu (_Configuration_ >
_Development_ > _Dummy data_).

Only users with the `generate dummy data` and/or `delete dummy data` permissions can use these features through the 
administration interface.

### Events
You can subscribe to the following events to attach custom logic to the dummy data generation process:

#### `Drupal\wmdummy_data\DummyDataEvents::MAKE`
Will be triggered after an entity is generated.

#### `Drupal\wmdummy_data\DummyDataEvents::CREATE`
Will be triggered after an entity is generated and persisted to the database.

## Changelog
All notable changes to this project will be documented in the
[CHANGELOG](CHANGELOG.md) file.

## Security
If you discover any security-related issues, please email
[security@wieni.be](mailto:security@wieni.be) instead of using the issue
tracker.

## License
Distributed under the MIT License. See the [LICENSE](LICENSE.md) file
for more information.
