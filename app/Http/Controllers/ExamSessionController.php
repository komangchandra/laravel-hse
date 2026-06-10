<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use Illuminate\Http\Request;

class ExamSessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $examSessions = ExamSession::latest()->paginate(10);

        return view(
            'dashboard.exam-sessions.index',
            compact('examSessions')
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.exam-sessions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255'],
            'description' => ['nullable'],
            'duration' => ['required', 'integer', 'min:1'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        ExamSession::create($validated);

        return redirect()
            ->route('dashboard.exam-sessions.index')
            ->with('success', 'Ujian berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExamSession $examSession)
    {
        return view(
            'dashboard.exam-sessions.edit',
            compact('examSession')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExamSession $examSession)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration' => ['required', 'integer', 'min:1'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $examSession->update([
            'name' => $request->name,
            'description' => $request->description,
            'duration' => $request->duration,
            'passing_score' => $request->passing_score,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('dashboard.exam-sessions.index')
            ->with('success', 'Sesi ujian berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
