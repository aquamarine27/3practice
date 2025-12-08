@extends('layouts.app')
@section('title', 'Астрособытия')

@section('content')
<h1 class="mb-4">Астрономические события</h1>
<div class="row g-3">
    <div class="col-md-4">
        <input type="number" step="0.0001" class="form-control" id="lat" value="55.7558" placeholder="Широта">
    </div>
    <div class="col-md-4">
        <input type="number" step="0.0001" class="form-control" id="lon" value="37.6176" placeholder="Долгота">
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary w-100" onclick="loadEvents()">Показать</button>
    </div>
</div>

<div id="eventsList" class="mt-4"></div>

<script>
async function loadEvents() {
    const lat = document.getElementById('lat').value;
    const lon = document.getElementById('lon').value;
    const res = await fetch(`/api/astro/events?lat=${lat}&lon=${lon}&days=7`);
    const data = await res.json();
    document.getElementById('eventsList').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
}
loadEvents();
</script>
@endsection