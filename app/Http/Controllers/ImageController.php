<?php

namespace App\Http\Controllers;

use App\Image as ImageModel;
use App\Image\ImageCharacteristic;
use App\Image\ImageGrid;
use App\Image\Matrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageController extends Controller
{
	public function index($id)
	{
		$id = (int)$id;
		$image_data = ImageModel::find($id);

		$algorithms = ImageGrid::getAlgorithms();

		$file_path = Storage::disk('public')->path($image_data->image);

		$img = Image::read($file_path);
		$relative_path = 'uploads/' . $id . '.png';

		$img->greyscale();

		$new_file_path = Storage::disk('public')->path($relative_path);
		$img->save($new_file_path);

		$imageGrid = new ImageGrid (
			$new_file_path, $image_data->divide_n, $image_data->divide_m
		);

		$originalImage = new ImageGrid (
			$new_file_path, $image_data->divide_n, $image_data->divide_m
		);

		$imageCharacteristic = new ImageCharacteristic();
		$imageCharacteristic->setImage($originalImage->getImage());
		$featureOfAllImage = $imageCharacteristic->getIntensity();

		$imageGrid->addGridToImage();

		$file_path_with_grid = 'uploads/' . $id . '_grid.png';
		$imageGrid->saveImageToFile(Storage::disk('public')->path($file_path_with_grid));

		// divided images
		$n = $image_data->divide_n; // cols
		$m = $image_data->divide_m; // rows
		$threshold = (int)$image_data->threshold;
		$algorithm = (int)$image_data->algorithm;
		$numOfGroup = (int)$image_data->groups;

		$algorithmData = $algorithms[$algorithm];

		$cropped_images = [];

		for ($i = 0; $i < $n; $i++) {
			for ($j = 0; $j < $m; $j++) {
				$file_path_crop = "uploads/{$id}_{$j}_{$i}_grid_crop.png";
				$crop = $originalImage->getImageByPosition($j, $i);
				$originalImage->saveImageToFile(Storage::disk('public')->path($file_path_crop), $crop);

				$cropped_images["{$j}x{$i}"] = [
					'image' => '/storage/' . $file_path_crop,
					'm' => $j,
					'n' => $i,
					'position' => "{$j}_{$i}"
				];
			}
		}

		// distance matrix
		$n = $image_data->divide_n; // cols
		$m = $image_data->divide_m; // rows

		$dataForDistance = [[]];

		$featureDataOfImages = [];

		for ($i = 0; $i < $n; $i++) {
			for ($j = 0; $j < $m; $j++) {
				$imageCharacteristic = new ImageCharacteristic();
				$file_path_crop = "uploads/{$id}_{$j}_{$i}_grid_crop.png";

				$imageCharacteristic->setImageByPath(Storage::disk('public')->path($file_path_crop));

				// based data on selected feature
				if (is_callable([$imageCharacteristic, $algorithmData['feature_method']])) {
					$featureData = $imageCharacteristic->{$algorithmData['feature_method']}();
				} else {
					$featureData = $imageCharacteristic->getIntensityByRow();
				}

				$dataForDistance[$i][$j] = $featureData;

				$featureDataOfImages["{$j}x{$i}"] = $featureData;

			}
		}

		// row graph identification
		$dataGraphIdentification = [];
		$graphCounter = 0;
		for ($i = 0; $i < $n; $i++) {
			for ($j = 0; $j < $m; $j++) {
				$dataGraphIdentification[$graphCounter] = [$i, $j];
				$graphCounter++;
			}
		}

		$graphCounter = count($dataGraphIdentification);
		$dataForDistanceCount = [[]];

		for ($graphI = 0; $graphI < $graphCounter; $graphI++) {
			for ($graphJ = 0; $graphJ < $graphCounter; $graphJ++) {
				$dataForDistanceCount[$graphI][$graphJ] =
					Matrix::findDistance($graphI, $graphJ, $dataGraphIdentification, $dataForDistance);
			}
		}

		// groups
		$groups = Matrix::getGroups($dataForDistanceCount, $numOfGroup);
		$groupsOriginal = array_merge($groups);

		$transpose = Matrix::transpose($groups);

		$maxElementInGroup = Matrix::getMaxElementInGroup($groups);

		// total distances by groups
		$totalDistances = Matrix::getTotalDistancesByGroups($dataGraphIdentification, $dataForDistanceCount, $groups);

		// percentage
		$percentDefectData = [];

		$groups = $transpose;
		foreach ($groups as $groupData) {
			foreach ($groupData as $group) {
				if (isset($group)) {
					$image_key = $dataGraphIdentification[$group][1] . 'x' . $dataGraphIdentification[$group][0];
					$featureData = $featureDataOfImages[$image_key];

					$averageForGroup = array_sum($featureData) / count($featureData);

					$diffArray = [];
					foreach ($featureData as $feature) {
						$diffArray[] = pow($feature - $averageForGroup, 2);
					}

					$min = min($diffArray);
					$max = max($diffArray);

					$distanceSqrt = 0;
					$distanceSqrt += $min;
					$distanceSqrt += $max;

					$distanceSqrt = round(sqrt($distanceSqrt), 2);
					$percentDefectData[$image_key] = $distanceSqrt;
				}

			}
		}

		$sumDiffDistance = array_sum($percentDefectData);
		if ($sumDiffDistance == 0) {
			$sumDiffDistance = 1;
		}

		$percentData = array_map(function ($diff) use ($sumDiffDistance) {
			return round(($diff / $sumDiffDistance), 2);
		}, $percentDefectData);

		$percentDataGroups = [];
		foreach ($groupsOriginal as $k => $groupData) {
			$sumPercent = 0;
			foreach ($groupData as $group) {
				if (isset($group)) {
					$image_key = $dataGraphIdentification[$group][1] . 'x' . $dataGraphIdentification[$group][0];
					$sumPercent += $percentData[$image_key];
				}
			}
			$percentDataGroups[$k] = round($sumPercent * 100);
		}

		$progressBarClasses = [];

		for ($i = 0; $i < 99; $i++) {
			if ($i < 10) {
				$class = 'stat-3';
			} elseif ($i < 40) {
				$class = 'stat-2';
			} else {
				$class = 'stat-1';
			}
			$progressBarClasses[$i] = $class;
		}

		// for highlight red group
		$minPercent = 40;
		$needHighlight = false;
		foreach ($percentDataGroups as $percentDataGroup) {
			if ($percentDataGroup > $minPercent) {
				$needHighlight = true;
				break;
			}
		}
		$dangerGroups = [];
		$dangerSegment = [];
		if ($needHighlight) {
			foreach ($percentDataGroups as $number => $percentDataGroup) {
				if ($percentDataGroup > $minPercent) {
					$dangerGroups[] = $number;
				}
			}

			foreach ($groupsOriginal as $numberOfGroup => $groupData) {
				foreach ($groupData as $group) {
					if (in_array($numberOfGroup, $dangerGroups)) {
						if (isset($group)) {
							$image_key = $dataGraphIdentification[$group][1] . 'x' . $dataGraphIdentification[$group][0];

							$dangerSegment[] = $image_key;
						}
					}
				}
			}
		}

		$data = [];
		$data['image'] = $image_data;
		$data['image_url'] = '/storage/' . $image_data->image;
		$data['algorithmData'] = $algorithmData;
		$data['image_edit'] = '/storage/' . $relative_path;
		$data['image_grid'] = '/storage/' . $file_path_with_grid;
		$data['cropped_images'] = $cropped_images;

		$data['n'] = $image_data->divide_n; // cols
		$data['m'] = $image_data->divide_m; // rows
		$data['matrix_distance'] = $dataForDistanceCount;

		$data['dataGraphIdentification'] = $dataGraphIdentification;

		$data['groups'] = $groups;

		$data['percentDataGroups'] = $percentDataGroups;

		$data['progressBarClasses'] = $progressBarClasses;

		$data['groups'] = $transpose;

		$data['numOfGroup'] = $numOfGroup;
		$data['maxElementInGroup'] = $maxElementInGroup;

		$data['totalDistances'] = $totalDistances;

		$data['dangerSegment'] = $dangerSegment;
		$data['needHighlight'] = $needHighlight;

		$data['featureDataOfImages'] = json_encode($featureDataOfImages);

		return view('image', $data);

	}

	public function upload(Request $request)
	{
		if ($request->hasFile('image')) {
			$file = $request->file('image');
			Storage::disk('public')->putFileAs('uploads', $file, $file->getClientOriginalName());
			$divide_n = (int)$request->input('divide_n', 3);
			$divide_m = (int)$request->input('divide_m', 3);
			$threshold = (int)$request->input('threshold', 255);
			$algorithm = (int)$request->input('algorithm', 1);
			$groups = (int)$request->input('groups', 3);

			$image_model = new ImageModel;

			$image_model->image = 'uploads/' . $file->getClientOriginalName();
			$image_model->divide_n = $divide_n;
			$image_model->divide_m = $divide_m;
			$image_model->threshold = $threshold;
			$image_model->algorithm = $algorithm;
			$image_model->groups = $groups;

			$image_model->save();
			return Redirect::to('image/' . $image_model->id);
		} else {
			return Redirect::to('/');
		}

	}
}

