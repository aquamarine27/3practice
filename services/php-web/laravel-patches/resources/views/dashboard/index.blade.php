@extends('layouts.app')
@section('title', 'Главная')

@section('content')
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <div class="col">
        <a href="{{ route('iss') }}" class="text-decoration-none">
            <div class="card h-100 shadow-sm border-0 hover-lift">
                <div class="card-body text-center py-5">
                    <i class="bi bi-globe2 display-1 text-primary mb-3"></i>
                    <h4 class="card-title text-dark">МКС в реальном времени</h4>
                    <p class="text-muted">Карта, траектория, скорость, высота</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col">
        <a href="{{ route('jwst') }}" class="text-decoration-none">
            <div class="card h-100 shadow-sm border-0 hover-lift">
                <div class="card-body text-center py-5">
                    <i class="bi bi-camera display-1 text-warning mb-3"></i>
                    <h4 class="card-title text-dark">JWST Галерея</h4>
                    <p class="text-muted">Последние снимки с фильтрами</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col">
        <a href="{{ route('osdr') }}" class="text-decoration-none">
            <div class="card h-100 shadow-sm border-0 hover-lift">
                <div class="card-body text-center py-5">
                    <i class="bi bi-database display-1 text-success mb-3"></i>
                    <h4 class="card-title text-dark">NASA OSDR</h4>
                    <p class="text-muted">Датасеты с поиском и сортировкой</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col">
        <a href="{{ route('astro') }}" class="text-decoration-none">
            <div class="card h-100 shadow-sm border-0 hover-lift">
                <div class="card-body text-center py-5">
                    <i class="bi bi-stars display-1 text-info mb-3"></i>
                    <h4 class="card-title text-dark">Астрособытия</h4>
                    <p class="text-muted">Метеоры, затмения, вспышки</p>
                </div>
            </div>
        </a>
    </div>
</div>

<style>
.hover-lift {
    transition: all 0.3s ease;
}
.hover-lift:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
}
</style>
@endsection