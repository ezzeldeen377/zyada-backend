@php
    $vendorData = \App\CentralLogics\Helpers::get_store_data();
    $title = $vendorData?->module_type == 'rental' && addon_published_status('Rental') ? 'Provider' : 'Store';
@endphp
@extends('layouts.vendor.app')
@section('title',translate('messages.store_view'))
@push('css_or_js')
    <!-- Custom styles for this page -->
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between">
            <h2 class="page-header-title text-capitalize my-2">
                <img class="w--26" src="{{asset('/public/assets/admin/img/store.png')}}" alt="public">
                <span>
                    {{translate('messages.my_'.$title.'_info')}}
                </span>
            </h2>
            <div class="my-2">
                <a class="btn btn--primary" href="{{route('vendor.shop.edit')}}"><i class="tio-edit"></i>{{translate('messages.edit_'.$title.'_information')}}</a>
            </div>
        </div>
    </div>
    <div class="card border-0">
        <div class="card-body p-0">
            @if($shop->cover_photo)
            <div class="position-relative">
                <img class="my-restaurant-img onerror-image" src="{{ $shop->cover_photo_full_url }}"
                data-onerror-image="{{asset('public/assets/admin/img/900x400/img1.jpg')}}">
                @if ($shop->cover_photo_full_url)
                    <a href="{{ $shop->cover_photo_full_url }}" download class="btn btn-sm btn-dark position-absolute" style="top: 15px; right: 15px; opacity: 0.9; padding: 6px 12px; z-index: 5;" title="{{ translate('Download Cover Photo') }}">
                        <i class="tio-download-to mr-1"></i> {{ translate('Download Cover') }}
                    </a>
                @endif
            </div>
            @endif
            <div class="my-resturant--card">

                @if($shop->image=='def.png')
                <div class="my-resturant--avatar position-relative">
                    <img class="border onerror-image"
                    src="{{asset('public/assets/back-end')}}/img/shop.png"
                    data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}" alt="User Pic">
                </div>
                @else
                    <div class="my-resturant--avatar onerror-image position-relative">
                        <img src="{{ $shop->logo_full_url }}"
                        class="border" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}" alt="">
                        @if ($shop->logo_full_url)
                            <a href="{{ $shop->logo_full_url }}" download class="btn btn-sm btn-dark position-absolute" style="bottom: 8px; right: 8px; opacity: 0.9; padding: 4px 7px; z-index: 5;" title="{{ translate('Download Logo') }}">
                                <i class="tio-download-to"></i>
                            </a>
                        @endif
                    </div>
                @endif

                <div class="my-resturant--content">
                    <span class="d-block mb-1 pb-1">
                        <strong> {{translate('messages.name')}} :</strong>{{$shop->name}}
                    </span>
                    <span class="d-block mb-1 pb-1">
                        <strong>{{translate('messages.phone')}} :</strong> <a href="tel:{{$shop->phone}}">{{$shop->phone}}</a>
                    </span>
                    <span class="d-block mb-1 pb-1">
                        <strong>{{translate('messages.address')}} : </strong> {{$shop->address}}
                    </span>
                    @if (\App\CentralLogics\Helpers::subscription_check())
                    <span class="d-block mb-1 pb-1">
                        <strong>{{translate('messages.Business_Plan')}} : </strong> {{translate($shop->store_business_model)}}
                    </span>
                    @endif
                    <span class="d-block mb-1 pb-1">
                        @if ($shop->store_business_model == 'commission')

                        <strong>{{translate('messages.admin_commission')}} : </strong> {{(isset($shop->comission)? $shop->comission:\App\Models\BusinessSetting::where('key','admin_commission')->first()->value)}}%
                        @elseif(in_array($shop->store_business_model ,['subscription','unsubscribed']))

                        <strong>{{translate('Subscription_plan')}} : </strong> {{ $shop?->store_sub_update_application?->package?->package_name}}
                        @endif

                    </span>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Information & Legal Documents (Read-Only) -->
    <div class="card border-0 mt-3 shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom">
            <div>
                <h4 class="text-title m-0 font-weight-bold d-flex align-items-center">
                    <span class="card-header-icon mr-2 text-primary">
                        <i class="tio-assignment-turned-in fs-20"></i>
                    </span>
                    <span>{{ translate('Submitted Registration Details') }}</span>
                </h4>
                <p class="fs-12 m-0 text-muted">{{ translate('View-only record of store and owner profile submitted during registration') }}</p>
            </div>
            <span class="badge badge-soft-secondary px-3 py-1 font-weight-bold">
                <i class="tio-lock mr-1"></i> {{ translate('Read Only') }}
            </span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card __bg-FAFAFA border-0 h-100 p-3">
                        <h5 class="mb-3 font-weight-bold text-dark d-flex align-items-center">
                            <i class="tio-shop mr-2 text-primary"></i> {{ translate('messages.General_Information') }}
                        </h5>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex align-items-start py-2 border-bottom">
                                <span class="text-muted" style="min-width: 140px;">{{ translate('messages.store_name') }}</span>
                                <span class="font-weight-bold text-dark">: {{ $shop->getRawOriginal('name') ?? $shop->name }}</span>
                            </li>
                            <li class="d-flex align-items-start py-2 border-bottom">
                                <span class="text-muted" style="min-width: 140px;">{{ translate('messages.phone') }}</span>
                                <span class="font-weight-bold text-dark">: <a href="tel:{{ $shop->phone }}">{{ $shop->phone }}</a></span>
                            </li>
                            <li class="d-flex align-items-start py-2 border-bottom">
                                <span class="text-muted" style="min-width: 140px;">{{ translate('messages.address') }}</span>
                                <span class="font-weight-bold text-dark">: {{ $shop->getRawOriginal('address') ?? $shop->address }}</span>
                            </li>
                            <li class="d-flex align-items-start py-2 border-bottom">
                                <span class="text-muted" style="min-width: 140px;">{{ translate('Trade License No') }}</span>
                                <span class="font-weight-bold text-dark">: {{ $shop->trade_license_number ?? translate('messages.n/a') }}</span>
                            </li>
                            <li class="d-flex align-items-start py-2">
                                <span class="text-muted" style="min-width: 140px;">{{ translate('VAT Registration No') }}</span>
                                <span class="font-weight-bold text-dark">: {{ $shop->tin ?? translate('messages.n/a') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card __bg-FAFAFA border-0 h-100 p-3">
                        <h5 class="mb-3 font-weight-bold text-dark d-flex align-items-center">
                            <i class="tio-user mr-2 text-primary"></i> {{ translate('messages.Owner_Information') }}
                        </h5>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex align-items-start py-2 border-bottom">
                                <span class="text-muted" style="min-width: 140px;">{{ translate('messages.First Name') }}</span>
                                <span class="font-weight-bold text-dark">: {{ $shop->vendor?->f_name ?? translate('messages.n/a') }}</span>
                            </li>
                            <li class="d-flex align-items-start py-2 border-bottom">
                                <span class="text-muted" style="min-width: 140px;">{{ translate('messages.Last Name') }}</span>
                                <span class="font-weight-bold text-dark">: {{ $shop->vendor?->l_name ?? translate('messages.n/a') }}</span>
                            </li>
                            <li class="d-flex align-items-start py-2 border-bottom">
                                <span class="text-muted" style="min-width: 140px;">{{ translate('messages.Phone') }}</span>
                                <span class="font-weight-bold text-dark">: <a href="tel:{{ $shop->vendor?->phone }}">{{ $shop->vendor?->phone ?? translate('messages.n/a') }}</a></span>
                            </li>
                            <li class="d-flex align-items-start py-2">
                                <span class="text-muted" style="min-width: 140px;">{{ translate('messages.Email') }}</span>
                                <span class="font-weight-bold text-dark">: <a href="mailto:{{ $shop->vendor?->email }}">{{ $shop->vendor?->email ?? translate('messages.n/a') }}</a></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submitted Documents & Executed Contract (Read-Only) -->
    @php($registrationDocuments = [
        ['label' => 'Executed Vendor Contract', 'icon' => 'tio-document-text', 'file' => $shop->store_contract_pdf, 'url' => $shop->store_contract_pdf_full_url, 'badge' => 'Contract / اتفاقية'],
        ['label' => 'Trade License', 'icon' => 'tio-file-text', 'file' => $shop->trade_license, 'url' => $shop->trade_license_full_url, 'badge' => 'License / رخصة'],
        ['label' => 'Emirates ID', 'icon' => 'tio-credit-card', 'file' => $shop->emirates_id, 'url' => $shop->emirates_id_full_url, 'badge' => 'EID / هوية'],
        ['label' => 'Passport Copy', 'icon' => 'tio-airplane', 'file' => $shop->passport_copy, 'url' => $shop->passport_copy_full_url, 'badge' => 'Passport / جواز'],
        ['label' => 'IBAN Letter / Bank Account Certificate', 'icon' => 'tio-bank', 'file' => $shop->iban_letter, 'url' => $shop->iban_letter_full_url, 'badge' => 'Bank / بنك'],
        ['label' => 'VAT Registration Certificate', 'icon' => 'tio-receipt', 'file' => $shop->tin_certificate_image, 'url' => $shop->tin_certificate_image_full_url, 'badge' => 'VAT / ضريبة'],
    ])
    @php($hasAnyDoc = collect($registrationDocuments)->contains(fn ($document) => filled($document['file'])) || filled($shop->tin) || filled($shop->trade_license_number))
    @if ($hasAnyDoc)
        <div class="row pt-3 g-3">
            <div class="col-12">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom">
                        <h5 class="card-title m-0 d-flex align-items-center font-weight-bold">
                            <span class="card-header-icon mr-2 text-primary">
                                <i class="tio-folder-special fs-20"></i>
                            </span>
                            <span>{{ translate('Submitted Documents & Legal Agreement') }}</span>
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge badge-soft-secondary font-weight-bold px-3 py-1">
                                <i class="tio-lock mr-1"></i> {{ translate('Read Only') }}
                            </span>
                            @if ($shop->trade_license_number)
                                <span class="badge badge-soft-primary px-3 py-2 fs-12">
                                    <strong>{{ translate('Trade License No') }}:</strong> {{ $shop->trade_license_number }}
                                </span>
                            @endif
                            @if ($shop->tin)
                                <span class="badge badge-soft-info px-3 py-2 fs-12">
                                    <strong>{{ translate('VAT Registration No') }}:</strong> {{ $shop->tin }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($registrationDocuments as $document)
                                @if ($document['file'])
                                    @php($isPdf = \Illuminate\Support\Str::endsWith(strtolower($document['file']), '.pdf'))
                                    @php($thumbnail = $isPdf ? asset('public/assets/admin/img/document.svg') : $document['url'])
                                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                                        <div class="card h-100 border rounded-10 p-3 shadow-none hover-shadow transition">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="badge badge-soft-secondary font-weight-bold">{{ $document['badge'] }}</span>
                                                <a href="{{ $document['url'] }}" download class="btn btn-sm btn-outline-primary" style="padding: 3px 8px;" title="{{ translate('Download') }}">
                                                    <i class="tio-download-to mr-1"></i> {{ translate('Download') }}
                                                </a>
                                            </div>
                                            <h6 class="font-weight-bold mb-2 text-truncate" title="{{ translate($document['label']) }}">
                                                {{ translate($document['label']) }}
                                            </h6>
                                            <div class="pdf-single w-100 mb-2 cursor-pointer" data-pdf-url="{{ $document['url'] }}" style="height: 140px; border-radius: 6px; overflow: hidden; position: relative;">
                                                <div class="pdf-frame h-100 w-100 d-flex align-items-center justify-content-center bg-light">
                                                    <img class="pdf-thumbnail-alt" src="{{ $thumbnail }}" alt="{{ translate($document['label']) }}" style="max-height: 120px; max-width: 100%; object-fit: contain;">
                                                </div>
                                                <div class="overlay">
                                                    <a href="{{ $document['url'] }}" download class="download-btn" title="{{ translate('Download') }}">
                                                        <i class="tio-download-to"></i>
                                                    </a>
                                                    <div class="pdf-info d-flex gap-10px align-items-center">
                                                        <img src="{{ asset('public/assets/admin/img/' . ($isPdf ? 'document.svg' : 'picture.svg')) }}" width="28" alt="">
                                                        <div class="fs-12 text--title d-flex flex-column text-white">
                                                            <span class="file-name js-filename-truncate">{{ $document['file'] }}</span>
                                                            <span class="opacity-75">{{ translate('Click to preview') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between pt-1">
                                                <small class="text-muted text-truncate js-filename-truncate" style="max-width: 180px;">{{ $document['file'] }}</small>
                                                <a href="{{ $document['url'] }}" target="_blank" class="text-primary font-weight-bold fs-12">
                                                    {{ translate('View') }} <i class="tio-open-in-new"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 mt-3">
        <div class="card-header">
            <h5 class="card-title toggle-switch toggle-switch-sm d-flex justify-content-between">
                <span class="card-header-icon mr-1"><i class="tio-dashboard"></i></span>
                <span>{{translate('Announcement')}}</span><span class="input-label-secondary" data-toggle="tooltip" data-placement="right" data-original-title="{{translate('This_feature_is_for_sharing_important_information_or_announcements_related_to_the_'.$title.'.')}}"><img src="{{asset('/public/assets/admin/img/info-circle.svg')}}" alt="{{translate('messages.This_feature_is_for_sharing_important_information_or_announcements_related_to_the_'.$title)}}"></span>
            </h5>
            <label class="toggle-switch toggle-switch-sm" for="announcement_status">
                <input class="toggle-switch-input dynamic-checkbox" type="checkbox" id="announcement_status"
                       data-id="announcement_status"
                       data-type="status"
                       data-image-on='{{asset('/public/assets/admin/img/modal')}}/digital-payment-on.png'
                       data-image-off="{{asset('/public/assets/admin/img/modal')}}/digital-payment-off.png"
                       data-title-on="{{translate('Do_you_want_to_enable_the_announcement')}}"
                       data-title-off="{{translate('Do_you_want_to_disable_the_announcement')}}"
                       data-text-on="<p>{{translate('User_will_able_to_see_the_Announcement_on_the_store_page.')}}</p>"
                       data-text-off="<p>{{translate('User_will_not_be_able_to_see_the_Announcement_on_the_store_page')}}</p>"
                       name="announcement" value="1" {{$shop->announcement?'checked':''}}>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
            </label>


        </div>
        <form action="{{route('vendor.business-settings.toggle-settings',[$shop->id,$shop->announcement?0:1, 'announcement'])}}"
            method="get" id="announcement_status_form">
            </form>
        <div class="card-body">
            <form action="{{route('vendor.shop.update-message')}}" method="post">
            @csrf
                <textarea name="announcement_message" id="" class="form-control" rows="5" placeholder="{{ translate('messages.ex_:_ABC_Company') }}">{{ $shop->announcement_message??'' }}</textarea>
                <div class="justify-content-end btn--container mt-2">
                    <button type="submit" class="btn btn--primary">{{translate('publish')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
