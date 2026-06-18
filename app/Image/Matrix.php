<?php
/**
 * User: Ivan
 * Date: 30.09.2017
 * Time: 14:35
 */

namespace App\Image;

class Matrix
{
    public static function findDistance($i, $j, array $dataGraphIdentification, array $data)
    {
        $sum = 0;
        $row1 = $data[$dataGraphIdentification[$i][0]][$dataGraphIdentification[$i][1]];
        $row2 = $data[$dataGraphIdentification[$j][0]][$dataGraphIdentification[$j][1]];
        $num = count($row1);

        for ($k = 0; $k < $num; $k++) {
            $diff = $row1[$k] - $row2[$k];

            $sqr = pow($diff, 2);
            $sum += $sqr;
        }

        return round($sum / $num, 2);
    }

    /**
     * @param $distanceMatrix
     * @param int $numOfGroup кількість груп
     * @return array
     */
    public static function getGroups($distanceMatrix, $numOfGroup = 3)
    {
        $group = [];

        $num = count($distanceMatrix);
        $elementInGroup = (int)($num / $numOfGroup);

        $countIteration = 1;
        while (count($group) < (($numOfGroup - 1) * $elementInGroup)) {
            $localMins = [];
            for ($i = 0; $i < $num; $i++) {
                if (!in_array($i, $group)) {
                    $array = array_filter($distanceMatrix[$i]);
                    if (count($array) < 2) {
                        $array[] = 1;
                    }
                    $localMins[$i] = min($array);
                }
            }

            $minimum = min($localMins);
            $graphCounter = $num;

            for ($graphI = 0; $graphI < $graphCounter; $graphI++) {
                for ($graphJ = 0; $graphJ < $graphCounter; $graphJ++) {
                    if ($distanceMatrix[$graphI][$graphJ] === $minimum) {
                        $group[] = $graphI;
                        $group[] = $graphJ;
                        break;
                    }
                }
            }

            $countGroup = count($group);

            if (($elementInGroup % 2)) {
                if ($countGroup % 2) {
                    array_pop($group);
                }
            }

            $group = array_unique($group);

            $group = array_values($group);

            $countIteration++;
            if ($countIteration > 100) {
                break;
            }
        }

        $lastGroup = [];
        for ($i = 0; $i < $num; $i++) {
            if (!in_array($i, $group)) {
                $lastGroup[] = $i;
            }
        }

        $group = array_merge($group, $lastGroup);
        $chunk = [];

        $usedElement = [];
        for ($i = 0; $i < $elementInGroup * ($numOfGroup - 1); $i += $elementInGroup) {
            $offset = $i;
            $length = $elementInGroup;

            $part = array_slice($group, $offset, $length);

            $chunk[] = $part;
            $usedElement = array_merge($usedElement, $part);
        }

        // різна кількість елементів у групах
        $diff = array_diff($group, $usedElement);
        if (count($diff)) {
            $chunk[] = $diff;
        }

        return $chunk;

    }

    public static function getTotalDistancesByGroups(array $dataGraphIdentification, array $distanceMatrix, array $groups)
    {
        $distances = [];
        foreach ($groups as $indexKey => $groupItem) {
            $distances[$indexKey] = 0;
            foreach ($groupItem as $group) {
                $i = $dataGraphIdentification[$group][0];
                $j = $dataGraphIdentification[$group][1];
                $distances[$indexKey] += $distanceMatrix[$i][$j];
            }
        }
        return $distances;
    }

    public static function getMaxElementInGroup($groups)
    {
        $groupElements = [];
        foreach ($groups as $groupData) {
            $groupElements[] = count($groupData);
        }
        $max = max($groupElements);
        return $max;
    }

    public static function transpose($array)
    {
        return array_map(null, ...$array);
    }

}