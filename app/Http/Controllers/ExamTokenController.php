<?php

namespace App\Http\Controllers;

use App\Models\ExamToken;
use App\Models\Simper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExamTokenController extends Controller
{
    public function generate(Simper $simper)
    {
        ExamToken::where('simper_id', $simper->id)
            ->whereNull('used_at')
            ->delete();

        do {

            $token = strtoupper(Str::random(6));

        } while (
            ExamToken::where('token', $token)->exists()
        );

        ExamToken::create([
            'simper_id' => $simper->id,
            'token' => $token,
            'expired_at' => now()->addHour(),
        ]);

        return back()->with(
            'success',
            'Token ujian berhasil dibuat.'
        );
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ExamToken $examToken)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExamToken $examToken)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExamToken $examToken)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExamToken $examToken)
    {
        //
    }
}
