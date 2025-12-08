@extends('layouts.app')
@section('title', 'МКС')

@section('content')
<h1 class="mb-4">МКС в реальном времени</h1>
<div id="map" style="height: 500px; border-radius: 12px;" class="shadow"></div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const res = await fetch('/api/iss/last');
    const data = await res.json();
    const { latitude, longitude, altitude, velocity } = data.payload || {};

    const map = L.map('map').setView([latitude || 0, longitude || 0], 3);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    L.marker([latitude, longitude]).addTo(map)
        .bindPopup(`МКС<br>Высота: ${altitude} км<br>Скорость: ${velocity} км/ч`)
        .openPopup();
});
</script>
@endsection