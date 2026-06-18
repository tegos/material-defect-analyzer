<?php

namespace Tests\Unit;

use App\Image\Matrix;
use Tests\TestCase;

class MatrixTest extends TestCase
{
    public function test_find_distance_between_identical_vectors_is_zero(): void
    {
        $identification = [[0, 0], [0, 1]];
        $data = [[0 => [100, 100, 100], 1 => [100, 100, 100]]];

        $result = Matrix::findDistance(0, 1, $identification, $data);

        $this->assertEquals(0.0, $result);
    }

    public function test_find_distance_same_element_against_itself_is_zero(): void
    {
        $identification = [[0, 0]];
        $data = [[0 => [50, 75, 100]]];

        $result = Matrix::findDistance(0, 0, $identification, $data);

        $this->assertEquals(0.0, $result);
    }

    public function test_find_distance_between_different_vectors(): void
    {
        $identification = [[0, 0], [0, 1]];
        $data = [[0 => [100, 100], 1 => [0, 0]]];

        // (100^2 + 100^2) / 2 = 10000
        $result = Matrix::findDistance(0, 1, $identification, $data);

        $this->assertEquals(10000.0, $result);
    }

    public function test_find_distance_is_symmetric(): void
    {
        $identification = [[0, 0], [0, 1]];
        $data = [[0 => [30, 60, 90], 1 => [10, 20, 30]]];

        $d01 = Matrix::findDistance(0, 1, $identification, $data);
        $d10 = Matrix::findDistance(1, 0, $identification, $data);

        $this->assertEquals($d01, $d10);
    }

    public function test_transpose_swaps_rows_and_columns(): void
    {
        $input = [[1, 2, 3], [4, 5, 6]];

        $result = Matrix::transpose($input);

        $this->assertEquals([[1, 4], [2, 5], [3, 6]], $result);
    }

    public function test_get_max_element_in_group(): void
    {
        $groups = [[0, 1, 2], [3, 4], [5]];

        $result = Matrix::getMaxElementInGroup($groups);

        $this->assertEquals(3, $result);
    }

    public function test_get_groups_returns_correct_count(): void
    {
        $matrix = [
            [0.0, 1.0, 5.0, 6.0],
            [1.0, 0.0, 4.0, 7.0],
            [5.0, 4.0, 0.0, 2.0],
            [6.0, 7.0, 2.0, 0.0],
        ];

        $groups = Matrix::getGroups($matrix, 2);

        $this->assertCount(2, $groups);
    }

    public function test_get_groups_covers_all_elements(): void
    {
        $matrix = [
            [0.0, 1.0, 5.0, 6.0],
            [1.0, 0.0, 4.0, 7.0],
            [5.0, 4.0, 0.0, 2.0],
            [6.0, 7.0, 2.0, 0.0],
        ];

        $groups = Matrix::getGroups($matrix, 2);

        $all = array_values(array_unique(array_merge(...$groups)));
        sort($all);

        $this->assertEquals([0, 1, 2, 3], $all);
    }
}
