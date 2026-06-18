<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_upload_without_file_redirects_to_home(): void
    {
        $response = $this->post('/upload');

        $response->assertRedirect('/');
    }

    public function test_upload_with_valid_image_redirects_to_image_page(): void
    {
        $file = UploadedFile::fake()->image('crazing.jpg');

        $response = $this->post('/upload', [
            'image'     => $file,
            'divide_n'  => 3,
            'divide_m'  => 3,
            'threshold' => 200,
            'algorithm' => 1,
            'groups'    => 3,
        ]);

        $response->assertRedirectContains('/image/');
    }

    public function test_upload_stores_record_in_database(): void
    {
        $file = UploadedFile::fake()->image('scratches.jpg');

        $this->post('/upload', [
            'image'     => $file,
            'divide_n'  => 4,
            'divide_m'  => 4,
            'threshold' => 128,
            'algorithm' => 2,
            'groups'    => 2,
        ]);

        $this->assertDatabaseHas('images', [
            'divide_n'  => 4,
            'divide_m'  => 4,
            'threshold' => 128,
            'algorithm' => 2,
            'groups'    => 2,
        ]);
    }
}
