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

```
composer require wieni\wmdummy_data
```

## Example 
To create a specified entity generator, make a generator file in the map **Generator** and extend **DummyDataBase**.
Use the generate function to return an array of fields you want to specify.
```
use Drupal\wmdummy_data\DummyDataBase;

/**
 * @DummyData(
 *   id = "entityType.bundle.preset_name",
 *   langcode = "nl",
 * )
 */
class SomeGenerator extends DummyDataBase
{
    public function generate(): array
    {
        return [
            'title' => $this->faker->sentence,
        ];
    }
}
```

## Annotations 
### ID 
To make sure the generator file can be used for the entity, make sure the generator ID is named after the **entity type** and **bundle** of the entity. (see example below)
The last section of the id, is the name of the preset. This allows multiple generators for the same entity.
```
/**
 * @DummyData(
 *   id = "node.page.default",
 * )
 */
```
### Langcode 
To make sure the generator file can be used for the language, make sure the langcode given is correct. For generators used for all languages, leave out the langcode.

```
/**
 * @DummyData(
 *   id = "node.page.default",
 * )
 */
```
```
/**
 * @DummyData(
 *   id = "node.page.french_version",
 *   langcode = "fr",
 * )
 */
```

## Use with wmContent 
To also specify the content children, implement the **ContentGenerateInterface**. There you can choose to specify the fields.
```
use Drupal\wmdummy_data\DummyDataBase;

/**
 * @DummyData(
 *   id = "entityType.bundle.preset_name",
 *   langcode = "nl",
 * )
 */
class SomeGenerator extends DummyDataBase implements ContentGenerateInterface
{
    public function generate(): array
    {
        return [
            'title' => $this->faker->sentence,
        ];
    }
    
    public function generateContents() : array
    {
        return [
            'content_block' => [
                'call_to_action' => [
                    'field_cta_description' => [
                        'value' => $this->faker->paragraph,
                        'format' => 'bullet',
                    ],
                ],
                'faq' => [],
            ],
        ];
    }
}
```

In case the field description is empty, like the entity *faq* in the example above, the module will look for a preset with the **same preset name as the parent**. If that does not exist, the module will look for the **default preset**. If that also does not exist, the module will use no presets and generate every field with the **standard** generator.
