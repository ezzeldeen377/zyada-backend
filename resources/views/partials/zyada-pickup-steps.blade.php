<section class="zyada-pickup-section">
    <div class="container">
        <div class="section-header text-start">
            <h2 class="title">{{ __('landing.pickup_title') }}</h2>
            <p>{{ __('landing.pickup_intro') }}</p>
        </div>
        <div class="row g-4">
            @foreach (['discover', 'reserve', 'collect'] as $step)
                <div class="col-md-4">
                    <article class="zyada-pickup-step">
                        <span aria-hidden="true">0{{ $loop->iteration }} /</span>
                        <h3>{{ __('landing.' . $step) }}</h3>
                        <p>{{ __('landing.' . $step . '_description') }}</p>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>
