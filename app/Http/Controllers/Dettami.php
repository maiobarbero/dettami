<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Transcription;

class Dettami extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        if ($request->hasFile('audio')) {
            $file = $request->file('audio');

            $directory = 'dettami';
            $extension = $file->getClientOriginalExtension() ?: 'webm';
            $filename = 'web_rec_'.now()->format('Ymd_His').'.'.$extension;

            $path = $file->storeAs($directory, $filename);
            $fullPath = Storage::path($path);

            try {
                $mime = $file->getMimeType();

                if ($mime === 'video/webm' || $mime === 'video/x-matroska') {
                    $mime = 'audio/webm';
                }

                $transcription = Transcription::fromPath($fullPath, $mime)->generate();

                Storage::delete($path);

                return response()->json([
                    'success' => true,
                    'transcription' => (string) $transcription,
                ]);
            } catch (\Throwable $e) {
                Storage::delete($path);

                return response()->json([
                    'success' => false,
                    'message' => 'Errore durante la trascrizione: '.$e->getMessage(),
                ], 500);
            }
        }

        return response()->json(['success' => false, 'message' => 'Nessun file audio ricevuto'], 400);
    }
}
