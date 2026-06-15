<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class QuestionImageController extends Controller
{
    public function __invoke(string $filename): Response
    {
        $path = 'question-images/'.basename($filename);

        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path));
    }
}
