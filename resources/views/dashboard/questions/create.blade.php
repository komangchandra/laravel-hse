@extends('layouts.admin')

@section('title', 'Buat Soal')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Buat Soal</h4>
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
                <li class="breadcrumb-item active">Buat Soal Baru</li>
            </ol>
        </nav>
    </div>

    <a href="{{ route('dashboard.questions.index') }}"
        class="btn btn-sm btn-outline-secondary shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">

        <form method="POST"
            action="{{ route('dashboard.questions.store') }}"
            enctype="multipart/form-data">

            @csrf

            {{-- CATEGORY + QUESTION --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="category_id" class="form-label fw-bold">
                        Kategori Soal <span class="text-danger">*</span>
                    </label>

                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-folder"></i>
                        </span>

                        <select name="category_id"
                            id="category_id"
                            class="form-control border-start-0 @error('category_id') is-invalid @enderror"
                            required>

                            <option value="" disabled selected>
                                Pilih kategori soal...
                            </option>

                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
                </div>

                <div class="col-md-6">
                    <label for="question" class="form-label fw-bold">
                        Pertanyaan <span class="text-danger">*</span>
                    </label>

                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-question-circle"></i>
                        </span>

                        <textarea name="question"
                            id="question"
                            class="form-control border-start-0 @error('question') is-invalid @enderror"
                            placeholder="Masukkan pertanyaan soal..."
                            required>{{ old('question') }}</textarea>

                        @error('question')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- PHOTO --}}
            <div class="row mb-4">
                <div class="col-md-6">

                    <label for="photo_path" class="form-label fw-bold">
                        Gambar Soal
                    </label>

                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-image"></i>
                        </span>

                        <input type="file"
                            name="photo_path"
                            id="photo_path"
                            class="form-control border-start-0 @error('photo_path') is-invalid @enderror"
                            accept="image/*">

                        @error('photo_path')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <small class="text-muted">
                        Opsional. Format: JPG, JPEG, PNG.
                    </small>

                </div>
            </div>

            {{-- TYPE + SCORE --}}
            <div class="row mb-4">

                <div class="col-md-6">
                    <label for="question-type" class="form-label fw-bold">
                        Tipe Soal <span class="text-danger">*</span>
                    </label>

                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-ui-checks-grid"></i>
                        </span>

                        <select name="type"
                            id="question-type"
                            class="form-control border-start-0 @error('type') is-invalid @enderror"
                            required>

                            <option value="">Pilih Tipe...</option>

                            <option value="multiple_choice"
                                {{ old('type') == 'multiple_choice' ? 'selected' : '' }}>
                                Pilihan Ganda
                            </option>

                            <option value="essay_auto"
                                {{ old('type') == 'essay_auto' ? 'selected' : '' }}>
                                Essay (Auto)
                            </option>
                        </select>

                        @error('type')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="score" class="form-label fw-bold">
                        Nilai Soal <span class="text-danger">*</span>
                    </label>

                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-star"></i>
                        </span>

                        <input type="number"
                            name="score"
                            id="score"
                            class="form-control border-start-0 @error('score') is-invalid @enderror"
                            placeholder="e.g. 10"
                            value="{{ old('score', 0) }}"
                            min="0"
                            required>

                        @error('score')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

            </div>

            {{-- MULTIPLE CHOICE --}}
            <div id="multiple-choice-section"
                class="border rounded-3 p-4 bg-light mb-4"
                style="display:none">

                <h5 class="fw-bold text-primary mb-3">
                    <i class="bi bi-list-check me-1"></i>
                    Pilihan Jawaban
                </h5>

                @foreach (['A','B','C'] as $key => $label)

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Pilihan {{ $label }}
                        </label>

                        <div class="input-group">

                            <div class="input-group-text bg-white">
                                <input type="radio"
                                    name="correct_option"
                                    value="{{ $label }}">
                            </div>

                            <input type="text"
                                name="options[{{ $key }}][answer]"
                                class="form-control"
                                placeholder="Masukkan jawaban {{ $label }}">

                            <input type="hidden"
                                name="options[{{ $key }}][label]"
                                value="{{ $label }}">

                        </div>

                    </div>

                @endforeach

                <small class="text-muted">
                    Pilih radio button untuk menentukan jawaban yang benar.
                </small>

            </div>

            {{-- ESSAY AUTO --}}
            <div id="essay-section"
                class="border rounded-3 p-4 bg-light mb-4"
                style="display:none">

                <h5 class="fw-bold text-primary mb-3">
                    <i class="bi bi-pencil-square me-1"></i>
                    Keyword Jawaban Essay
                </h5>

                <div id="keyword-wrapper">

                    <div class="row mb-3 keyword-row">

                        <div class="col-md-8">
                            <input type="text"
                                name="keywords[0][keyword]"
                                class="form-control"
                                placeholder="Masukkan keyword jawaban">
                        </div>

                        <div class="col-md-4">
                            <input type="number"
                                name="keywords[0][score]"
                                class="form-control"
                                placeholder="Nilai keyword">
                        </div>

                    </div>

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
                    Simpan

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
let keywordIndex = 1;

document.getElementById('add-keyword').addEventListener('click', function () {

    const wrapper = document.getElementById('keyword-wrapper');

    wrapper.insertAdjacentHTML('beforeend', `
        <div class="row mb-3 keyword-row">

            <div class="col-md-8">
                <input type="text"
                    name="keywords[${keywordIndex}][keyword]"
                    class="form-control"
                    placeholder="Masukkan keyword jawaban">
            </div>

            <div class="col-md-4">
                <input type="number"
                    name="keywords[${keywordIndex}][score]"
                    class="form-control"
                    placeholder="Nilai keyword">
            </div>

        </div>
    `);

    keywordIndex++;
});
</script>

@endsection