<?php

namespace App\Http\Controllers;

use App\Models\ExamSessionCategory;
use App\Models\ExamSession;
use App\Models\QuestionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamSessionCategoryController extends Controller
{
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
    public function create(ExamSession $examSession)
    {
        $categories = QuestionCategory::orderBy('name')->get();

        $selectedCategories = $examSession->categories
            ->pluck('pivot.question_count', 'id')
            ->toArray();

        return view(
            'dashboard.exam-sessions.categories.create',
            compact(
                'examSession',
                'categories',
                'selectedCategories'
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, ExamSession $examSession)
    {

        $syncData = [];

        foreach ($request->categories ?? [] as $categoryId => $data) {

            if (!empty($data['selected'])) {

                $syncData[$categoryId] = [
                    'question_count' => $data['question_count'] ?? 0,
                ];
            }
        }

        $examSession->categories()->sync($syncData);

        return redirect()
            ->route('dashboard.exam-sessions.index')
            ->with(
                'success',
                'Kategori sesi ujian berhasil diperbarui.'
            );
    }

    /**
     * Display the specified resource.
     */
    public function show(ExamSessionCategory $examSessionCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExamSessionCategory $examSessionCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExamSessionCategory $examSessionCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExamSessionCategory $examSessionCategory)
    {
        //
    }
}
