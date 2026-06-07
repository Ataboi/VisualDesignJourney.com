@extends('layouts.app')

@php
    $isEdit = $mode === 'edit';
@endphp

@section('title', $isEdit ? 'Edit Board' : 'Create Board')
@section('meta_description', 'Create and edit Visual Design Journey boards.')

@section('content')
    <section class="vk-container" style="max-width:940px;padding-top:54px;">
        <div style="display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:22px;">
            <div>
                <div class="badge badge-acid" style="margin-bottom:14px;">Boards</div>
                <h1 style="margin:0;color:#131B2E;font-size:clamp(36px,7vw,68px);font-weight:900;">{{ $isEdit ? 'Edit board' : 'Create board' }}</h1>
            </div>
            @if($isEdit)
                <a class="btn-secondary" href="{{ \App\Support\Vdj::boardUrl($board) }}">View board</a>
            @endif
        </div>

        <form method="post" enctype="multipart/form-data" action="{{ $isEdit ? route('boards.update', ['id' => $board->id]) : route('boards.store') }}" class="card-lg" style="padding:22px;border-radius:8px;display:grid;gap:16px;">
            @csrf
            @if($isEdit) @method('PUT') @endif

            <div>
                <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Title</label>
                <input class="input-field" name="title" value="{{ old('title', $board->title) }}" required maxlength="150">
                @error('title') <p style="color:#FF4D4D;font-size:12px;margin:7px 0 0;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Description</label>
                <textarea class="input-field" name="description" rows="5">{{ old('description', $board->description) }}</textarea>
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Style tags</label>
                <input class="input-field" name="style_tags" value="{{ old('style_tags', $board->style_tags) }}" placeholder="Minimalist, Bauhaus, Y2K">
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Image URLs</label>
                <textarea class="input-field" name="image_urls" rows="5" placeholder="https://...">{{ old('image_urls') }}</textarea>
                <p style="margin:7px 0 0;color:#787583;font-size:12px;">One image URL per line. Uploaded files are also supported.</p>
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Upload images</label>
                <input class="input-field" type="file" name="images[]" accept="image/*" multiple>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button class="btn-primary" type="submit" name="action" value="publish">{{ $isEdit ? 'Save changes' : 'Publish board' }}</button>
                <button class="btn-secondary" type="submit" name="action" value="draft">Save draft</button>
            </div>
        </form>
    </section>
@endsection
