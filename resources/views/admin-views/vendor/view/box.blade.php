@extends('layouts.admin.app')

@section('title', $store->name . "'s " . translate('messages.mystery_box'))

@section('content')
    <div class="content container-fluid">
        @include('admin-views.vendor.view.partials._header', ['store' => $store])

        <div class="card">
            <div class="card-header border-0 py-2">
                <div class="search--button-wrapper">
                    <h3 class="card-title"> {{ translate('messages.mystery_box') }} <span
                            class="badge badge-soft-dark ml-2">{{ $boxes->total() }}</span>
                    </h3>

                    <form class="search-form">
                        <div class="input-group input--group">
                            <input id="datatableSearch" name="search" value="{{ request()?->search ?? null }}"
                                type="search" class="form-control h--40px"
                                placeholder="{{ translate('messages.search_boxes') }}"
                                aria-label="{{ translate('messages.search_here') }}">
                            <button type="submit" class="btn btn--secondary h--40px"><i
                                    class="tio-search"></i></button>
                        </div>
                    </form>

                    <button type="button" class="btn btn--primary pull-right" data-toggle="modal" data-target="#add-box-modal">
                        <i class="tio-add-circle"></i> {{ translate('messages.add_new_box') }}
                    </button>
                </div>
            </div>
            <div class="table-responsive datatable-custom">
                <table id="columnSearchDatatable"
                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                    data-hs-datatables-options='{
                        "order": [],
                        "orderCellsTop": true,
                        "paging": false
                    }'>
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('messages.sl') }}</th>
                            <th>{{ translate('messages.image') }}</th>
                            <th>{{ translate('messages.name') }}</th>
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
                                    <img class="img--60" src="{{ $box->image_full_url }}"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                        alt="{{ $box->name }}">
                                </td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        {{ $box->name }}
                                    </span>
                                </td>
                                <td>{{ \App\CentralLogics\Helpers::format_currency($box->price) }}</td>
                                <td>{{ $box->available_count }}</td>
                                <td>{{ $box->item_count }}</td>
                                <td>
                                    <label class="toggle-switch toggle-switch-sm" for="statusCheckbox{{ $box->id }}">
                                        <input type="checkbox"
                                            data-url="{{ route('admin.box.status', [$box['id'], $box->status ? 0 : 1]) }}"
                                            class="toggle-switch-input redirect-url" id="statusCheckbox{{ $box->id }}"
                                            {{ $box->status ? 'checked' : '' }}>
                                        <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="btn btn-sm btn--primary btn-outline-primary action-btn"
                                            href="{{ route('admin.box.edit', [$box['id']]) }}"
                                            title="{{ translate('messages.edit') }}"><i class="tio-edit"></i></a>
                                        <a class="btn btn-sm btn--danger btn-outline-danger action-btn form-alert"
                                            href="javascript:" data-id="box-{{ $box['id'] }}"
                                            data-message="{{ translate('messages.Want_to_delete_this_box') }}"
                                            title="{{ translate('messages.delete') }}"><i
                                                class="tio-delete-outlined"></i></a>
                                        <form action="{{ route('admin.box.delete', ['id' => $box['id']]) }}"
                                            method="post" id="box-{{ $box['id'] }}">
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

    <!-- Add Box Modal for Store View -->
    <div class="modal fade" id="add-box-modal" tabindex="-1" role="dialog" aria-labelledby="addBoxModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBoxModalLabel">{{ translate('messages.add_new_box') }} - {{ $store->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.box.store') }}" method="post" enctype="multipart/form-data" id="modal_box_form">
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $store->id }}">
                    <div class="modal-body">
                        @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
                        @php($language = $language->value ?? null)

                        @if ($language)
                            <ul class="nav nav-tabs mb-4">
                                <li class="nav-item">
                                    <a class="nav-link modal_lang_link active" href="#"
                                       id="modal-default-link">{{ translate('messages.default') }}</a>
                                </li>
                                @foreach (json_decode($language) as $lang)
                                    <li class="nav-item">
                                        <a class="nav-link modal_lang_link" href="#"
                                           id="modal-{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                @if ($language)
                                    <div class="modal_lang_form" id="modal-default-form">
                                        <div class="form-group">
                                            <label class="input-label" for="name">{{ translate('messages.name') }} ({{ translate('messages.default') }})</label>
                                            <input type="text" name="name[]" class="form-control" placeholder="{{ translate('messages.name') }}" required>
                                        </div>
                                        <input type="hidden" name="lang[]" value="default">

                                        <div class="form-group">
                                            <label class="input-label" for="description">{{ translate('messages.description') }} ({{ translate('messages.default') }})<span class="input-label-secondary text-danger">*</span></label>
                                            <textarea name="description[]" class="form-control" placeholder="{{ translate('messages.description') }}" required></textarea>
                                        </div>
                                    </div>
                                    @foreach (json_decode($language) as $lang)
                                        <div class="d-none modal_lang_form" id="modal-{{ $lang }}-form">
                                            <div class="form-group">
                                                <label class="input-label" for="name">{{ translate('messages.name') }} ({{ strtoupper($lang) }})</label>
                                                <input type="text" name="name[]" class="form-control" placeholder="{{ translate('messages.name') }}">
                                            </div>
                                            <input type="hidden" name="lang[]" value="{{ $lang }}">

                                            <div class="form-group">
                                                <label class="input-label" for="description">{{ translate('messages.description') }} ({{ strtoupper($lang) }})</label>
                                                <textarea name="description[]" class="form-control" placeholder="{{ translate('messages.description') }}"></textarea>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="modal_lang_form" id="modal-default-form">
                                        <div class="form-group">
                                            <label class="input-label" for="name">{{ translate('messages.name') }}</label>
                                            <input type="text" name="name[]" class="form-control" placeholder="{{ translate('messages.name') }}" required>
                                        </div>
                                        <input type="hidden" name="lang[]" value="default">

                                        <div class="form-group">
                                            <label class="input-label" for="description">{{ translate('messages.description') }}<span class="input-label-secondary text-danger">*</span></label>
                                            <textarea name="description[]" class="form-control" placeholder="{{ translate('messages.description') }}" required></textarea>
                                        </div>
                                    </div>
                                @endif

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
                                        <img class="img--176" id="modal_viewer" src="{{ asset('public/assets/admin/img/upload-img.png') }}" alt="image" />
                                    </center>
                                    <label class="input-label">{{ translate('messages.image') }} <small class="text-danger">* ( {{ translate('messages.ratio') }} 1:1 )</small></label>
                                    <div class="custom-file">
                                        <input type="file" name="image" id="modalCustomFileEg1" class="custom-file-input" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required>
                                        <label class="custom-file-label" for="modalCustomFileEg1">{{ translate('messages.choose_file') }}</label>
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
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="input-label" for="pickup_time_from">{{ translate('messages.pickup_time_from') }}</label>
                                            <input type="time" name="pickup_time_from" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="input-label" for="pickup_time_to">{{ translate('messages.pickup_time_to') }}</label>
                                            <input type="time" name="pickup_time_to" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--reset" data-dismiss="modal">{{ translate('messages.cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        $(".modal_lang_link").click(function (e) {
            e.preventDefault();
            $(".modal_lang_link").removeClass('active');
            $(".modal_lang_form").addClass('d-none');
            $(this).addClass('active');

            let id = $(this).attr('id');
            let lang = id.split('-')[1];
            $("#modal-" + lang + "-form").removeClass('d-none');
        });

        function readModalURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#modal_viewer').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#modalCustomFileEg1").change(function () {
            readModalURL(this);
        });

        $('#modal_box_form').on('submit', function (e) {
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
                beforeSend: function () {
                    $('#loading').show();
                },
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
                        }, 1500);
                    }
                },
                complete: function () {
                    $('#loading').hide();
                }
            });
        });
    </script>
@endpush
