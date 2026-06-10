@extends('layouts.admin')

@section('title', 'Edit Simper')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h4 class="fw-bold mb-0">
            Edit Simper
        </h4>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">

                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}"
                        class="text-decoration-none">

                        Dashboard

                    </a>
                </li>

                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.simpers.index') }}"
                        class="text-decoration-none">

                        Simper

                    </a>
                </li>

                <li class="breadcrumb-item active">
                    Edit Simper
                </li>

            </ol>
        </nav>

    </div>

    <a href="{{ route('dashboard.simpers.index') }}"
        class="btn btn-sm btn-outline-secondary shadow-sm">

        <i class="bi bi-arrow-left me-1"></i>
        Kembali

    </a>

</div>

<div class="card border-0 shadow-sm rounded-3">

    <div class="card-body p-4">

        <form method="POST"
            action="{{ route('dashboard.simpers.update', $simper) }}">

            @csrf
            @method('PUT')

            {{-- BASIC --}}
            <div class="row mb-4">

                {{-- MANPOWER --}}
                <div class="col-md-4">

                    <label class="form-label fw-bold">
                        Manpower
                        <span class="text-danger">*</span>
                    </label>

                    <select name="manpower_id"
                        class="form-select @error('manpower_id') is-invalid @enderror"
                        required>

                        <option value="">
                            Pilih Manpower
                        </option>

                        @foreach($manpowers as $manpower)

                            <option value="{{ $manpower->id }}"
                                @selected(old('manpower_id', $simper->manpower_id) == $manpower->id)>

                                {{ $manpower->name }}

                            </option>

                        @endforeach

                    </select>

                    @error('manpower_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                {{-- PARTNER --}}
                <div class="col-md-4">

                    <label class="form-label fw-bold">
                        Partner
                        <span class="text-danger">*</span>
                    </label>

                    <select name="partner_id"
                        class="form-select @error('partner_id') is-invalid @enderror"
                        required>

                        <option value="">
                            Pilih Partner
                        </option>

                        @foreach($partners as $partner)

                            <option value="{{ $partner->id }}"
                                @selected(old('partner_id', $simper->partner_id) == $partner->id)>

                                {{ $partner->short_name }}

                            </option>

                        @endforeach

                    </select>

                    @error('partner_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                {{-- CODE --}}
                <div class="col-md-4">

                    <label class="form-label fw-bold">
                        Code Simper
                        <span class="text-danger">*</span>
                    </label>

                    <input type="text"
                        name="code"
                        class="form-control @error('code') is-invalid @enderror"
                        value="{{ old('code', $simper->code) }}"
                        required>

                    @error('code')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>

            {{-- CATEGORIES --}}
            <div class="mb-3">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h6 class="fw-bold mb-0">
                        Kategori Simper
                    </h6>

                    <button type="button"
                        class="btn btn-sm btn-primary"
                        id="add-category">

                        <i class="bi bi-plus-lg"></i>
                        Tambah

                    </button>

                </div>

                <div id="category-wrapper">

                    @foreach($simper->categories as $index => $category)

                    <div class="row mb-3 category-item">

                        {{-- CATEGORY --}}
                        <div class="col-md-7">

                            <select name="categories[{{ $index }}][id]"
                                class="form-select"
                                required>

                                <option value="">
                                    Pilih Kategori
                                </option>

                                @foreach($categories as $item)

                                    <option value="{{ $item->id }}"
                                        @selected($item->id == $category->id)>

                                        {{ $item->name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                        {{-- LEVEL --}}
                        <div class="col-md-4">

                            <select name="categories[{{ $index }}][level]"
                                class="form-select"
                                required>

                                <option value="full"
                                    @selected($category->pivot->level == 'full')>

                                    Full

                                </option>

                                <option value="partial"
                                    @selected($category->pivot->level == 'partial')>

                                    Partial

                                </option>

                                <option value="test"
                                    @selected($category->pivot->level == 'test')>

                                    Test

                                </option>

                                <option value="latihan"
                                    @selected($category->pivot->level == 'latihan')>

                                    Latihan

                                </option>

                            </select>

                        </div>

                        {{-- REMOVE --}}
                        <div class="col-md-1">

                            <button type="button"
                                class="btn btn-danger remove-category w-100">

                                <i class="bi bi-trash"></i>

                            </button>

                        </div>

                    </div>

                    @endforeach

                </div>

            </div>

            {{-- ACTION --}}
            <div class="mt-5 pt-3 border-top d-flex gap-2">

                <button type="submit"
                    class="btn btn-warning px-4 shadow-sm">

                    <i class="bi bi-save me-1"></i>
                    Update

                </button>

                <a href="{{ route('dashboard.simpers.index') }}"
                    class="btn btn-light border px-4">

                    Batal

                </a>

            </div>

        </form>

    </div>

</div>

{{-- SCRIPT --}}
<script>

    let index = {{ $simper->categories->count() }};

    document.getElementById('add-category')
        .addEventListener('click', function () {

            let html = `
                <div class="row mb-3 category-item">

                    <div class="col-md-7">

                        <select name="categories[\${index}][id]"
                            class="form-select"
                            required>

                            <option value="">
                                Pilih Kategori
                            </option>

                            @foreach($categories as $category)

                                <option value="{{ $category->id }}">
                                    {{ $category->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div class="col-md-4">

                        <select name="categories[\${index}][level]"
                            class="form-select"
                            required>

                            <option value="">
                                Pilih Level
                            </option>

                            <option value="full">
                                FULL
                            </option>

                            <option value="partial">
                                PARTIAL
                            </option>

                            <option value="T">
                                T
                            </option>

                            <option value="latihan">
                                LATIHAN
                            </option>

                        </select>

                    </div>

                    <div class="col-md-1">

                        <button type="button"
                            class="btn btn-danger remove-category w-100">

                            <i class="bi bi-trash"></i>

                        </button>

                    </div>

                </div>
            `;

            document.getElementById('category-wrapper')
                .insertAdjacentHTML('beforeend', html);

            index++;

        });

    document.addEventListener('click', function (e) {

        if (e.target.closest('.remove-category')) {

            e.target.closest('.category-item').remove();

        }

    });

</script>

@endsection