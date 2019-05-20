<?php

namespace Drupal\wmdummy_data\Faker\Provider;

use Faker\Provider\Base;

class RandomElementWeight extends Base
{
    /**
     * Picks a random element by weight
     * @see https://stackoverflow.com/a/4726300/2637528
     *
     * @param array $values
     * @return mixed
     * @throws \Exception
     */
    public function randomElementWithWeight(array $values)
    {
        $total = $currentTotal = $value = 0;
        $firstRand = random_int(1, 100);

        foreach ($values as $amount) {
            $total += $amount;
        }

        $rand = ($firstRand / 100) * $total;

        foreach ($values as $amount) {
            $currentTotal += $amount;

            if ($rand > $currentTotal) {
                $value++;
            } else {
                break;
            }
        }

        return array_keys($values)[$value];
    }
}
