@extends('layouts.app')

@section('content')
<div class="container pb-5">
  <div class="card bg-dark border-0 shadow rounded-3 mb-4">
    <div class="card-body p-4">
      <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
          <div class="text-center p-3">
            <div class="small text-primary mb-1">Скорость МКС</div>
            <div class="display-5 fw-bold text-white" id="issSpeed">
              {!! isset(($iss['payload'] ?? [])['velocity']) ? number_format($iss['payload']['velocity'], 0, '', ' ') : '<span class="placeholder col-8 bg-secondary"></span>' !!}
            </div>
            <div class="text-white fw-bold">км/ч</div>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="text-center p-3">
            <div class="small text-primary mb-1">Высота МКС</div>
            <div class="display-5 fw-bold text-white" id="issAlt">
              {!! isset(($iss['payload'] ?? [])['altitude']) ? number_format($iss['payload']['altitude'], 0, '', ' ') : '<span class="placeholder col-8 bg-secondary"></span>' !!}
            </div>
            <div class="text-white fw-bold">км</div>
          </div>
        </div>
      </div>

      <hr class="border-secondary my-4">

      <div class="mb-4">
        <h5 class="text-white mb-3 text-center">МКС — положение и траектория</h5>
        <div id="map" class="rounded-3 bg-black border border-secondary mx-auto" style="height: 380px;"></div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
          <canvas id="issSpeedChart" height="150" class="bg-black rounded-3 p-3"></canvas>
        </div>
        <div class="col-12 col-md-6">
          <canvas id="issAltChart" height="150" class="bg-black rounded-3 p-3"></canvas>
        </div>
      </div>

      <hr class="border-secondary my-4">

      <div class="text-center">
        <h5 class="text-white mb-4">JWST — выбранное наблюдение</h5>
        <div id="jwstPreview" class="bg-black rounded-3 d-flex align-items-center justify-content-center overflow-hidden mx-auto position-relative" style="height: 600px; max-width: 1000px;">
          <div class="text-secondary fs-5">Выберите любое изображение ниже</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card bg-dark border-0 shadow rounded-3">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="text-white m-0">JWST — последние изображения</h5>
        <form id="jwstFilterForm" class="row g-3 align-items-center">
          <div class="col-auto">
            <select class="form-select form-select-sm bg-secondary text-white border-secondary" name="source" id="filterSource">
              <option value="jpg" selected>Все JPG</option>
              <option value="suffix">По суффиксу</option>
              <option value="program">По программе</option>
            </select>
          </div>
          <div class="col-auto">
            <input type="text" class="form-control form-control-sm bg-secondary text-white border-secondary" name="suffix" id="suffixField" placeholder="_cal / _thumb" style="width:160px; display:none;">
            <input type="text" class="form-control form-control-sm bg-secondary text-white border-secondary" name="program" id="programField" placeholder="Программа ID" style="width:140px; display:none;">
          </div>
          <div class="col-auto">
            <select class="form-select form-select-sm bg-secondary text-white border-secondary" name="instrument">
              <option value="">Любой инструмент</option>
              <option>NIRCam</option><option>MIRI</option><option>NIRISS</option><option>NIRSpec</option><option>FGS</option>
            </select>
          </div>
          <div class="col-auto">
            <select class="form-select form-select-sm bg-secondary text-white border-secondary" name="perPage">
              <option>12</option><option selected>24</option><option>36</option><option>48</option>
            </select>
          </div>
          <div class="col-auto">
            <button class="btn btn-primary btn-sm px-4" type="submit">Показать</button>
          </div>
        </form>
      </div>

      <div class="position-relative">
        <button class="btn btn-outline-light btn-lg position-absolute top-50 start-0 translate-middle-y z-3 shadow" style="left: 15px;">‹</button>
        <button class="btn btn-outline-light btn-lg position-absolute top-50 end-0 translate-middle-y z-3 shadow" style="right: 15px;">›</button>

        <div id="galleryTrack" class="d-flex gap-4 overflow-auto pb-3 px-5" style="scrollbar-width: none; -ms-overflow-style: none;">
          <style>#galleryTrack::-webkit-scrollbar { display: none; }</style>
        </div>
      </div>

      <div id="galleryInfo" class="text-white text-center mt-3 fw-bold"></div>
    </div>
  </div>
</div>

<style>
  body { background: #0d0d0d; color: #e0e0e0; }
  .card { background: #1a1a1a; }
  .form-control, .form-select {
    background-color: #2a2a2a !important;
    border-color: #444 !important;
    color: white !important;
  }
  .form-control:focus, .form-select:focus {
    background-color: #333 !important;
    border-color: #007bff !important;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
  }

  .jwst-thumb {
    flex: 0 0 240px;
    cursor: pointer;
    transition: transform 0.2s ease;
  }
  .jwst-thumb:hover { transform: scale(1.06); }
  .jwst-thumb img {
    width: 100%; height: 240px; object-fit: cover; border-radius: 12px; box-shadow: 0 6px 16px rgba(0,0,0,0.6);
  }
  .jwst-thumb-cap {
    font-size: 0.9rem; margin-top: 0.8rem; color: #ccc; text-align: center;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', async function () {
  // МКС: карта и графики
  if (typeof L !== 'undefined' && typeof Chart !== 'undefined') {
    const initialData = @json(($iss['payload'] ?? []));
    let initLat = Number(initialData.latitude || 0);
    let initLon = Number(initialData.longitude || 0);

    const issMap = L.map('map').setView([initLat || 0, initLon || 0], initLat ? 3 : 2);
    L.tileLayer('https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png', { noWrap: true }).addTo(issMap);
    const pathLine = L.polyline([], { color: '#00ccff', weight: 4 }).addTo(issMap);
    const issMarker = L.marker([initLat || 0, initLon || 0]).addTo(issMap);

    const chartColor = '#007bff';
    const pointStyle = {
      pointRadius: 5,
      pointHoverRadius: 7,
      pointBackgroundColor: '#ffffff',
      pointBorderColor: chartColor,
      pointBorderWidth: 2
    };

    const speedGraph = new Chart(document.getElementById('issSpeedChart'), {
      type: 'line',
      data: { labels: [], datasets: [{ data: [], borderColor: chartColor, tension: 0.3, ...pointStyle }] },
      options: { responsive: true, scales: { x: { display: false } }, plugins: { legend: { display: false } } }
    });
    const altGraph = new Chart(document.getElementById('issAltChart'), {
      type: 'line',
      data: { labels: [], datasets: [{ data: [], borderColor: chartColor, tension: 0.3, borderDash: [5, 5], ...pointStyle }] },
      options: { responsive: true, scales: { x: { display: false } }, plugins: { legend: { display: false } } }
    });

    async function updateIssData() {
      try {
        const response = await fetch('/api/iss/last');
        const result = await response.json();
        const payload = result.payload || {};

        if (payload.latitude && payload.longitude) {
          const newPosition = [payload.latitude, payload.longitude];
          pathLine.addLatLng(newPosition);
          if (pathLine.getLatLngs().length > 240) pathLine.getLatLngs().shift();
          issMarker.setLatLng(newPosition);
          issMap.panTo(newPosition);
        }

        document.getElementById('issSpeed').textContent = payload.velocity ? Math.round(payload.velocity).toLocaleString() : '—';
        document.getElementById('issAlt').textContent = payload.altitude ? Math.round(payload.altitude).toLocaleString() : '—';

        const currentTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        speedGraph.data.labels.push(currentTime);
        speedGraph.data.datasets[0].data.push(payload.velocity || 0);
        altGraph.data.labels.push(currentTime);
        altGraph.data.datasets[0].data.push(payload.altitude || 0);

        if (speedGraph.data.labels.length > 20) {
          speedGraph.data.labels.shift();
          speedGraph.data.datasets[0].data.shift();
          altGraph.data.labels.shift();
          altGraph.data.datasets[0].data.shift();
        }
        speedGraph.update();
        altGraph.update();
      } catch (e) { console.error(e); }
    }
    updateIssData();
    setInterval(updateIssData, {{ $issEverySeconds ?? 120 }} * 1000);
  }

  // JWST
  const gallery = document.getElementById('galleryTrack');
  const infoLine = document.getElementById('galleryInfo');
  const filterForm = document.getElementById('jwstFilterForm');
  const sourceSelect = document.getElementById('filterSource');
  const suffixInput = document.getElementById('suffixField');
  const programInput = document.getElementById('programField');
  const previewBlock = document.getElementById('jwstPreview');

  function displayPreview(photo) {
    previewBlock.innerHTML = `
      <div class="h-100 w-100 d-flex flex-column justify-content-center align-items-center px-4">
        <img src="${photo.url}" class="rounded-3 shadow-lg" style="height: 400px; width: auto; object-fit: cover; max-width: 90%;" alt="JWST">
        <p class="mt-4 fs-5 text-white text-center">${photo.caption || 'Без названия'}</p>
        <a href="${photo.link || photo.url}" target="_blank" class="btn btn-outline-primary mt-3">Открыть оригинал ↗</a>
      </div>`;
  }

  function toggleFilterFields() {
    suffixInput.style.display = sourceSelect.value === 'suffix' ? 'block' : 'none';
    programInput.style.display = sourceSelect.value === 'program' ? 'block' : 'none';
  }
  sourceSelect.addEventListener('change', toggleFilterFields);
  toggleFilterFields();

  async function refreshGallery(params) {
    gallery.innerHTML = '<div class="text-center py-5 text-white"><span class="spinner-border text-primary"></span><span class="ms-3">Загрузка...</span></div>';
    infoLine.textContent = '';
    try {
      const requestUrl = '/api/jwst/feed?' + new URLSearchParams(params);
      const resp = await fetch(requestUrl);
      const galleryData = await resp.json();

      gallery.innerHTML = '';
      (galleryData.items || []).forEach(pic => {
        const thumb = document.createElement('div');
        thumb.className = 'jwst-thumb';
        thumb.innerHTML = `
          <img loading="lazy" src="${pic.url}" class="rounded-3 shadow" alt="JWST">
          <div class="jwst-thumb-cap">${(pic.caption || '').replace(/</g, '&lt;')}</div>`;
        thumb.onclick = () => displayPreview(pic);
        gallery.appendChild(thumb);
      });

      infoLine.textContent = `Источник: ${galleryData.source} · Показано: ${galleryData.count || 0}`;
    } catch (e) {
      gallery.innerHTML = '<div class="text-danger text-center py-5">Ошибка загрузки</div>';
    }
  }

  filterForm.addEventListener('submit', e => {
    e.preventDefault();
    refreshGallery(Object.fromEntries(new FormData(filterForm)));
  });

  document.querySelectorAll('.btn-outline-light')[0].onclick = () => gallery.scrollBy({ left: -700, behavior: 'smooth' });
  document.querySelectorAll('.btn-outline-light')[1].onclick = () => gallery.scrollBy({ left: 700, behavior: 'smooth' });

  refreshGallery({ source: 'jpg', perPage: 24 });
});
</script>
@endsection