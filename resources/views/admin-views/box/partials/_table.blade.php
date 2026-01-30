@foreach ($boxes as $key => $box)
    <tr>
        <td>{{ $key + 1 }}</td>
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
