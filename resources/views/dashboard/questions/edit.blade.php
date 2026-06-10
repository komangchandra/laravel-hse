@extends('layouts.admin')

@section('title', 'Edit Soal')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Edit Soal</h4>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none">
                        Dashboard
                    </a>
                </li>

                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.questions.index') }}" class="text-decoration-none">
                        Soal
                    </a>
                </li>

                <li class="breadcrumb-item active">
                    Edit Soal
                </li>
            </ol>
        </nav>
    </div>

    <a href="{{ route('dashboard.questions.index') }}"
        class="btn btn-sm btn-outline-secondary shadow-sm">

        <i class="bi bi-arrow-left me-1"></i>
        Kembali

    </a>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">

        <form method="POST"
            action="{{ route('dashboard.questions.update', $question) }}"
            enctype="multipart/form-data">

            @csrf
            @method('PUT')

            {{-- CATEGORY + QUESTION --}}
            <div class="row mb-4">

                <div class="col-md-6">

                    <label class="form-label fw-bold">
                        Kategori Soal <span class="text-danger">*</span>
                    </label>

                    <select name="category_id"
                        class="form-control @error('category_id') is-invalid @enderror"
                        required>

                        @foreach($categories as $category)

                            <option value="{{ $category->id }}"
                                {{ old('category_id', $question->category_id) == $category->id ? 'selected' : '' }}>

                                {{ $category->name }}

                            </option>

                        @endforeach

                    </select>

                    @error('category_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                <div class="col-md-6">

                    <label class="form-label fw-bold">
                        Pertanyaan <span class="text-danger">*</span>
                    </label>

                    <textarea name="question"
                        rows="3"
                        class="form-control @error('question') is-invalid @enderror"
                        required>{{ old('question', $question->question) }}</textarea>

                    @error('question')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>

            {{-- PHOTO --}}
            <div class="row mb-4">

                <div class="col-md-12">

                    <label class="form-label fw-bold">
                        Gambar Soal
                    </label>

                    <input type="file"
                        name="photo_path"
                        class="form-control @error('photo_path') is-invalid @enderror"
                        accept="image/*">

                    @error('photo_path')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    @if($question->photo_path)

                        <div class="mt-3">

                            <img src="{{ asset('storage/' . $question->photo_path) }}"
                                class="img-thumbnail"
                                style="max-height:200px">

                        </div>

                    @endif

                </div>

            </div>

            {{-- TYPE + SCORE --}}
            <div class="row mb-4">

                <div class="col-md-6">

                    <label class="form-label fw-bold">
                        Tipe Soal
                    </label>

                    <select name="type"
                        id="question-type"
                        class="form-control"
                        required>

                        <option value="multiple_choice"
                            {{ old('type', $question->type) == 'multiple_choice' ? 'selected' : '' }}>

                            Pilihan Ganda

                        </option>

                        <option value="essay_auto"
                            {{ old('type', $question->type) == 'essay_auto' ? 'selected' : '' }}>

                            Essay (Auto)

                        </option>

                    </select>

                </div>

                <div class="col-md-6">

                    <label class="form-label fw-bold">
                        Nilai Soal
                    </label>

                    <input type="number"
                        name="score"
                        class="form-control"
                        value="{{ old('score', $question->score) }}"
                        required>

                </div>

            </div>

            {{-- MULTIPLE CHOICE --}}
            <div id="multiple-choice-section"
                class="border rounded-3 p-4 bg-light mb-4"
                style="{{ old('type', $question->type) == 'multiple_choice' ? '' : 'display:none' }}">

                <h5 class="fw-bold text-primary mb-3">
                    Pilihan Jawaban
                </h5>

                @foreach (['A','B','C'] as $key => $label)

                    @php
                        $option = $question->options->where('label', $label)->first();
                    @endphp

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Pilihan {{ $label }}
                        </label>

                        <div class="input-group">

                            <div class="input-group-text">

                                <input type="radio"
                                    name="correct_option"
                                    value="{{ $label }}"
                                    {{ $option && $option->is_correct ? 'checked' : '' }}>

                            </div>

                            <input type="text"
                                name="options[{{ $key }}][answer]"
                                class="form-control"
                                value="{{ $option->answer ?? '' }}"
                                placeholder="Jawaban {{ $label }}">

                            <input type="hidden"
                                name="options[{{ $key }}][label]"
                                value="{{ $label }}">

                        </div>

                    </div>

                @endforeach

            </div>

            {{-- ESSAY AUTO --}}
            <div id="essay-section"
                class="border rounded-3 p-4 bg-light mb-4"
                style="{{ old('type', $question->type) == 'essay_auto' ? '' : 'display:none' }}">

                <h5 class="fw-bold text-primary mb-3">
                    Keyword Jawaban
                </h5>

                <div id="keyword-wrapper">

                    @foreach($question->keywords as $index => $keyword)

                        <div class="row mb-3 keyword-row">

                            <div class="col-md-8">

                                <input type="text"
                                    name="keywords[{{ $index }}][keyword]"
                                    class="form-control"
                                    value="{{ $keyword->keyword }}"
                                    placeholder="Keyword">

                            </div>

                            <div class="col-md-4">

                                <input type="number"
                                    name="keywords[{{ $index }}][score]"
                                    class="form-control"
                                    value="{{ $keyword->score }}"
                                    placeholder="Nilai">

                            </div>

                        </div>

                    @endforeach

                </div>

                <button type="button"
                    class="btn btn-sm btn-outline-primary"
                    id="add-keyword">

                    <i class="bi bi-plus-circle me-1"></i>
                    Tambah Keyword

                </button>

            </div>

            {{-- ACTION --}}
            <div class="mt-5 pt-3 border-top d-flex gap-2">

                <button type="submit"
                    class="btn btn-success px-4 shadow-sm">

                    <i class="bi bi-save me-1"></i>
                    Update

                </button>

                <a href="{{ route('dashboard.questions.index') }}"
                    class="btn btn-light border px-4">

                    Batal

                </a>

            </div>

        </form>

    </div>
</div>

{{-- SHOW / HIDE SECTION --}}
<script>
document.getElementById('question-type').addEventListener('change', function () {

    document.getElementById('multiple-choice-section').style.display =
        this.value === 'multiple_choice'
            ? 'block'
            : 'none';

    document.getElementById('essay-section').style.display =
        this.value === 'essay_auto'
            ? 'block'
            : 'none';
});
</script>

{{-- ADD KEYWORD --}}
<script>
let keywordIndex = {{ $question->keywords->count() }};

document.getElementById('add-keyword').addEventListener('click', function () {

    const wrapper = document.getElementById('keyword-wrapper');

    wrapper.insertAdjacentHTML('beforeend', `
        <div class="row mb-3 keyword-row">

            <div class="col-md-8">
                <input type="text"
                    name="keywords[${keywordIndex}][keyword]"
                    class="form-control"
                    placeholder="Keyword">
            </div>

            <div class="col-md-4">
                <input type="number"
                    name="keywords[${keywordIndex}][score]"
                    class="form-control"
                    placeholder="Nilai">
            </div>

        </div>
    `);

    keywordIndex++;
});
</script>

@endsection