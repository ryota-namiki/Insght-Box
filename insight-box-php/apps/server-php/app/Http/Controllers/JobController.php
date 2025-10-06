<?php

namespace App\Http\Controllers;

use App\Repositories\JobRepository;

class JobController extends Controller
{
    public function show(string $id, JobRepository $jobs)
    {
        $job = $jobs->find($id);
        if (!$job) {
            return response()->json(['error' => 'not found'], 404);
        }
        return response()->json([
            'status' => $job['status'],
            'progress' => $job['progress'],
            'document_id' => $job['document_id']
        ]);
    }
}
