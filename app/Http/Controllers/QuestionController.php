<?php

namespace App\Http\Controllers;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\QuestionKeyword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $questions = Question::when($search, function ($query, $search){
            return $query   ->where('question', 'like', "%{$search}%")
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
    public function create()
    {
        $categories = QuestionCategory::all();

        return view('dashboard.questions.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:question_categories,id',
            'question' => 'required|string',
            'type' => 'required|in:multiple_choice,essay_auto',
            'score' => 'required|integer|min:0',

            // PHOTO
            'photo_path' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        DB::transaction(function () use ($request) {

            // =====================
            // UPLOAD PHOTO
            // =====================
            $photoPath = null;

            if ($request->hasFile('photo_path')) {
                $photoPath = $request->file('photo_path')
                    ->store('questions', 'public');
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
    public function edit(Question $question)
    {
        $question->load([
            'options',
            'keywords',
        ]);

        $categories = QuestionCategory::latest()->get();

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
        $request->validate([
            'category_id' => 'required|exists:question_categories,id',
            'question' => 'required|string',
            'type' => 'required|in:multiple_choice,essay_auto',
            'score' => 'required|integer|min:0',

            // PHOTO
            'photo_path' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        DB::transaction(function () use ($request, $question) {

            // =====================
            // UPLOAD PHOTO
            // =====================
            $photoPath = $question->photo_path;

            if ($request->hasFile('photo_path')) {

                // hapus foto lama
                if ($question->photo_path &&
                    Storage::disk('public')->exists($question->photo_path)) {

                    Storage::disk('public')->delete($question->photo_path);
                }

                $photoPath = $request->file('photo_path')
                    ->store('questions', 'public');
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

                    if (!empty($option['answer'])) {

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

                    if (!empty($keyword['keyword'])) {

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
        DB::transaction(function () use ($question) {

            // =====================
            // DELETE PHOTO
            // =====================
            if ($question->photo_path &&
                Storage::disk('public')->exists($question->photo_path)) {

                Storage::disk('public')->delete($question->photo_path);
            }

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
}
