<?php

namespace Drupal\wmdummy_data\Traits;

use Faker\Generator;

/**
 * @property Generator $faker
 */
trait HtmlTextCreateTrait
{
    protected function createTaggedTextBlock(): string
    {
        $textPieces = [
            $this->createHeader($this->faker->randomDigitNotNull),
            $this->createTaggedParagraph(),
            $this->createHeader($this->faker->randomDigitNotNull),
            sprintf(
                '<p>%s %s %s</p>',
                $this->faker->sentence($this->faker->randomDigitNotNull),
                $this->createHref(),
                $this->faker->sentence($this->faker->randomDigitNotNull)
            ),
            $this->createTaggedString(),
            $this->createOrdenedList(),
            $this->createUnordenedList(),
            $this->createTable()
        ];
        return implode(PHP_EOL, $textPieces);
    }

    protected function createHeader(int $size): string
    {
        $header = sprintf(
            '<h%d>%s <strong>%s </strong>%s <em>%s </em></h%d>',
            $size,
            $this->faker->sentence($this->faker->randomDigitNotNull),
            $this->faker->word,
            $this->faker->sentence($this->faker->randomDigitNotNull),
            $this->faker->word,
            $size
        );
        return $header;
    }

    protected function createTaggedParagraph(): string
    {
        $text = sprintf(
            '<p> %s <strong> %s </strong> %s %s</p>',
            $this->faker->paragraph($this->faker->randomDigitNotNull),
            $this->faker->sentence($this->faker->randomDigitNotNull),
            $this->faker->sentence($this->faker->randomDigitNotNull),
            $this->createHref()
        );
        return $text;
    }

    protected function createTaggedString(): string
    {
        $string = sprintf(
            '<p>%s <em>%s</em> <strong>%s</strong> %s</p>',
            $this->faker->sentence($this->faker->randomDigitNotNull),
            $this->faker->word,
            $this->faker->word,
            $this->faker->sentence($this->faker->randomDigitNotNull)
        );
        return $string;
    }

    protected function createOrdenedList(): string
    {
        $ol = sprintf(
            '<ol>
                <li> %s </li>
                <li> %s </li>
                <li> %s <em> %s </em></li>
            </ol>',
            $this->faker->sentence($this->faker->randomDigitNotNull),
            $this->faker->sentence($this->faker->randomDigitNotNull),
            $this->faker->sentence($this->faker->randomDigitNotNull),
            $this->faker->word
        );

        return $ol;
    }

    protected function createUnordenedList(): string
    {
        $ul = sprintf(
            '<ul>
                <li> %s </li>
                <li> %s </li>
                <li> %s <em> %s </em></li>
            </ul>',
            $this->faker->sentence($this->faker->randomDigitNotNull),
            $this->faker->sentence($this->faker->randomDigitNotNull),
            $this->faker->sentence($this->faker->randomDigitNotNull),
            $this->faker->word
        );

        return $ul;
    }

    protected function createDescription(): string
    {
        return $this->createTaggedString() . ' ' . $this->createUnordenedList();
    }

    protected function createHtmlEmbed(int $width = 600, int $height = 450): string
    {
        $iframe = sprintf(
            '<iframe src="%s" width="%d" height="%d" frameborder="0" style="border:0" allowfullscreen></iframe>',
            $this->faker->url,
            $width,
            $height
        );

        return $iframe;
    }

    protected function createHref(): string
    {
        $link = sprintf(
            '<a href="%s"> %s </a>',
            $this->faker->url,
            $this->faker->sentence($this->faker->randomDigitNotNull)
        );

        return $link;
    }

    protected function createTable(): string
    {
        $tablePieces = [
            '<table><tbody>',
            '<tr>',
            sprintf('<th>%s</th>', $this->faker->word),
            sprintf('<th>%s</th>', $this->faker->sentence($this->faker->randomDigitNotNull)),
            sprintf('<th>%s</th>', $this->faker->sentence($this->faker->randomDigitNotNull)),
            '</tr>',
            '<tr>',
            sprintf(
                '<td><strong>%s</strong> %s</td>',
                $this->faker->sentence($this->faker->randomDigitNotNull),
                $this->faker->sentence($this->faker->randomDigitNotNull)
            ),
            sprintf(
                '<td>%s <em>%s</em> %s</td>',
                $this->faker->sentence($this->faker->randomDigitNotNull),
                $this->faker->word,
                $this->faker->sentence($this->faker->randomDigitNotNull)
            ),
            sprintf('<td>%s</td>', $this->faker->sentence($this->faker->randomDigitNotNull)),
            '</tr>',
            '<tr>',
            sprintf('<td>%s</td>', $this->faker->sentence($this->faker->randomDigitNotNull)),
            sprintf('<td>%s</td>', $this->faker->sentence($this->faker->randomDigitNotNull)),
            sprintf('<td>%s</td>', $this->faker->sentence($this->faker->randomDigitNotNull)),
            '</tr>',
            '</tbody></table>'
        ];

        return implode(PHP_EOL, $tablePieces);
    }
}
