@extends('layouts.admin.app')

@section('title', translate('messages.mystery_box'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/items.png') }}" class="w--20" alt="">
                </span>
                <span>
                    {{ translate('messages.mystery_box') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->

        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.box.store') }}" method="post" enctype="multipart/form-data" id="box_form">
                    @csrf
                    @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
                    @php($language = $language->value ?? null)
                    @php($default_lang = str_replace('_', '-', app()->getLocale()))

                    @if ($language)
                        <ul class="nav nav-tabs mb-4">
                            <li class="nav-item">
                                <a class="nav-link lang_link active" href="#"
                                   id="default-link">{{ translate('messages.default') }}</a>
                            </li>
                            @foreach (json_decode($language) as $lang)
                                <li class="nav-item">
                                    <a class="nav-link lang_link" href="#"
                                       id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            @if ($language)
                                <div class="form-group lang_form" id="default-form">
                                    <label class="input-label" for="name">{{ translate('messages.name') }} ({{ translate('messages.default') }})</label>
                                    <input type="text" name="name[]" class="form-control" placeholder="{{ translate('messages.name') }}" required>
                                </div>
                                <input type="hidden" name="lang[]" value="default">
                                @foreach (json_decode($language) as $lang)
                                    <div class="form-group d-none lang_form" id="{{ $lang }}-form">
                                        <label class="input-label" for="name">{{ translate('messages.name') }} ({{ strtoupper($lang) }})</label>
                                        <input type="text" name="name[]" class="form-control" placeholder="{{ translate('messages.name') }}">
                                    </div>
                                    <input type="hidden" name="lang[]" value="{{ $lang }}">
                                @endforeach
                            @endif

                            <div class="form-group">
                                <label class="input-label" for="store_id">{{ translate('messages.store') }}</label>
                                <select name="store_id" id="store_id" class="form-control js-select2-custom" required></select>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label" for="available_count">{{ translate('messages.available_count') }}</label>
                                        <input type="number" name="available_count" class="form-control" placeholder="{{ translate('messages.available_count') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label" for="item_count">{{ translate('messages.item_count') }}</label>
                                        <input type="number" name="item_count" class="form-control" placeholder="{{ translate('messages.item_count') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label" for="price">{{ translate('messages.price') }}</label>
                                        <input type="number" step="0.01" name="price" class="form-control" placeholder="{{ translate('messages.price') }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <center>
                                    <img class="img--176" id="viewer" src="{{ asset('public/assets/admin/img/upload-img.png') }}" alt="image" />
                                </center>
                                <label class="input-label">{{ translate('messages.image') }} <small class="text-danger">* ( {{ translate('messages.ratio') }} 1:1 )</small></label>
                                <div class="custom-file">
                                    <input type="file" name="image" id="customFileEg1" class="custom-file-input" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required>
                                    <label class="custom-file-label" for="customFileEg1">{{ translate('messages.choose_file') }}</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label" for="start_date">{{ translate('messages.start_date') }}</label>
                                        <input type="date" name="start_date" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label" for="end_date">{{ translate('messages.end_date') }}</label>
                                        <input type="date" name="end_date" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            @if ($language)
                                <div class="form-group lang_form" id="default-form-desc">
                                    <label class="input-label">{{ translate('messages.description') }} ({{ translate('messages.default') }})</label>
                                    <textarea name="description[]" class="form-control"></textarea>
                                </div>
                                @foreach (json_decode($language) as $lang)
                                    <div class="form-group d-none lang_form" id="{{ $lang }}-form-desc">
                                        <label class="input-label">{{ translate('messages.description') }} ({{ strtoupper($lang) }})</label>
                                        <textarea name="description[]" class="form-control"></textarea>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="btn--container justify-content-end">
                        <button type="reset" id="reset_btn" class="btn btn--reset">{{ translate('messages.reset') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{ translate('messages.box_list') }}<span class="badge badge-soft-dark ml-2" id="itemCount">{{ $boxes->total() }}</span></h5>
                    <form action="javascript:" id="search-form">
                        @csrf
                        <div class="input-group input--group">
                            <input id="datatableSearch" type="search" name="search" class="form-control" placeholder="{{ translate('messages.search_boxes') }}" aria-label="{{ translate('messages.search_boxes') }}">
                            <button type="submit" class="btn btn--primary"><i class="tio-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-responsive datatable-custom">
                <table id="columnSearchDatatable" class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                       data-hs-datatables-options='{"order": [], "search": "#datatableSearch", "isResponsive": false, "isShowPaging": false, "paging":false}'>
                    <thead class="thead-light">
                    <tr>
                        <th>{{ translate('messages.sl') }}</th>
                        <th>{{ translate('messages.image') }}</th>
                        <th>{{ translate('messages.name') }}</th>
                        <th>{{ translate('messages.store') }}</th>
                        <th>{{ translate('messages.price') }}</th>
                        <th>{{ translate('messages.available') }}</th>
                        <th>{{ translate('messages.item_count') }}</th>
                        <th>{{ translate('messages.status') }}</th>
                        <th class="text-center">{{ translate('messages.action') }}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach ($boxes as $key => $box)
                        <tr>
                            <td>{{ $key + $boxes->firstItem() }}</td>
                            <td>
                                <img class="img--60" src="{{ $box->image_full_url }}" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" alt="{{ $box->name }}">
                            </td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{ $box->name }}
                                </span>
                            </td>
                            <td>{{ $box->store ? $box->store->name : translate('messages.store_deleted') }}</td>
                            <td>{{ \App\CentralLogics\Helpers::format_currency($box->price) }}</td>
                            <td>{{ $box->available_count }}</td>
                            <td>{{ $box->item_count }}</td>
                            <td>
                                <label class="toggle-switch toggle-switch-sm" for="statusCheckbox{{ $box->id }}">
                                    <input type="checkbox" data-url="{{ route('admin.box.status', [$box['id'], $box->status ? 0 : 1]) }}" class="toggle-switch-input redirect-url" id="statusCheckbox{{ $box->id }}" {{ $box->status ? 'checked' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="btn btn-sm btn--primary btn-outline-primary action-btn" href="{{ route('admin.box.edit', [$box['id']]) }}" title="{{ translate('messages.edit') }}"><i class="tio-edit"></i></a>
                                    <a class="btn btn-sm btn--danger btn-outline-danger action-btn" href="javascript:" onclick="form_alert('box-{{ $box['id'] }}','{{ translate('messages.Want_to_delete_this_box') }}')" title="{{ translate('messages.delete') }}"><i class="tio-delete-outlined"></i></a>
                                    <form action="{{ route('admin.box.delete', ['id' => $box['id']]) }}" method="post" id="box-{{ $box['id'] }}">
                                        @csrf @method('delete')
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if (count($boxes) === 0)
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif
            <div class="card-footer">
                {!! $boxes->links() !!}
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        $(document).on('ready', function () {
            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });

            $('#store_id').select2({
                ajax: {
                    url: '{{ url('/') }}/admin/store/get-stores',
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page,
                            module_id: {{ Config::get('module.current_module_id') }}
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    __port: function (params, success, failure) {
                        var $request = $.ajax(params);
                        $request.then(success);
                        $request.fail(failure);
                        return $request;
                    }
                }
            });
        });

        $(".lang_link").click(function (e) {
            e.preventDefault();
            $(".lang_link").removeClass('active');
            $(".lang_form").addClass('d-none');
            $(this).addClass('active');

            let id = $(this).attr('id');
            let lang = id.split('-')[0];
            if (lang == 'default') {
                $("#default-form").removeClass('d-none');
                $("#default-form-desc").removeClass('d-none');
            } else {
                $("#" + lang + "-form").removeClass('d-none');
                $("#" + lang + "-form-desc").removeClass('d-none');
            }
        });

        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#viewer').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function () {
            readURL(this);
        });

        $('#box_form').on('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.box.store') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    if (data.errors) {
                        for (var i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else {
                        toastr.success(data.success, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function () {
                            location.reload();
                        }, 2000);
                    }
                }
            });
        });

        $('#search-form').on('submit', function () {
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.box.search') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    $('#set-rows').html(data.view);
                    $('.card-footer').hide();
                    $('#itemCount').html(data.count);
                },
                complete: function () {
                    $('#loading').hide();
                },
            });
        });
    </script>
@endpush
