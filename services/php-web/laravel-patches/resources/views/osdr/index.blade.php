@extends('layouts.app')
@section('title', 'NASA OSDR')

@section('content')
<h1 class="mb-4">NASA Open Science Data Repository</h1>
<input type="text" id="search" class="form-control mb-3" placeholder="Поиск по названию..." onkeyup="filterTable()">

<table class="table table-striped" id="osdrTable">
    <thead class="table-dark">
        <tr>
            <th onclick="sortTable(0)">ID</th>
            <th onclick="sortTable(1)">Название</th>
            <th>Ссылка</th>
        </tr>
    </thead>
    <tbody>
        <!-- JS заполнит -->
    </tbody>
</table>

<script>
async function loadOsdr() {
    const res = await fetch('http://rust_iss:3000/osdr/list?limit=100');
    const data = await res.json();
    const tbody = document.querySelector('#osdrTable tbody');
    tbody.innerHTML = data.items.map(item => {
        const raw = item.raw || {};
        const title = raw.title || raw.name || '—';
        const url = raw.REST_URL || raw.rest_url || '#';
        return `<tr><td>${item.id}</td><td>${title}</td><td><a href="${url}" target="_blank">Открыть</a></td></tr>`;
    }).join('');
}
loadOsdr();

function filterTable() {
    const input = document.getElementById('search').value.toLowerCase();
    const rows = document.querySelectorAll('#osdrTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}
</script>
@endsection