@extends('layouts.app')
@section('title', 'JWST Галерея')

@section('content')
<h1 class="mb-4">JWST — Последние снимки</h1>
<div class="row g-3" id="jwstGallery">
    <div class="col-12 text-center">
        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Загрузка...</span></div>
    </div>
</div>

<script>
async function loadJwst() {
    const res = await fetch('/api/jwst/feed?perPage=24');
    const { items } = await res.json();
    const container = document.getElementById('jwstGallery');
    container.innerHTML = items.map(item => `
        <div class="col-md-4 col-lg-3">
            <a href="${item.link}" target="_blank">
                <img src="${item.url}" class="img-fluid rounded shadow" style="height: 220px; object-fit: cover; width: 100%">
                <div class="small text-muted mt-1">${item.caption}</div>
            </a>
        </div>
    `).join('');
}
loadJwst();
</script>
@endsection