<?php

namespace App\Http\Controllers;

use App\Models\QuestionCategory;
use Illuminate\Http\Request;

class QuestionCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $questionCategories = QuestionCategory::when($search, function ($query, $search){
            return $query->where('name', 'like', "%{$search}%");
        })
        ->latest()
        ->paginate(10)
        ->withQueryString();

        return view('dashboard.question-categories.index', compact('questionCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.question-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:question_categories,name',
            'description' => 'required|string',
            'measured'    => 'required|string',
            'measurable'  => 'required|string',
        ], [
            'name.required'        => 'Nama kategori wajib diisi.',
            'name.unique'          => 'Nama kategori sudah digunakan.',
            'description.required' => 'Deskripsi wajib diisi.',
            'measured.required'    => 'Aspek yang diukur wajib diisi.',
            'measurable.required'  => 'Aspek terukur wajib diisi.',
        ]);

        QuestionCategory::create($validated);

        return redirect()
            ->route('dashboard.question-categories.index')
            ->with('success', 'Kategori soal berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(QuestionCategory $questionCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(QuestionCategory $questionCategory)
    {
        return view('dashboard.question-categories.edit', compact('questionCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, QuestionCategory $questionCategory)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:question_categories,name,' . $questionCategory->id,
            'description' => 'required|string',
            'measured'    => 'required|string',
            'measurable'  => 'required|string',
        ], [
            'name.required'        => 'Nama kategori wajib diisi.',
            'name.unique'          => 'Nama kategori sudah digunakan.',
            'description.required' => 'Deskripsi wajib diisi.',
            'measured.required'    => 'Aspek yang diukur wajib diisi.',
            'measurable.required'  => 'Aspek terukur wajib diisi.',
        ]);

        $questionCategory->update($validated);

        return redirect()
            ->route('dashboard.question-categories.index')
            ->with('success', 'Kategori soal berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QuestionCategory $questionCategory)
    {
        $questionCategory->delete();
        return redirect()->route('dashboard.question-categories.index')
            ->with('success', 'Kategori soal berhasil dihapus.');
    }
}
