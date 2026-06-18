<?php

namespace Tests\Feature;

use Tests\TestCase;

class GroupChartTest extends TestCase
{
    private array $featureData = [
        '0x0' => [100, 120, 110, 130],
        '1x0' => [50, 60, 55, 65],
        '2x0' => [20, 25, 22, 18],
    ];

    public function test_group_chart_returns_200(): void
    {
        $response = $this->post('/ajax/chart', [
            'imageKeys'          => ['0x0', '1x0'],
            'featureDataOfImages' => json_encode($this->featureData),
        ]);

        $response->assertStatus(200);
    }

    public function test_group_chart_response_has_expected_structure(): void
    {
        $response = $this->post('/ajax/chart', [
            'imageKeys'          => ['0x0', '1x0'],
            'featureDataOfImages' => json_encode($this->featureData),
        ]);

        $response->assertJsonStructure([
            '*' => ['data', 'name', 'min', 'max'],
        ]);
    }

    public function test_group_chart_series_count_matches_requested_keys(): void
    {
        $response = $this->post('/ajax/chart', [
            'imageKeys'          => ['0x0', '1x0'],
            'featureDataOfImages' => json_encode($this->featureData),
        ]);

        $this->assertCount(2, $response->json());
    }

    public function test_group_chart_series_contains_correct_data(): void
    {
        $response = $this->post('/ajax/chart', [
            'imageKeys'          => ['0x0'],
            'featureDataOfImages' => json_encode($this->featureData),
        ]);

        $series = $response->json();

        $this->assertEquals([100, 120, 110, 130], $series[0]['data']);
        $this->assertEquals('Частина 0x0', $series[0]['name']);
    }
}
