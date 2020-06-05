<?php

namespace Drupal\wmdummy_data\Faker\Provider;

use Faker\Provider\Base;

/**
 * @property string $htmlBlock
 * @property string $htmlHeading
 * @property string $htmlParagraph
 * @property string $htmlOrdenedList
 * @property string $htmlUnordenedList
 * @property string $htmlEmbed
 * @property string $htmlAnchor
 * @property string $htmlTable
 */
class Html extends Base
{
    public function htmlBlock(): string
    {
        return implode(PHP_EOL, [
            $this->htmlHeading($this->generator->randomDigitNotNull),
            $this->htmlParagraph(),
            $this->htmlHeading(),
            sprintf(
                '<p>%s %s %s</p>',
                $this->generator->sentence($this->generator->randomDigitNotNull),
                $this->htmlAnchor(),
                $this->generator->sentence($this->generator->randomDigitNotNull)
            ),
            $this->htmlParagraph(),
            $this->htmlOrdenedList(),
            $this->htmlUnordenedList(),
            $this->htmlTable(),
        ]);
    }

    public function htmlHeading(?int $size = null): string
    {
        $size = $size ?? $this->generator->numberBetween(1, 6);

        return sprintf(
            '<h%d>%s <strong>%s </strong>%s <em>%s </em></h%d>',
            $size,
            $this->generator->sentence($this->generator->randomDigitNotNull),
            $this->generator->word,
            $this->generator->sentence($this->generator->randomDigitNotNull),
            $this->generator->word,
            $size
        );
    }

    public function htmlParagraph(): string
    {
        return sprintf(
            '<p> %s <strong> %s </strong> %s %s</p>',
            $this->generator->paragraph($this->generator->randomDigitNotNull),
            $this->generator->sentence($this->generator->randomDigitNotNull),
            $this->generator->sentence($this->generator->randomDigitNotNull),
            $this->htmlAnchor()
        );
    }

    public function htmlOrdenedList(): string
    {
        return sprintf(
            '<ol>
                <li> %s </li>
                <li> %s </li>
                <li> %s <em> %s </em></li>
            </ol>',
            $this->generator->sentence($this->generator->randomDigitNotNull),
            $this->generator->sentence($this->generator->randomDigitNotNull),
            $this->generator->sentence($this->generator->randomDigitNotNull),
            $this->generator->word
        );
    }

    public function htmlUnordenedList(): string
    {
        return sprintf(
            '<ul>
                <li> %s </li>
                <li> %s </li>
                <li> %s <em> %s </em></li>
            </ul>',
            $this->generator->sentence($this->generator->randomDigitNotNull),
            $this->generator->sentence($this->generator->randomDigitNotNull),
            $this->generator->sentence($this->generator->randomDigitNotNull),
            $this->generator->word
        );
    }

    public function htmlEmbed(int $width = 600, int $height = 450): string
    {
        return sprintf(
            '<iframe src="%s" width="%d" height="%d" frameborder="0" style="border:0" allowfullscreen></iframe>',
            $this->generator->url,
            $width,
            $height
        );
    }

    public function htmlAnchor(): string
    {
        return sprintf(
            '<a href="%s"> %s </a>',
            $this->generator->url,
            $this->generator->sentence($this->generator->randomDigitNotNull)
        );
    }

    public function htmlTable(): string
    {
        return implode(PHP_EOL, [
            '<table><tbody>',
            '<tr>',
            sprintf('<th>%s</th>', $this->generator->word),
            sprintf('<th>%s</th>', $this->generator->sentence($this->generator->randomDigitNotNull)),
            sprintf('<th>%s</th>', $this->generator->sentence($this->generator->randomDigitNotNull)),
            '</tr>',
            '<tr>',
            sprintf(
                '<td><strong>%s</strong> %s</td>',
                $this->generator->sentence($this->generator->randomDigitNotNull),
                $this->generator->sentence($this->generator->randomDigitNotNull)
            ),
            sprintf(
                '<td>%s <em>%s</em> %s</td>',
                $this->generator->sentence($this->generator->randomDigitNotNull),
                $this->generator->word,
                $this->generator->sentence($this->generator->randomDigitNotNull)
            ),
            sprintf('<td>%s</td>', $this->generator->sentence($this->generator->randomDigitNotNull)),
            '</tr>',
            '<tr>',
            sprintf('<td>%s</td>', $this->generator->sentence($this->generator->randomDigitNotNull)),
            sprintf('<td>%s</td>', $this->generator->sentence($this->generator->randomDigitNotNull)),
            sprintf('<td>%s</td>', $this->generator->sentence($this->generator->randomDigitNotNull)),
            '</tr>',
            '</tbody></table>',
        ]);
    }
}
