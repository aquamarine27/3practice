@extends('layouts.app')

@section('content')
<div class="container pb-5">
  <h2 class="mb-5 text-white fade-in text-center">Астрономические события <span class="text-primary">(AstronomyAPI)</span></h2>

  <div class="card bg-dark text-white border-0 shadow-lg rounded-3 fade-in fade-in-delay-1">
    <div class="card-body p-4">
      <form id="astroForm" class="row g-3 align-items-end mb-4">
        <div class="col-12 col-sm-6 col-md-auto flex-fill">
          <label class="form-label text-light mb-1">Широта</label>
          <input type="number" step="0.0001" class="form-control form-control-sm bg-secondary text-white border-0" name="lat" value="55.7558">
        </div>
        <div class="col-12 col-sm-6 col-md-auto flex-fill">
          <label class="form-label text-light mb-1">Долгота</label>
          <input type="number" step="0.0001" class="form-control form-control-sm bg-secondary text-white border-0" name="lon" value="37.6176">
        </div>
        <div class="col-12 col-sm-6 col-md-auto">
          <label class="form-label text-light mb-1">Высота (м)</label>
          <input type="number" class="form-control form-control-sm bg-secondary text-white border-0" name="elevation" value="150" style="width:100px">
        </div>
        <div class="col-12 col-sm-6 col-md-auto">
          <label class="form-label text-light mb-1">Время</label>
          <input type="time" class="form-control form-control-sm bg-secondary text-white border-0" name="time" value="12:00">
        </div>
        <div class="col-12 col-sm-6 col-md-auto">
          <label class="form-label text-light mb-1">Дни</label>
          <input type="number" min="1" max="365" class="form-control form-control-sm bg-secondary text-white border-0" name="days" value="7" style="width:100px">
        </div>
        <div class="col-12 col-md-auto">
          <button class="btn btn-primary btn-sm px-4 shadow-sm" type="submit">
            <i class="bi bi-search me-1"></i> Показать
          </button>
        </div>
      </form>

      <div class="table-responsive rounded-3 overflow-hidden shadow-sm">
        <table class="table table-dark table-hover table-sm align-middle mb-0">
          <thead class="bg-primary text-white">
            <tr>
              <th class="ps-3">#</th>
              <th>Тело</th>
              <th>Событие</th>
              <th>Когда (UTC)</th>
              <th class="pe-3">Дополнительно</th>
            </tr>
          </thead>
          <tbody id="astroBody" class="bg-dark">
            <tr><td colspan="5" class="text-muted text-center py-4">данные не найдены</td></tr>
          </tbody>
        </table>
      </div>

      <details class="mt-4">
        <summary class="text-primary cursor-pointer fw-bold">Показать JSON</summary>
        <pre id="astroRaw" class="bg-black text-light rounded-3 p-3 small mt-2 overflow-auto" style="max-height: 400px; white-space: pre-wrap;"></pre>
      </details>
    </div>
  </div>
</div>

<style>
  body {
    background-color: #0f0f0f;
    color: #e0e0e0;
  }
  .fade-in {
    animation: fadeIn 0.8s ease-out;
  }
  .fade-in-delay-1 {
    animation-delay: 0.2s;
  }
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .card {
    background: linear-gradient(135deg, #1a1a1a, #2d2d2d) !important;
    border-radius: 16px !important;
  }
  .form-control:focus,
  .form-control-sm:focus {
    background-color: #444 !important;
    color: white !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
    border-color: #007bff !important;
  }
  .btn-primary {
    background: linear-gradient(to right, #007bff, #0056b3);
    border: none;
    transition: all 0.3s ease;
  }
  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
  }
  .table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.15) !important;
  }
  summary {
    list-style: none;
  }
  summary::before {
    content: '▶ ';
    display: inline-block;
    transition: transform 0.2s;
  }
  details[open] summary::before {
    transform: rotate(90deg);
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('astroForm');
  const body = document.getElementById('astroBody');
  const raw  = document.getElementById('astroRaw');

  function normalize(node){
    const name = node.name || node.body || node.object || node.target || '';
    const type = node.type || node.event_type || node.category || node.kind || '';
    const when = node.time || node.date || node.occursAt || node.peak || node.instant || '';
    const extra = node.magnitude || node.mag || node.altitude || node.note || '';
    return {name, type, when, extra};
  }

  function collect(root){
    const rows = [];
    (function dfs(x){
      if (!x || typeof x !== 'object') return;
      if (Array.isArray(x)) { x.forEach(dfs); return; }
      if ((x.type || x.event_type || x.category) && (x.name || x.body || x.object || x.target)) {
        rows.push(normalize(x));
      }
      Object.values(x).forEach(dfs);
    })(root);
    return rows;
  }

  async function load(q){
    body.innerHTML = '<tr><td colspan="5" class="text-center py-5"><span class="spinner-border spinner-border-sm text-primary"></span> <span class="ms-2 text-light">Ожидание...</span></td></tr>';
    const url = '/api/astro/events?' + new URLSearchParams(q).toString();
    try{
      const r  = await fetch(url);
      const js = await r.json();
      raw.textContent = JSON.stringify(js, null, 2);

      const rows = collect(js);
      if (!rows.length) {
        body.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-light">События не найдены</td></tr>';
        return;
      }
      body.innerHTML = rows.slice(0,200).map((r,i)=>`
        <tr>
          <td class="ps-3">${i+1}</td>
          <td>${r.name || '—'}</td>
          <td>${r.type || '—'}</td>
          <td><code class="bg-black px-2 py-1 rounded">${r.when || '—'}</code></td>
          <td class="pe-3">${r.extra || ''}</td>
        </tr>
      `).join('');
    }catch(e){
      body.innerHTML = '<tr><td colspan="5" class="text-danger text-center py-5">ошибка</td></tr>';
    }
  }

  form.addEventListener('submit', ev=>{
    ev.preventDefault();
    load(Object.fromEntries(new FormData(form).entries()));
  });

  load({lat: form.lat.value, lon: form.lon.value, elevation: form.elevation.value, time: form.time.value, days: form.days.value});
});
</script>
@endsection