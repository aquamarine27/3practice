@extends('layouts.app')

@section('content')
<div class="container py-5">
  <h2 class="text-white text-center mb-5 fade-in">CMS — блоки контента</h2>

  <div class="row g-4 justify-content-center">
    <div class="col-lg-8">
      <div class="card bg-dark border-0 shadow-lg rounded-3 fade-in fade-in-delay-1">
        <div class="card-header bg-primary text-white fw-bold py-3">
          Welcome
        </div>
        <div class="card-body p-4">
          @if($cmsWelcome)
            <div class="cms-content">
              {!! $cmsWelcome !!}
            </div>
          @else
            <p class="text-muted text-center">Блок не найден</p>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card bg-dark border-0 shadow-lg rounded-3 fade-in fade-in-delay-2">
        <div class="card-header bg-danger text-white fw-bold py-3">
          Unsafe
        </div>
        <div class="card-body p-4">
          @if($cmsUnsafe)
            <div class="cms-content">
              {!! $cmsUnsafe !!}
            </div>
          @else
            <p class="text-muted text-center">Блок не найден</p>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .cms-content {
    line-height: 1.7;
    font-size: 1.1rem;
  }
  .cms-content p {
    margin-bottom: 1rem;
  }
  .cms-content h3, .cms-content h4 {
    color: #00ccff;
    margin-top: 1.5rem;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .fade-in {
    animation: fadeUp 0.8s ease-out both;
  }
  .fade-in-delay-1 { animation-delay: 0.2s; }
  .fade-in-delay-2 { animation-delay: 0.4s; }
</style>
@endsection