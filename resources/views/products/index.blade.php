@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => __('Inventory'),
        'subtitle' => __('Products'),
        'buttons' => [
            [
                'title' => __('Create Product'),
                'href' => '#',
                'data-toggle' => 'modal',
                'data-target' => '#productCreateModal',
            ]
        ]
    ])

    @if (app('user')->getSessionCustomer()->parent->id ?? null)
    <div class="container-fluid  select2Container">
        <form action="{{ route('product.reseller') }}" method="post">
            @csrf
            <input type="hidden" id="customer_id" name="customer_id" value="{{ app('user')->getSessionCustomer()->id }}" class="customer_id" />

            <div class="row">
                <div class="col-6">
                    @include('shared.forms.ajaxSelect', [
                        'url' => route('supplier.filterProducts', ['customer' => app('user')->getSessionCustomer()->parent->id]),
                        'name' => 'product_id',
                        'className' => 'ajax-user-input product_id',
                        'placeholder' => __('Search products'),
                        'label' => '',
                        'fixRouteAfter' => '.ajax-user-input.customer_id'
                    ])
                </div>
                <div class="col-2">
                    <button type="submit" class="btn bg-logoOrange text-white font-weight-700 mt-4" id="submit-reseller-product">
                        {{ __('Add Product') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endif

    <x-datatable
            search-placeholder="{{ __('Search product') }}"
            table-id="products-table"
            filters="local"
            filter-menu="shared.collapse.forms.products"
            :data="$data"
            datatableOrder="{!! json_encode($datatableOrder) !!}"
            :bulk-edit="true"
            :bulk-print="true"
            model-name="Product"
            bulk-edit-form="products.bulk_edit"
            disable-autoload="{{ (bool) customer_settings(app('user')->getSessionCustomer()->id ?? null, \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PRODUCTS, 0) }}"
    >
        <x-slot name="tableActions">
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Import Products') }}" data-toggle="modal"
                   data-target="#import-products-modal">
                    <i class="picon-upload-light icon-lg"></i>
                </a>
            </div>
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Export Products') }}" data-toggle="modal"
                   data-target="#export-products-modal">
                    <i class="picon-archive-light icon-lg"></i>
                </a>
            </div>
        </x-slot>
    </x-datatable>

    @include('shared.modals.productCreate')
    @include('shared.modals.components.product.importCsv')
    @include('shared.modals.components.product.exportCsv')
    @include('shared.modals.components.product.recover')
    @include('shared.modals.components.product.image')
@endsection

@push('js')
    <script>
        new Product('{{$keyword}}');
    </script>
@endpush
