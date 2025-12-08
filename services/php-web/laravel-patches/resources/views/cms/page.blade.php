@extends('layouts.app')

@section('title', $title ?? 'Страница')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <article class="bg-white rounded-4 shadow p-5">
                @if(isset($title))
                    <h1 class="display-5 fw-bold text-primary mb-4">{{ $title }}</h1>
                @endif

                <div class="prose prose-lg max-w-none">
                    {!! $html ?? '<p class="text-muted">Контент не найден.</p>' !!}
                </div>

                <div class="mt-5 text-center">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                        ← На главную
                    </a>
                </div>
            </article>
        </div>
    </div>
</div>
@endsection