<?php

namespace App\Http\Controllers;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\QuestionKeyword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Question::class);
        $search = $request->input('search');
        $questions = Question::visibleTo($request->user())->when($search, function ($query, $search) {
            return $query->where('question', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%");
        })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('dashboard.questions.index', compact('questions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Question::class);
        $categories = $this->writableCategories($request);

        return view('dashboard.questions.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Question::class);
        $request->validate([
            'category_id' => 'required|exists:question_categories,id',
            'question' => 'required|string',
            'type' => ['required', Rule::in(['multiple_choice'])],
            'score' => 'required|integer|min:1|max:1000',
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*.label' => ['required', 'string', 'max:3', 'distinct'],
            'options.*.answer' => ['required', 'string', 'max:1000'],
            'correct_option' => ['required', 'string', 'max:3'],

            // PHOTO
            'photo_path' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        $this->validateMultipleChoice($request);

        $this->ensureWritableCategory($request, (int) $request->category_id);

        DB::transaction(function () use ($request) {

            // =====================
            // UPLOAD PHOTO
            // =====================
            $photoPath = null;

            if ($request->hasFile('photo_path')) {
                $photoPath = $request->file('photo_path')
                    ->store('questions', 'local');
            }

            $question = Question::create([
                'category_id' => $request->category_id,
                'question' => $request->question,
                'type' => $request->type,
                'score' => $request->score,

                // PHOTO
                'photo_path' => $photoPath,
            ]);

            // =====================
            // PILIHAN GANDA
            // =====================
            if ($request->type === 'multiple_choice') {

                foreach ($request->options as $option) {

                    AnswerOption::create([
                        'question_id' => $question->id,
                        'label' => $option['label'],
                        'answer' => $option['answer'],
                        'is_correct' => $request->correct_option === $option['label'],
                    ]);

                }
            }

            // =====================
            // ESSAY AUTO
            // =====================
            if ($request->type === 'essay_auto') {

                foreach ($request->keywords as $keyword) {

                    QuestionKeyword::create([
                        'question_id' => $question->id,
                        'keyword' => $keyword['keyword'],
                        'score' => $keyword['score'],
                    ]);

                }
            }
        });

        return redirect()
            ->route('dashboard.questions.index')
            ->with('success', 'Soal berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question)
    {
        $this->authorize('view', $question);
        $question->load([
            'category',
            'options',
            'keywords',
        ]);

        return view('dashboard.questions.show', compact('question'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Question $question)
    {
        $this->authorize('update', $question);
        $question->load([
            'options',
            'keywords',
        ]);

        $categories = $this->writableCategories($request);

        return view('dashboard.questions.edit', compact(
            'question',
            'categories'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Question $question)
    {
        $this->authorize('update', $question);
        $request->validate([
            'category_id' => 'required|exists:question_categories,id',
            'question' => 'required|string',
            'type' => ['required', Rule::in(['multiple_choice'])],
            'score' => 'required|integer|min:1|max:1000',
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*.label' => ['required', 'string', 'max:3', 'distinct'],
            'options.*.answer' => ['required', 'string', 'max:1000'],
            'correct_option' => ['required', 'string', 'max:3'],

            // PHOTO
            'photo_path' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        $this->validateMultipleChoice($request);

        $this->ensureWritableCategory($request, (int) $request->category_id);

        DB::transaction(function () use ($request, $question) {

            // =====================
            // UPLOAD PHOTO
            // =====================
            $photoPath = $question->photo_path;

            if ($request->hasFile('photo_path')) {

                // hapus foto lama
                $this->deletePhoto($question->photo_path);

                $photoPath = $request->file('photo_path')
                    ->store('questions', 'local');
            }

            // =====================
            // UPDATE QUESTION
            // =====================
            $question->update([
                'category_id' => $request->category_id,
                'question' => $request->question,
                'type' => $request->type,
                'score' => $request->score,
                'photo_path' => $photoPath,
            ]);

            // =====================
            // RESET RELATION
            // =====================
            $question->options()->delete();
            $question->keywords()->delete();

            // =====================
            // MULTIPLE CHOICE
            // =====================
            if ($request->type === 'multiple_choice') {

                foreach ($request->options as $option) {

                    if (! empty($option['answer'])) {

                        AnswerOption::create([
                            'question_id' => $question->id,
                            'label' => $option['label'],
                            'answer' => $option['answer'],
                            'is_correct' => $request->correct_option === $option['label'],
                        ]);

                    }
                }
            }

            // =====================
            // ESSAY AUTO
            // =====================
            if ($request->type === 'essay_auto') {

                foreach ($request->keywords as $keyword) {

                    if (! empty($keyword['keyword'])) {

                        QuestionKeyword::create([
                            'question_id' => $question->id,
                            'keyword' => $keyword['keyword'],
                            'score' => $keyword['score'] ?? 0,
                        ]);

                    }
                }
            }
        });

        return redirect()
            ->route('dashboard.questions.index')
            ->with('success', 'Soal berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question)
    {
        $this->authorize('delete', $question);
        DB::transaction(function () use ($question) {

            // =====================
            // DELETE PHOTO
            // =====================
            $this->deletePhoto($question->photo_path);

            // =====================
            // DELETE RELATION
            // =====================
            $question->options()->delete();
            $question->keywords()->delete();

            // =====================
            // DELETE QUESTION
            // =====================
            $question->delete();

        });

        return redirect()
            ->route('dashboard.questions.index')
            ->with('success', 'Soal berhasil dihapus');
    }

    private function ensureWritableCategory(Request $request, int $categoryId): void
    {
        $user = $request->user();
        $category = QuestionCategory::visibleTo($user)->find($categoryId);

        if (! $category || (! $user->isDeveloper() && $category->owner_id !== $user->ownerOrganizationId())) {
            throw ValidationException::withMessages([
                'category_id' => 'Soal hanya dapat disimpan pada kategori milik owner Anda.',
            ]);
        }
    }

    private function writableCategories(Request $request)
    {
        $user = $request->user();

        return QuestionCategory::query()
            ->when(
                ! $user->isDeveloper(),
                fn ($query) => $query->where('owner_id', $user->ownerOrganizationId())
            )
            ->orderBy('name')
            ->get();
    }

    private function validateMultipleChoice(Request $request): void
    {
        $labels = collect($request->input('options', []))->pluck('label');
        if ($labels->filter(fn ($label) => $label === $request->input('correct_option'))->count() !== 1) {
            throw ValidationException::withMessages([
                'correct_option' => 'Tepat satu opsi yang tersedia wajib dipilih sebagai jawaban benar.',
            ]);
        }
    }

    public function photo(Question $question): StreamedResponse
    {
        $this->authorize('view', $question);

        return $this->photoResponse($question);
    }

    private function photoResponse(Question $question): StreamedResponse
    {
        abort_unless($question->photo_path, 404);
        $disk = Storage::disk('local')->exists($question->photo_path) ? Storage::disk('local') : Storage::disk('public');
        abort_unless($disk->exists($question->photo_path), 404);

        return $disk->response($question->photo_path, 'soal-'.$question->id, ['Content-Disposition' => 'inline']);
    }

    private function deletePhoto(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
            Storage::disk('public')->delete($path);
        }
    }
}
