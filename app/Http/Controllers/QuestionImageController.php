<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuestionImageController extends Controller
{
    public function __invoke(string $filename): BinaryFileResponse
    {
        $filename = basename($filename);
        $storagePath = 'question-images/'.$filename;

        if (Storage::disk('public')->exists($storagePath)) {
            return response()->file(Storage::disk('public')->path($storagePath));
        }

        $fallbackPaths = [
            public_path('storage/question-images/'.$filename),
            public_path('question-images/'.$filename),
        ];

        foreach ($fallbackPaths as $path) {
            if (is_file($path)) {
                return response()->file($path);
            }
        }

        abort(404);
    }
}
