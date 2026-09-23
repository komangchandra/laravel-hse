<?php

namespace App\Http\Controllers;

use App\Models\QuestionCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', QuestionCategory::class);
        $search = $request->input('search');
        $questionCategories = QuestionCategory::visibleTo($request->user())->when($search, function ($query, $search) {
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
        $this->authorize('create', QuestionCategory::class);

        return view('dashboard.question-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', QuestionCategory::class);
        $ownerId = $request->user()->isDeveloper() ? null : $request->user()->ownerOrganizationId();
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('question_categories', 'name')
                    ->where(fn ($query) => $ownerId === null
                        ? $query->whereNull('owner_id')
                        : $query->where('owner_id', $ownerId)),
            ],
            'description' => 'required|string',
            'measured' => 'required|string',
            'measurable' => 'required|string',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Nama kategori sudah digunakan.',
            'description.required' => 'Deskripsi wajib diisi.',
            'measured.required' => 'Aspek yang diukur wajib diisi.',
            'measurable.required' => 'Aspek terukur wajib diisi.',
        ]);

        $validated['owner_id'] = $ownerId;
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
        $this->authorize('update', $questionCategory);

        return view('dashboard.question-categories.edit', compact('questionCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, QuestionCategory $questionCategory)
    {
        $this->authorize('update', $questionCategory);
        $ownerId = $questionCategory->owner_id;
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('question_categories', 'name')
                    ->where(fn ($query) => $ownerId === null
                        ? $query->whereNull('owner_id')
                        : $query->where('owner_id', $ownerId))
                    ->ignore($questionCategory),
            ],
            'description' => 'required|string',
            'measured' => 'required|string',
            'measurable' => 'required|string',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Nama kategori sudah digunakan.',
            'description.required' => 'Deskripsi wajib diisi.',
            'measured.required' => 'Aspek yang diukur wajib diisi.',
            'measurable.required' => 'Aspek terukur wajib diisi.',
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
        $this->authorize('delete', $questionCategory);
        $questionCategory->delete();

        return redirect()->route('dashboard.question-categories.index')
            ->with('success', 'Kategori soal berhasil dihapus.');
    }
}
