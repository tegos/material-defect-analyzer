<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GroupChart extends Controller
{
    public function get(Request $request)
    {
        $imageKeys = $request->input('imageKeys');
        $featureDataOfImages = json_decode($request->input('featureDataOfImages'), true);

        $imageKeysData = [];
        foreach ($imageKeys as $key => $imageKey) {
            $imageKeysData[] = $imageKey;
        }

        $resultFeatures = [];
        $imageKeysDataIndex = [];
        $min = [];
        $max = [];

        foreach ($imageKeysData as $imageKey) {
            $resultFeatures[] = $featureDataOfImages[$imageKey];
            $imageKeysDataIndex[] = $imageKey;
            $min[] = min($featureDataOfImages[$imageKey]);
            $max[] = max($featureDataOfImages[$imageKey]);
        }

        $seriesData = [];

        for ($i = 0; $i < count($resultFeatures); $i++) {
            $seriesData[] = [
                'data' => $resultFeatures[$i],
                'name' => "Частина {$imageKeysDataIndex[$i]}",
                'min' => min($min) - 15,
                'max' => max($max) + 15
            ];
        }

        return response()->json($seriesData);
    }
}
