<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

            $filename = 'web_rec_'.now()->format('Ymd_His').'.webm';

            $path = $file->storeAs($directory, $filename);

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => Storage::url($path),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Nessun file audio ricevuto'], 400);
    }
}
