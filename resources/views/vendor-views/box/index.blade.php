@extends('layouts.vendor.app')

@section('title', translate('messages.mystery_box'))

@section('content')
    @php($language = getWebConfig('language'))
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><img src="{{ asset('public/assets/admin/img/items.png') }}" class="w--20" alt=""></span>
                <span>{{ translate('messages.mystery_box') }}</span>
            </h1>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('vendor.box.store') }}" method="post" enctype="multipart/form-data" id="box_form">
                    @csrf
                    @if ($language)
                        <ul class="nav nav-tabs mb-4">
                            <li class="nav-item"><a class="nav-link lang_link active" href="#" id="default-link">{{ translate('messages.default') }}</a></li>
                            @foreach ($language as $lang)
                                <li class="nav-item"><a class="nav-link lang_link" href="#" id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) }} ({{ strtoupper($lang) }})</a></li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="lang_form" id="default-form">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('messages.name') }} ({{ translate('messages.default') }})</label>
                                    <input type="text" name="name[]" class="form-control" value="{{ old('name.0') }}" required>
                                </div>
                                <div class="form-group">
                                    <label class="input-label">{{ translate('messages.description') }} <span class="text-danger">*</span></label>
                                    <textarea name="description[]" class="form-control" required>{{ old('description.0') }}</textarea>
                                </div>
                            </div>
                            <input type="hidden" name="lang[]" value="default">
                            @foreach ($language ?? [] as $index => $lang)
                                <div class="d-none lang_form" id="{{ $lang }}-form">
                                    <div class="form-group">
                                        <label class="input-label">{{ translate('messages.name') }} ({{ strtoupper($lang) }})</label>
                                        <input type="text" name="name[]" class="form-control" value="{{ old('name.' . ($index + 1)) }}">
                                    </div>
                                    <div class="form-group">
                                        <label class="input-label">{{ translate('messages.description') }} ({{ strtoupper($lang) }})</label>
                                        <textarea name="description[]" class="form-control">{{ old('description.' . ($index + 1)) }}</textarea>
                                    </div>
                                </div>
                                <input type="hidden" name="lang[]" value="{{ $lang }}">
                            @endforeach

                            <div class="row">
                                <div class="col-md-4"><div class="form-group"><label class="input-label">{{ translate('messages.available_count') }}</label><input type="number" min="0" name="available_count" class="form-control" value="{{ old('available_count') }}" required></div></div>
                                <div class="col-md-4"><div class="form-group"><label class="input-label">{{ translate('messages.item_count') }}</label><input type="number" min="1" name="item_count" class="form-control" value="{{ old('item_count') }}" required></div></div>
                                <div class="col-md-4"><div class="form-group"><label class="input-label">{{ translate('messages.price') }}</label><input type="number" min="0" step="0.01" name="price" class="form-control" value="{{ old('price') }}" required></div></div>
                            </div>
                            <div class="form-group">
                                <label class="input-label">{{ translate('messages.category') }}</label>
                                <select name="category_id" class="form-control js-select2-custom" required>
                                    <option value="">{{ translate('messages.select_category') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6"><div class="form-group"><label class="input-label">{{ translate('messages.discount_type') }}</label><select name="discount_type" class="form-control"><option value="">{{ translate('messages.no_discount') }}</option><option value="percent" @selected(old('discount_type') === 'percent')>{{ translate('messages.percent') }}</option><option value="amount" @selected(old('discount_type') === 'amount')>{{ translate('messages.amount') }}</option></select></div></div>
                                <div class="col-md-6"><div class="form-group"><label class="input-label">{{ translate('messages.discount_amount') }}</label><input type="number" min="0" step="0.01" name="discount_amount" class="form-control" value="{{ old('discount_amount', 0) }}"></div></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <center><img class="img--176" id="viewer" src="{{ asset('public/assets/admin/img/upload-img.png') }}" alt="{{ translate('messages.image') }}"></center>
                                <label class="input-label">{{ translate('messages.image') }} <small class="text-danger">* ({{ translate('messages.ratio') }} 1:1)</small></label>
                                <div class="custom-file"><input type="file" name="image" id="customFileEg1" class="custom-file-input" accept="image/*" required><label class="custom-file-label" for="customFileEg1">{{ translate('messages.choose_file') }}</label></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6"><div class="form-group"><label class="input-label">{{ translate('messages.start_date') }}</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}"></div></div>
                                <div class="col-md-6"><div class="form-group"><label class="input-label">{{ translate('messages.end_date') }}</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}"></div></div>
                                <div class="col-md-6"><div class="form-group"><label class="input-label">{{ translate('messages.pickup_time_from') }}</label><input type="time" name="pickup_time_from" class="form-control" value="{{ old('pickup_time_from') }}"></div></div>
                                <div class="col-md-6"><div class="form-group"><label class="input-label">{{ translate('messages.pickup_time_to') }}</label><input type="time" name="pickup_time_to" class="form-control" value="{{ old('pickup_time_to') }}"></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="btn--container justify-content-end"><button type="reset" class="btn btn--reset">{{ translate('messages.reset') }}</button><button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button></div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{ translate('messages.box_list') }} <span class="badge badge-soft-dark ml-2">{{ $boxes->total() }}</span></h5>
                    <form method="get" action="{{ route('vendor.box.add-new') }}"><div class="input-group input--group"><input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ translate('messages.search_boxes') }}"><button type="submit" class="btn btn--primary"><i class="tio-search"></i></button></div></form>
                </div>
            </div>
            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light"><tr><th>{{ translate('messages.sl') }}</th><th>{{ translate('messages.image') }}</th><th>{{ translate('messages.name') }}</th><th>{{ translate('messages.price') }}</th><th>{{ translate('messages.available') }}</th><th>{{ translate('messages.item_count') }}</th><th>{{ translate('messages.status') }}</th><th class="text-center">{{ translate('messages.action') }}</th></tr></thead>
                    <tbody>
                        @forelse ($boxes as $key => $box)
                            <tr>
                                <td>{{ $key + $boxes->firstItem() }}</td><td><img class="img--60" src="{{ $box->image_full_url }}" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" alt="{{ $box->name }}"></td><td>{{ $box->name }}</td><td>{{ \App\CentralLogics\Helpers::format_currency($box->discounted_price) }}</td><td>{{ $box->available_count }}</td><td>{{ $box->item_count }}</td>
                                <td><label class="toggle-switch toggle-switch-sm" for="statusCheckbox{{ $box->id }}"><input type="checkbox" data-url="{{ route('vendor.box.status', [$box->id, $box->status ? 0 : 1]) }}" class="toggle-switch-input redirect-url" id="statusCheckbox{{ $box->id }}" @checked($box->status)><span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span></label></td>
                                <td><div class="btn--container justify-content-center"><a class="btn btn-sm btn--primary btn-outline-primary action-btn" href="{{ route('vendor.box.edit', $box->id) }}" title="{{ translate('messages.edit') }}"><i class="tio-edit"></i></a><a class="btn btn-sm btn--danger btn-outline-danger action-btn form-alert" href="javascript:" data-id="box-{{ $box->id }}" data-message="{{ translate('messages.Want_to_delete_this_box') }}" title="{{ translate('messages.delete') }}"><i class="tio-delete-outlined"></i></a><form action="{{ route('vendor.box.delete', $box->id) }}" method="post" id="box-{{ $box->id }}">@csrf @method('delete')</form></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center">{{ translate('messages.no_data_found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($boxes->count())<div class="page-area">{{ $boxes->links() }}</div>@endif
        </div>
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
