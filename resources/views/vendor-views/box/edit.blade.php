@extends('layouts.vendor.app')

@section('title', translate('messages.update_mystery_box'))

@section('content')
    @php($language = getWebConfig('language'))
    @php($translations = $box->translations->groupBy('locale'))
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title"><span class="page-header-icon"><img src="{{ asset('public/assets/admin/img/items.png') }}" class="w--20" alt=""></span><span>{{ translate('messages.update_mystery_box') }}</span></h1>
        </div>
        <div class="card"><div class="card-body">
            <form action="{{ route('vendor.box.update', $box->id) }}" method="post" enctype="multipart/form-data" id="box_form">
                @csrf
                @if ($language)
                    <ul class="nav nav-tabs mb-4"><li class="nav-item"><a class="nav-link lang_link active" href="#" id="default-link">{{ translate('messages.default') }}</a></li>@foreach ($language as $lang)<li class="nav-item"><a class="nav-link lang_link" href="#" id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) }} ({{ strtoupper($lang) }})</a></li>@endforeach</ul>
                @endif
                <div class="row">
                    <div class="col-md-6">
                        <div class="lang_form" id="default-form">
                            <div class="form-group"><label class="input-label">{{ translate('messages.name') }} ({{ translate('messages.default') }})</label><input type="text" name="name[]" class="form-control" value="{{ old('name.0', $box->getRawOriginal('name')) }}" required></div>
                            <div class="form-group"><label class="input-label">{{ translate('messages.description') }} <span class="text-danger">*</span></label><textarea name="description[]" class="form-control" required>{{ old('description.0', $box->getRawOriginal('description')) }}</textarea></div>
                        </div>
                        <input type="hidden" name="lang[]" value="default">
                        @foreach ($language ?? [] as $index => $lang)
                            @php($localizedName = optional($translations->get($lang)?->firstWhere('key', 'name'))->value)
                            @php($localizedDescription = optional($translations->get($lang)?->firstWhere('key', 'description'))->value)
                            <div class="d-none lang_form" id="{{ $lang }}-form">
                                <div class="form-group"><label class="input-label">{{ translate('messages.name') }} ({{ strtoupper($lang) }})</label><input type="text" name="name[]" class="form-control" value="{{ old('name.' . ($index + 1), $localizedName) }}"></div>
                                <div class="form-group"><label class="input-label">{{ translate('messages.description') }} ({{ strtoupper($lang) }})</label><textarea name="description[]" class="form-control">{{ old('description.' . ($index + 1), $localizedDescription) }}</textarea></div>
                            </div>
                            <input type="hidden" name="lang[]" value="{{ $lang }}">
                        @endforeach
                        <div class="row">
                            <div class="col-md-4"><div class="form-group"><label class="input-label">{{ translate('messages.available_count') }}</label><input type="number" min="0" name="available_count" class="form-control" value="{{ old('available_count', $box->available_count) }}" required></div></div>
                            <div class="col-md-4"><div class="form-group"><label class="input-label">{{ translate('messages.item_count') }}</label><input type="number" min="1" name="item_count" class="form-control" value="{{ old('item_count', $box->item_count) }}" required></div></div>
                            <div class="col-md-4"><div class="form-group"><label class="input-label">{{ translate('messages.price') }}</label><input type="number" min="0" step="0.01" name="price" class="form-control" value="{{ old('price', $box->price) }}" required></div></div>
                        </div>
                        <div class="form-group"><label class="input-label">{{ translate('messages.category') }}</label><select name="category_id" class="form-control js-select2-custom" required><option value="">{{ translate('messages.select_category') }}</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $box->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                        <div class="row"><div class="col-md-6"><div class="form-group"><label class="input-label">{{ translate('messages.discount_type') }}</label><select name="discount_type" class="form-control"><option value="">{{ translate('messages.no_discount') }}</option><option value="percent" @selected(old('discount_type', $box->discount_type) === 'percent')>{{ translate('messages.percent') }}</option><option value="amount" @selected(old('discount_type', $box->discount_type) === 'amount')>{{ translate('messages.amount') }}</option></select></div></div><div class="col-md-6"><div class="form-group"><label class="input-label">{{ translate('messages.discount_amount') }}</label><input type="number" min="0" step="0.01" name="discount_amount" class="form-control" value="{{ old('discount_amount', $box->discount_amount) }}"></div></div></div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group"><center><img class="img--176" id="viewer" src="{{ $box->image_full_url }}" data-onerror-image="{{ asset('public/assets/admin/img/upload-img.png') }}" alt="{{ translate('messages.image') }}"></center><label class="input-label">{{ translate('messages.image') }} <small>({{ translate('messages.ratio') }} 1:1)</small></label><div class="custom-file"><input type="file" name="image" id="customFileEg1" class="custom-file-input" accept="image/*"><label class="custom-file-label" for="customFileEg1">{{ translate('messages.choose_file') }}</label></div></div>
                        <div class="row"><div class="col-md-6"><div class="form-group"><label class="input-label">{{ translate('messages.start_date') }}</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date', $box->start_date?->format('Y-m-d')) }}"></div></div><div class="col-md-6"><div class="form-group"><label class="input-label">{{ translate('messages.end_date') }}</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date', $box->end_date?->format('Y-m-d')) }}"></div></div><div class="col-md-6"><div class="form-group"><label class="input-label">{{ translate('messages.pickup_time_from') }}</label><input type="time" name="pickup_time_from" class="form-control" value="{{ old('pickup_time_from', $box->pickup_time_from) }}"></div></div><div class="col-md-6"><div class="form-group"><label class="input-label">{{ translate('messages.pickup_time_to') }}</label><input type="time" name="pickup_time_to" class="form-control" value="{{ old('pickup_time_to', $box->pickup_time_to) }}"></div></div></div>
                    </div>
                </div>
                <div class="btn--container justify-content-end"><a class="btn btn--reset" href="{{ route('vendor.box.add-new') }}">{{ translate('messages.cancel') }}</a><button type="submit" class="btn btn--primary">{{ translate('messages.update') }}</button></div>
            </form>
        </div></div>
    </div>
@endsection

@push('script_2')
<script>
    'use strict';
    $('.js-select2-custom').each(function () { $.HSCore.components.HSSelect2.init($(this)); });
    $('.lang_link').on('click', function (event) { event.preventDefault(); $('.lang_link').removeClass('active'); $('.lang_form').addClass('d-none'); $(this).addClass('active'); $('#' + $(this).attr('id').split('-')[0] + '-form').removeClass('d-none'); });
    $('#customFileEg1').on('change', function () { if (this.files && this.files[0]) { const reader = new FileReader(); reader.onload = function (event) { $('#viewer').attr('src', event.target.result); }; reader.readAsDataURL(this.files[0]); } });
</script>
@endpush
