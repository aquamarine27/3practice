<div class="card h-100 shadow-sm border-0 hover-lift">
    <div class="card-body text-center py-5">
        {!! $slot !!}
    </div>
</div>

<style>
.hover-lift { transition: all 0.3s ease; }
.hover-lift:hover { transform: translateY(-12px); box-shadow: 0 25px 50px rgba(0,0,0,0.15)!important; }
</style>