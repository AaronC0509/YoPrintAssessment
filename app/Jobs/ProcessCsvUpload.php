<?php

namespace App\Jobs;

use App\Models\FileUpload;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessCsvUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(protected FileUpload $fileUpload)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->fileUpload->update(['status' => 'processing']);

        try {
            $filePath = Storage::path($this->fileUpload->path);
            $fileContents = file_get_contents($filePath);
            $fileContents = mb_convert_encoding($fileContents, 'UTF-8', 'UTF-8');
            file_put_contents($filePath, $fileContents);

            $file = fopen($filePath, 'r');

            // Skip header row
            fgetcsv($file);

            $processedCount = 0;

            while (($row = fgetcsv($file)) !== false) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Map CSV columns to correct indexes based on header
                $data = [
                    'UNIQUE_KEY' => $row[0] ?? '',
                    'PRODUCT_TITLE' => $row[1] ?? '',
                    'PRODUCT_DESCRIPTION' => $row[2] ?? '',
                    'STYLE#' => $row[3] ?? '',
                    'SANMAR_MAINFRAME_COLOR' => $row[28] ?? '', // Correct index
                    'SIZE' => $row[18] ?? '',                    // Correct index
                    'COLOR_NAME' => $row[14] ?? '',              // Correct index
                    'PIECE_PRICE' => $row[21] ?? '0.00',         // Correct index
                ];

                Product::updateOrCreate(
                    ['unique_key' => $data['UNIQUE_KEY']],
                    [
                        'product_title' => $data['PRODUCT_TITLE'],
                        'product_description' => $data['PRODUCT_DESCRIPTION'],
                        'style' => $data['STYLE#'],
                        'sanmar_mainframe_color' => $data['SANMAR_MAINFRAME_COLOR'],
                        'size' => $data['SIZE'],
                        'color_name' => $data['COLOR_NAME'],
                        'piece_price' => floatval($data['PIECE_PRICE']),
                    ]
                );
                
                $processedCount++;
            }

            fclose($file);

            $this->fileUpload->update([
                'status' => 'completed',
                'processed_count' => $processedCount
            ]);
        } catch (Throwable $e) {
            $this->fileUpload->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
