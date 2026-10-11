@php($registrationDocuments = [
    ['field' => 'trade_license', 'label' => 'Trade License', 'accept' => '.pdf,.jpg,.jpeg,.png,.webp', 'url' => 'trade_license_full_url'],
    ['field' => 'emirates_id', 'label' => 'Emirates ID', 'accept' => 'image/*', 'url' => 'emirates_id_full_url'],
    ['field' => 'passport_copy', 'label' => 'Passport Copy', 'accept' => 'image/*', 'url' => 'passport_copy_full_url'],
    ['field' => 'iban_letter', 'label' => 'IBAN Letter / Bank Account Certificate', 'accept' => '.pdf,.jpg,.jpeg,.png,.webp', 'url' => 'iban_letter_full_url'],
])

<div class="col-lg-12">
    <div class="card p-20">
        <div class="mb-3">
            <h3 class="mb-1">{{ translate('Vendor Documents') }}</h3>
            <p class="fs-12 mb-0">{{ translate('All documents are optional. Maximum file size: 2 MB.') }}</p>
        </div>
        <div class="row g-3">
            @foreach ($registrationDocuments as $document)
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="input-label" for="{{ $document['field'] }}">{{ translate($document['label']) }}</label>
                        <input id="{{ $document['field'] }}" type="file" name="{{ $document['field'] }}"
                            class="form-control" accept="{{ $document['accept'] }}" data-max-size="2mb">
                        @if (isset($store) && $store->{$document['field']})
                            @php($documentUrl = $store->{$document['url']})
                            @php($documentExtension = strtolower(pathinfo(parse_url($documentUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)))
                            <div class="mt-2">
                                @if (in_array($documentExtension, ['jpg', 'jpeg', 'png', 'webp']))
                                    <a href="{{ $documentUrl }}" target="_blank" rel="noopener">
                                        <img src="{{ $documentUrl }}" alt="{{ translate($document['label']) }}"
                                            class="img-fluid rounded border" style="max-height: 220px;">
                                    </a>
                                @elseif ($documentExtension === 'pdf')
                                    <iframe src="{{ $documentUrl }}#toolbar=1" title="{{ translate($document['label']) }}"
                                        class="w-100 border rounded" style="height: 320px;"></iframe>
                                @else
                                    <a href="{{ $documentUrl }}" target="_blank" rel="noopener">
                                        {{ translate('Open document') }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
