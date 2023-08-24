<?php

namespace App\Jobs;

use App\Models\CorrectRecords;
use App\Models\IncorrectRecords;
use App\Models\UploadFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ProcessData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle()
    {
        $correctRecords   = 0;
        $incorrectRecords = 0;
        $f                = fopen($this->filePath, 'r');

        while (!feof($f)) {
            $row     = fgets($f);
            $columns = explode(";", trim($row));
            $isValid = true;

            foreach ($columns as $column) {
                if (!ctype_alpha($column)) {
                    $isValid = false;
                    break;
                }
            }

            if ($isValid) {
                $records = new CorrectRecords();
                $correctRecords++;
            } else {
                $records = new IncorrectRecords();
                $incorrectRecords++;
            }

            $records->row_data       = $row;
            $records->save();
        }

        Cache::put('process_result', [
            'correct'   => $correctRecords,
            'incorrect' => $incorrectRecords
        ], now()->addSecond(1));

    }
}
