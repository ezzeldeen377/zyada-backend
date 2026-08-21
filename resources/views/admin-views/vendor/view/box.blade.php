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

                    <a href="{{ route('admin.box.add-new', ['store_id' => $store->id]) }}" class="btn btn--primary pull-right"><i
                            class="tio-add-circle"></i> {{ translate('messages.add_new_box') }}</a>
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
@endsection
