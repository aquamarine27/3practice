@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="card bg-dark border-0 shadow-lg rounded-3">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-white mb-0">NASA OSDR</h3>
        <div class="small text-muted">Источник: <code class="text-primary">{{ $src }}</code></div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-auto">
          <select id="searchCol" class="form-select form-select-sm bg-secondary text-white border-secondary">
            <option value="1">dataset_id</option>
            <option value="2" selected>title</option>
            <option value="4">updated_at</option>
          </select>
        </div>
        <div class="col">
          <input type="text" id="searchInput" class="form-control form-control-sm bg-secondary text-white border-secondary" placeholder="Поиск по таблице...">
        </div>
      </div>

      <div class="table-wrapper">
        <div class="table-fade-in">
          <div class="table-responsive">
            <table class="table table-sm table-dark table-striped table-hover align-middle mb-0" id="dataTable">
              <thead>
                <tr>
                  <th data-sort="0" style="cursor:pointer; width:5%">#</th>
                  <th data-sort="1" style="cursor:pointer">dataset_id</th>
                  <th data-sort="2" style="cursor:pointer">title</th>
                  <th style="width:10%">REST_URL</th>
                  <th data-sort="4" style="cursor:pointer">updated_at</th>
                  <th data-sort="5" style="cursor:pointer">inserted_at</th>
                  <th style="width:8%">raw</th>
                </tr>
              </thead>
              <tbody>
              @forelse($items as $row)
                <tr>
                  <td>{{ $row['id'] }}</td>
                  <td>{{ $row['dataset_id'] ?? '—' }}</td>
                  <td class="text-truncate" style="max-width:380px;">
                    <span title="{{ $row['title'] ?? '—' }}">{{ $row['title'] ?? '—' }}</span>
                  </td>
                  <td>
                    @if(!empty($row['rest_url']))
                      <a href="{{ $row['rest_url'] }}" target="_blank" rel="noopener" class="text-primary">открыть</a>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td>{{ $row['updated_at'] ?? '—' }}</td>
                  <td>{{ $row['inserted_at'] ?? '—' }}</td>
                  <td>
                    <button class="btn btn-outline-light btn-sm" data-bs-toggle="collapse" data-bs-target="#json-{{ $row['id'] }}-{{ md5($row['dataset_id'] ?? (string)$row['id']) }}">
                      JSON
                    </button>
                  </td>
                </tr>
                <tr class="collapse bg-black" id="json-{{ $row['id'] }}-{{ md5($row['dataset_id'] ?? (string)$row['id']) }}">
                  <td colspan="7" class="p-3">
                    <pre class="mb-0 text-light small" style="max-height:320px; overflow:auto; background:#111; padding:1rem; border-radius:8px;">
{{ json_encode($row['raw'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) }}</pre>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-5">данные не найдены</td>
                </tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .table-dark thead th {
    background-color: #222 !important;
    border-color: #444 !important;
    color: #e0e0e0;
  }
  .table-dark thead th.active-sort {
    background-color: #007bff !important;
    color: white !important;
  }
  .table-hover tbody tr:hover {
    background-color: rgba(0,123,255,0.15) !important;
  }
  .text-truncate span {
    display: inline-block;
    max-width: 100%;
  }
  pre {
    font-size: 0.85rem;
  }

  .table-wrapper {
    overflow: hidden;
  }
  .table-fade-in {
    animation: tableFadeIn 0.8s ease-out;
  }
  @keyframes tableFadeIn {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const table = document.getElementById('dataTable');
  const tbody = table.querySelector('tbody');
  const searchInput = document.getElementById('searchInput');
  const columnSelect = document.getElementById('searchCol');
  const sortDirections = {};

  // search
  searchInput.addEventListener('input', () => {
    const query = searchInput.value.toLowerCase().trim();
    const colIndex = parseInt(columnSelect.value);

    tbody.querySelectorAll('tr:not(.collapse)').forEach(row => {
      const cellText = row.cells[colIndex]?.textContent.toLowerCase() || '';
      row.style.display = cellText.includes(query) ? '' : 'none';
    });
  });

  // sort
  table.querySelectorAll('th[data-sort]').forEach(header => {
    header.addEventListener('click', () => {
      const col = parseInt(header.dataset.sort);
      const isAscending = sortDirections[col] = !sortDirections[col];

      table.querySelectorAll('th[data-sort]').forEach(h => {
        h.classList.remove('active-sort');
        delete h.dataset.dir;
      });

      header.classList.add('active-sort');
      header.dataset.dir = isAscending ? 'asc' : 'desc';

      const rows = Array.from(tbody.querySelectorAll('tr:not(.collapse)'));

      rows.sort((rowA, rowB) => {
        const valA = rowA.cells[col]?.textContent.trim() || '';
        const valB = rowB.cells[col]?.textContent.trim() || '';

        const comparison = valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' });
        return isAscending ? comparison : -comparison;
      });

      rows.forEach(r => tbody.appendChild(r));
    });
  });
});
</script>
@endsection