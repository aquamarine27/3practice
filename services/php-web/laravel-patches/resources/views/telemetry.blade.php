@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h2 class="text-white text-center mb-5 fade-in">Telemetry</h2>

    <div class="text-center mb-5 fade-in fade-in-delay-1">
        <a href="/telemetry/export/csv" class="btn btn-outline-primary btn-lg mx-3">Скачать CSV</a>
        <a href="/telemetry/export/excel" class="btn btn-outline-success btn-lg mx-3">Скачать Excel</a>
    </div>

    <!-- График -->
    <div class="card bg-dark border-0 shadow-lg rounded-3 mb-5 fade-in fade-in-delay-2">
        <div class="card-body p-5">
            <h5 class="text-white text-center mb-4">Voltage & Temperature</h5>
            <canvas id="telemetryChart" height="140"></canvas>
        </div>
    </div>

    <!-- Таблица -->
    <div class="card bg-dark border-0 shadow-lg rounded-3 fade-in fade-in-delay-3">
        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-auto">
                    <select id="searchCol" class="form-select form-select-sm bg-secondary text-white border-secondary">
                        <option value="2">recorded_at</option>
                        <option value="3">voltage</option>
                        <option value="4">temp</option>
                        <option value="7" selected>source_file</option>
                    </select>
                </div>
                <div class="col">
                    <input type="text" id="searchInput" class="form-control form-control-sm bg-secondary text-white border-secondary" placeholder="Поиск по таблице...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-dark table-striped table-hover align-middle" id="dataTable">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th data-sort="1">telemetry_id</th>
                            <th data-sort="2">recorded_at</th>
                            <th data-sort="3">voltage</th>
                            <th data-sort="4">temp</th>
                            <th data-sort="5">mission_status</th>
                            <th>is_active</th>
                            <th data-sort="7">source_file</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td>{{ $record->id }}</td>
                                <td>{{ $record->telemetry_id ?? '—' }}</td>
                                <td>{{ $record->recorded_at }}</td>
                                <td>{{ $record->voltage }}</td>
                                <td>{{ $record->temp }}</td>
                                <td>{{ $record->mission_status ?? '—' }}</td>
                                <td>{{ $record->is_active ? 'TRUE' : 'FALSE' }}</td>
                                <td>{{ $record->source_file ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">нет данных</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .fade-in { animation: fadeUp 0.8s ease-out both; }
    .fade-in-delay-1 { animation-delay: 0.2s; }
    .fade-in-delay-2 { animation-delay: 0.4s; }
    .fade-in-delay-3 { animation-delay: 0.6s; }

    th[data-dir="asc"]::after  { content: " ▲"; color: #fff; }
    th[data-dir="desc"]::after { content: " ▼"; color: #fff; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.querySelector('#dataTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    const allTimes = [];
    const voltages = [];
    const temps = [];

    rows.forEach(tr => {
        if (tr.cells.length >= 8) {
            const time = tr.cells[2].textContent.trim();
            allTimes.push(time);
            voltages.push(parseFloat(tr.cells[3].textContent) || 0);
            temps.push(parseFloat(tr.cells[4].textContent) || 0);
        }
    });

    if (allTimes.length > 0) {
        const displayLabels = allTimes.map((time, index) => {
            if (index === 0 || index === allTimes.length - 1) {
                return time;
            }
            return ''; 
        });

        new Chart(document.getElementById('telemetryChart'), {
            type: 'line',
            data: {
                labels: displayLabels,
                datasets: [
                    {
                        label: 'Voltage (V)',
                        data: voltages,
                        borderColor: '#0d6efd',
                        tension: 0.3,
                        pointRadius: 0,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'Temperature (°C)',
                        data: temps,
                        borderColor: '#dc3545',
                        tension: 0.3,
                        pointRadius: 0,
                        pointHoverRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: '#fff' } },
                    tooltip: {
                        callbacks: {
                            title: (items) => allTimes[items[0].dataIndex] || ''
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#ccc' },
                        grid: { color: '#333' }
                    },
                    y: { ticks: { color: '#ccc' }, grid: { color: '#333' } }
                }
            }
        });
    }

    // search
    const searchInput = document.getElementById('searchInput');
    const colSelect = document.getElementById('searchCol');

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase();
        const col = parseInt(colSelect.value);

        tbody.querySelectorAll('tr').forEach(row => {
            const text = row.cells[col]?.textContent.toLowerCase() || '';
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });

    // sort
    const table = document.getElementById('dataTable');
    let sortDir = {};

    table.querySelectorAll('th[data-sort]').forEach(th => {
        th.addEventListener('click', () => {
            const col = parseInt(th.dataset.sort);
            const isAscending = sortDir[col] = !sortDir[col];

            table.querySelectorAll('th[data-sort]').forEach(h => h.removeAttribute('data-dir'));
            th.setAttribute('data-dir', isAscending ? 'asc' : 'desc');

            const rowsArray = Array.from(tbody.querySelectorAll('tr'));
            rowsArray.sort((a, b) => {
                const aVal = a.cells[col]?.textContent.trim() || '';
                const bVal = b.cells[col]?.textContent.trim() || '';
                const comparison = aVal.localeCompare(bVal, undefined, { numeric: true });
                return isAscending ? comparison : -comparison;
            });

            rowsArray.forEach(r => tbody.appendChild(r));
        });
    });
});
</script>
@endsection