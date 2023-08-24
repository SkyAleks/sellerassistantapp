<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        if ($request->hasFile('fileChunk')) {
            $fileChunk            = $request->file('fileChunk');
            $temporaryStoragePath = storage_path('temp_upload');
            $job                  = new ProcessData($fileChunk->getPathname());

            $this->dispatchSync($job);
            $fileChunk->move($temporaryStoragePath, $fileChunk->getClientOriginalName());

            return response()->json(Cache::get('process_result'));
        }

        return response()->json(['message' => 'Chunk upload failed'], 400);
    }

    public function assemble(Request $request)
    {
        $totalChunks          = $request->input('totalChunks');
        $filename             = $request->input('filename');
        $temporaryStoragePath = storage_path('temp_upload');
        $fullFilePath         = $temporaryStoragePath . '/' . uniqid() . '_' . $filename;

        for ($chunkIndex = 0; $chunkIndex < $totalChunks; $chunkIndex++) {
            $chunkPath     = $temporaryStoragePath . '/' . $filename . '_' . $chunkIndex;
            $chunkContents = file_get_contents($chunkPath);
            @file_put_contents($fullFilePath, $chunkContents, FILE_APPEND);
        }

        for ($chunkIndex = 0; $chunkIndex < $totalChunks; $chunkIndex++) {
            $chunkPath = $temporaryStoragePath . '/' . $filename . '_' . $chunkIndex;
            unlink($chunkPath);
        }

        return response()->json(['message' => 'File uploaded successfully']);
    }
}
