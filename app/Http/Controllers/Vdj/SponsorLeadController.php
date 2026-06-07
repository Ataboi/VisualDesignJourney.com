<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use App\Models\SponsorLead;
use Illuminate\Http\Request;
use Throwable;

class SponsorLeadController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'company' => ['nullable', 'string', 'max:160'],
            'budget' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        try {
            SponsorLead::query()->create($data + ['status' => 'new']);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'Sponsor lead storage is not ready yet.'], 503);
        }

        return response()->json(['ok' => true]);
    }
}
