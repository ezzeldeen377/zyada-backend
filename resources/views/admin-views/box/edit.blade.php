@extends('layouts.admin.app')

@section('title', translate('messages.update_mystery_box'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/items.png') }}" class="w--20" alt="">
                </span>
                <span>
                    {{ translate('messages.update_mystery_box') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.box.update', [$box->id]) }}" method="post" enctype="multipart/form-data" id="box_form">
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
                                @php($translations = [])
                                @foreach($box->translations as $t)
                                    @if($t->key == 'name')
                                        @php($translations[$t->locale]['name'] = $t->value)
                                    @endif
                                    @if($t->key == 'description')
                                        @php($translations[$t->locale]['description'] = $t->value)
                                    @endif
                                @endforeach

                                <div class="form-group lang_form" id="default-form">
                                    <label class="input-label" for="name">{{ translate('messages.name') }} ({{ translate('messages.default') }})</label>
                                    <input type="text" name="name[]" class="form-control" value="{{ $box->getRawOriginal('name') }}" placeholder="{{ translate('messages.name') }}" required>
                                </div>
                                <input type="hidden" name="lang[]" value="default">
                                @foreach (json_decode($language) as $lang)
                                    <div class="form-group d-none lang_form" id="{{ $lang }}-form">
                                        <label class="input-label" for="name">{{ translate('messages.name') }} ({{ strtoupper($lang) }})</label>
                                        <input type="text" name="name[]" class="form-control" value="{{ $translations[$lang]['name'] ?? '' }}" placeholder="{{ translate('messages.name') }}">
                                    </div>
                                    <input type="hidden" name="lang[]" value="{{ $lang }}">
                                @endforeach
                            @endif

                            <div class="form-group">
                                <label class="input-label" for="store_id">{{ translate('messages.store') }}</label>
                                <select name="store_id" id="store_id" class="form-control js-select2-custom" required>
                                    @if($box->store)
                                        <option value="{{ $box->store_id }}" selected>{{ $box->store->name }}</option>
                                    @endif
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label" for="available_count">{{ translate('messages.available_count') }}</label>
                                        <input type="number" name="available_count" class="form-control" value="{{ $box->available_count }}" placeholder="{{ translate('messages.available_count') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label" for="item_count">{{ translate('messages.item_count') }}</label>
                                        <input type="number" name="item_count" class="form-control" value="{{ $box->item_count }}" placeholder="{{ translate('messages.item_count') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label" for="price">{{ translate('messages.price') }}</label>
                                        <input type="number" step="0.01" name="price" class="form-control" value="{{ $box->price }}" placeholder="{{ translate('messages.price') }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <center>
                                    <img class="img--176" id="viewer" src="{{ $box->image_full_url }}" data-onerror-image="{{ asset('public/assets/admin/img/upload-img.png') }}" alt="image" />
                                </center>
                                <label class="input-label">{{ translate('messages.image') }} <small class="text-danger">( {{ translate('messages.ratio') }} 1:1 )</small></label>
                                <div class="custom-file">
                                    <input type="file" name="image" id="customFileEg1" class="custom-file-input" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                    <label class="custom-file-label" for="customFileEg1">{{ translate('messages.choose_file') }}</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label" for="start_date">{{ translate('messages.start_date') }}</label>
                                        <input type="date" name="start_date" value="{{ $box->start_date?->format('Y-m-d') }}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label" for="end_date">{{ translate('messages.end_date') }}</label>
                                        <input type="date" name="end_date" value="{{ $box->end_date?->format('Y-m-d') }}" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            @if ($language)
                                <div class="form-group lang_form" id="default-form-desc">
                                    <label class="input-label">{{ translate('messages.description') }} ({{ translate('messages.default') }})</label>
                                    <textarea name="description[]" class="form-control">{{ $box->getRawOriginal('description') }}</textarea>
                                </div>
                                @foreach (json_decode($language) as $lang)
                                    <div class="form-group d-none lang_form" id="{{ $lang }}-form-desc">
                                        <label class="input-label">{{ translate('messages.description') }} ({{ strtoupper($lang) }})</label>
                                        <textarea name="description[]" class="form-control">{{ $translations[$lang]['description'] ?? '' }}</textarea>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="btn--container justify-content-end mt-3">
                        <button type="reset" id="reset_btn" class="btn btn--reset">{{ translate('messages.reset') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('messages.update') }}</button>
                    </div>
                </form>
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
                            module_id: {{ $box->module_id }}
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
                url: '{{ route('admin.box.update', [$box->id]) }}',
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
                            location.href = '{{ route('admin.box.add-new') }}';
                        }, 2000);
                    }
                }
            });
        });
    </script>
@endpush
