<?php

namespace App\Http\Controllers;

use App\Http\Resources\FileUploadResource;
use App\Jobs\ProcessCsvUpload;
use App\Models\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FileUploadController extends Controller
{
    public function index()
    {
        return view('uploads');
    }

    /**
     * Store a newly uploaded file and dispatch the processing job.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $file = $request->file('csv_file');
        $originalName = $file->getClientOriginalName();
        $path = $file->store('csv_uploads');

        $fileUpload = FileUpload::create([
            'original_name' => $originalName,
            'path' => $path,
            'status' => 'pending',
        ]);

        ProcessCsvUpload::dispatch($fileUpload);

        return back()->with('success', 'File uploaded successfully and is now processing.');
    }

    public function status()
    {
        $uploads = FileUpload::latest()->get();
        return FileUploadResource::collection($uploads);
    }
}
