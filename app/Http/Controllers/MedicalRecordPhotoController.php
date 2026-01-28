<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecordPhoto;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MedicalRecordPhotoController extends Controller
{
    public function show(MedicalRecordPhoto $photo): StreamedResponse
    {
        Gate::authorize('view', $photo->medicalRecord);

        return Storage::disk('local')->response($photo->path);
    }
}
