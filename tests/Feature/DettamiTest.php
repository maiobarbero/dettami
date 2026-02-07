<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Transcription;

uses(RefreshDatabase::class);

it('generates a transcription from an uploaded audio file', function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);

    Storage::fake('local');
    Transcription::fake(['This is a test transcription.']);

    $file = UploadedFile::fake()->create('audio.webm', 100, 'audio/webm');

    $this->postJson('/recorder/upload', [
        'audio' => $file,
    ])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'transcription' => 'This is a test transcription.',
        ]);

    Transcription::assertGenerated(fn () => true);

    $files = Storage::disk('local')->allFiles('dettami');
    expect($files)->toBeEmpty();
});
