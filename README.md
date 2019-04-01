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
To create a specified entity generator, make a generator file in the map "Generator" and extend DummyDataBase.
```
use Drupal\wmdummy_data\DummyDataBase;

/**
 * @DummyData(
 *   id = "entityType.bundle",
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
