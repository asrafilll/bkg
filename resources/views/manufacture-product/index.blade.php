<x-app>
    @if (Session::has('success'))
        <script>
            toastr.success('{{ Session::get('success') }}');
        </script>
    @endif

    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row justify-content-between mb-2">
                <div class="col-auto">
                    <h1 class="m-0">{{ __('Manufacture products') }}</h1>
                </div><!-- /.col -->
                @can(\App\Enums\PermissionEnum::create_manufacture_product()->value)
                    <div class="col-auto">
                        <a
                            href="{{ url('/manufacture-products/create') }}"
                            class="btn btn-primary"
                        >{{ __('Create manufacture product') }}</a>
                    </div><!-- /.col -->
                @endcan
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col">
                                    <form
                                        action=""
                                        method="GET"
                                    >
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-search"></i>
                                                </span>
                                            </div>
                                            <input
                                                type="search"
                                                name="term"
                                                class="form-control"
                                                value="{{ Request::get('term') }}"
                                                placeholder="{{ __('Filter manufacture products') }}"
                                            />
                                        </div>
                                    </form>
                                </div>
                                <div class="col-auto">
                                    <button
                                        type="button"
                                        class="btn btn-default"
                                        data-toggle="modal"
                                        data-target="#filterModal"
                                    >
                                        <i class="fas fa-filter"></i>
                                        <span>{{ __('Filter') }}</span>
                                    </button>
                                    <div
                                        class="modal fade"
                                        id="filterModal"
                                        tabindex="-1"
                                        aria-labelledby="filterModalLabel"
                                        aria-hidden="true"
                                    >
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form
                                                    action=""
                                                    method="GET"
                                                >
                                                    <div class="modal-header">
                                                        <h5
                                                            class="modal-title"
                                                            id="filterModalLabel"
                                                        >{{ __('Filter') }}</h5>
                                                        <button
                                                            type="button"
                                                            class="close"
                                                            data-dismiss="modal"
                                                            aria-label="Close"
                                                        >
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label for="term">
                                                                <span>{{ __('Filter manufacture products') }}</span>
                                                            </label>
                                                            <input
                                                                type="search"
                                                                name="term"
                                                                class="form-control"
                                                                value="{{ Request::get('term') }}"
                                                            />
                                                        </div>
                                                        <div class="row">
                                                            <div
                                                                class="form-group col"
                                                                id="start_created_at-module"
                                                            >
                                                                <label for="start_created_at">
                                                                    <span>{{ __('Start date') }}</span>
                                                                    <span class="text-danger">*</span>
                                                                </label>
                                                                <input
                                                                    id="start_created_at"
                                                                    type="text"
                                                                    name="start_created_at"
                                                                    class="form-control datetimepicker-input"
                                                                    data-target="#start_created_at"
                                                                    data-toggle="datetimepicker"
                                                                    value="{{ Request::get('start_created_at') }}"
                                                                />
                                                            </div>
                                                            <script>
                                                                var StartCreatedAtModule = (function() {
                                                                    var $el = $('#start_created_at-module');
                                                                    var $startCreatedAt = $el.find('#start_created_at');

                                                                    $startCreatedAt.on('change.datetimepicker', function(e) {
                                                                        if (typeof EndCreatedAtModule !== 'undefined') {
                                                                            EndCreatedAtModule.setMinDate(e.date);
                                                                        }
                                                                    });

                                                                    function init() {
                                                                        $startCreatedAt.datetimepicker({
                                                                            format: 'YYYY-MM-DD',
                                                                            maxDate: {!! "'" . Request::get('end_created_at') . "'" !!} || false,
                                                                        });
                                                                    }

                                                                    function setMaxDate(date) {
                                                                        $startCreatedAt.datetimepicker('maxDate', date);
                                                                    }

                                                                    init();

                                                                    return {
                                                                        setMaxDate: setMaxDate,
                                                                    };
                                                                })();
                                                            </script>
                                                            <div
                                                                class="form-group col"
                                                                id="end_created_at-module"
                                                            >
                                                                <label for="end_created_at">
                                                                    <span>{{ __('End date') }}</span>
                                                                    <span class="text-danger">*</span>
                                                                </label>
                                                                <input
                                                                    id="end_created_at"
                                                                    type="text"
                                                                    name="end_created_at"
                                                                    class="form-control datetimepicker-input"
                                                                    data-target="#end_created_at"
                                                                    data-toggle="datetimepicker"
                                                                    value="{{ Request::get('end_created_at') }}"
                                                                />
                                                            </div>
                                                            <script>
                                                                var EndCreatedAtModule = (function() {
                                                                    var $el = $('#end_created_at-module');
                                                                    var $endCreatedAt = $el.find('#end_created_at');

                                                                    $endCreatedAt.on('change.datetimepicker', function(e) {
                                                                        if (typeof StartCreatedAtModule !== 'undefined') {
                                                                            StartCreatedAtModule.setMaxDate(e.date);
                                                                        }
                                                                    });

                                                                    function init() {
                                                                        $endCreatedAt.datetimepicker({
                                                                            format: 'YYYY-MM-DD',
                                                                            useCurrent: false,
                                                                            minDate: {!! "'" . Request::get('start_created_at') . "'" !!} || false,
                                                                        });
                                                                    }

                                                                    function setMinDate(date) {
                                                                        $endCreatedAt.datetimepicker('minDate', date);
                                                                    }

                                                                    init();

                                                                    return {
                                                                        setMinDate: setMinDate,
                                                                    };
                                                                })();
                                                            </script>
                                                        </div>
                                                        <div
                                                            class="form-group"
                                                            id="branch-module"
                                                        >
                                                            <label for="branch_id">
                                                                <span>{{ __('Branch') }}</span>
                                                            </label>
                                                            <select
                                                                id="branch_id"
                                                                name="branch_id"
                                                                class="form-control @error('branch_id') is-invalid @enderror"
                                                                style="width: 100%;"
                                                            >
                                                                <option></option>
                                                                @if (Request::get('branch_id') && Request::get('branch_name'))
                                                                    <option
                                                                        value="{{ Request::get('branch_id') }}"
                                                                        selected
                                                                    >{{ Request::get('branch_name') }}</option>
                                                                @endif
                                                            </select>
                                                            <input
                                                                type="hidden"
                                                                id="branch_name"
                                                                name="branch_name"
                                                                value="{{ Request::get('branch_name') }}"
                                                            />
                                                            @error('branch_id')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <script>
                                                            var BranchModule = (function() {
                                                                var $el = $('#branch-module');
                                                                var $branchId = $el.find('#branch_id');
                                                                var $branchName = $el.find('#branch_name');

                                                                $branchId.on('select2:select', function(e) {
                                                                    $branchName.val(e.params.data.text);
                                                                });

                                                                $branchId.on('select2:unselect', function() {
                                                                    $branchName.val(null);
                                                                });

                                                                $branchId.on('select2:clear', function() {
                                                                    setTimeout(function() {
                                                                        $branchId.select2('close');
                                                                    }, 0);
                                                                });

                                                                function init() {
                                                                    $branchId.select2({
                                                                        theme: 'bootstrap4',
                                                                        placeholder: '',
                                                                        allowClear: true,
                                                                        ajax: {
                                                                            url: '/manufacture-products?action=fetch-branches',
                                                                            dataType: 'json',
                                                                            delay: 250,
                                                                            processResults: function(branches) {
                                                                                return {
                                                                                    results: branches.map(function(branch) {
                                                                                        return {
                                                                                            id: branch.id,
                                                                                            text: branch.name,
                                                                                            branch: branch,
                                                                                        };
                                                                                    }),
                                                                                };
                                                                            },
                                                                        },
                                                                    });
                                                                }

                                                                init();
                                                            })()
                                                        </script>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button
                                                            type="button"
                                                            class="btn btn-default"
                                                            data-dismiss="modal"
                                                        >{{ __('Close') }}</button>
                                                        <button
                                                            type="submit"
                                                            class="btn btn-primary"
                                                        >{{ __('Save') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="dropdown">
                                        <button
                                            type="button"
                                            class="btn btn-default"
                                            data-toggle="dropdown"
                                        >
                                            <i class="fas fa-sort"></i>
                                            <span>{{ __('Sort') }}</span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a
                                                href="{{ Request::fullUrlWithQuery([
                                                    'sort' => 'order_number',
                                                    'direction' => 'asc',
                                                ]) }}"
                                                class="dropdown-item {{ Request::get('sort') == 'order_number' && Request::get('direction') == 'asc' ? 'active' : '' }}"
                                            >{{ __('Order number ascending') }}</a>
                                            <a
                                                href="{{ Request::fullUrlWithQuery([
                                                    'sort' => 'order_number',
                                                    'direction' => 'desc',
                                                ]) }}"
                                                class="dropdown-item {{ Request::get('sort') == 'order_number' && Request::get('direction') == 'desc' ? 'active' : '' }}"
                                            >{{ __('Order number descending') }}</a>
                                            <a
                                                href="{{ Request::fullUrlWithQuery([
                                                    'sort' => 'created_at',
                                                    'direction' => 'asc',
                                                ]) }}"
                                                class="dropdown-item {{ Request::get('sort') == 'created_at' && Request::get('direction') == 'asc' ? 'active' : '' }}"
                                            >{{ __('Date created ascending') }}</a>
                                            <a
                                                href="{{ Request::fullUrlWithQuery([
                                                    'sort' => 'created_at',
                                                    'direction' => 'desc',
                                                ]) }}"
                                                class="dropdown-item {{ (Request::get('sort') == 'created_at' && Request::get('direction') == 'desc') || (!Request::filled('sort') && !Request::filled('direction')) ? 'active' : '' }}"
                                            >{{ __('Date created descending') }}</a>
                                            <a
                                                href="{{ Request::fullUrlWithQuery([
                                                    'sort' => 'total_line_product_components_quantity',
                                                    'direction' => 'asc',
                                                ]) }}"
                                                class="dropdown-item {{ Request::get('sort') == 'total_line_product_components_quantity' && Request::get('direction') == 'asc' ? 'active' : '' }}"
                                            >{{ __('Total line product components quantity ascending') }}</a>
                                            <a
                                                href="{{ Request::fullUrlWithQuery([
                                                    'sort' => 'total_line_product_components_quantity',
                                                    'direction' => 'desc',
                                                ]) }}"
                                                class="dropdown-item {{ Request::get('sort') == 'total_line_product_components_quantity' && Request::get('direction') == 'desc' ? 'active' : '' }}"
                                            >{{ __('Total line product components quantity descending') }}</a>
                                            <a
                                                href="{{ Request::fullUrlWithQuery([
                                                    'sort' => 'total_line_products_quantity',
                                                    'direction' => 'asc',
                                                ]) }}"
                                                class="dropdown-item {{ Request::get('sort') == 'total_line_products_quantity' && Request::get('direction') == 'asc' ? 'active' : '' }}"
                                            >{{ __('Total line products quantity ascending') }}</a>
                                            <a
                                                href="{{ Request::fullUrlWithQuery([
                                                    'sort' => 'total_line_products_quantity',
                                                    'direction' => 'desc',
                                                ]) }}"
                                                class="dropdown-item {{ Request::get('sort') == 'total_line_products_quantity' && Request::get('direction') == 'desc' ? 'active' : '' }}"
                                            >{{ __('Total line products quantity descending') }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th>{{ __('Order number') }}</th>
                                        <th>{{ __('Components') }}</th>
                                        <th>{{ __('Products') }}</th>
                                        <th>{{ __('Date created') }}</th>
                                        <th>{{ __('Branch') }}</th>
                                        <th>{{ __('Total line product components quantity') }}</th>
                                        <th>{{ __('Total line products quantity') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($manufactureProducts as $manufactureProduct)
                                        <tr>
                                            <td class="align-top">
                                                <a
                                                    href="{{ url('/manufacture-products/' . $manufactureProduct->id) }}">
                                                    {{ $manufactureProduct->order_number }}
                                                </a>
                                            </td>
                                            <td class="align-top">
                                                <ul>
                                                    @foreach ($manufactureProduct->lineProductComponents as $productComponent)
                                                        <li>{{ $productComponent->product_component_name }}</li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                            <td class="align-top">
                                                <ul>
                                                    @foreach ($manufactureProduct->lineProducts as $product)
                                                        <li>{{ $product->product_name }}</li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                            <td class="align-top">{{ $manufactureProduct->created_at }}</td>
                                            <td class="align-top">{{ $manufactureProduct->branch_name }}</td>
                                            <td class="align-top">
                                                {{ $manufactureProduct->idr_total_line_product_components_quantity }}
                                            </td>
                                            <td class="align-top">
                                                {{ $manufactureProduct->idr_total_line_products_quantity }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td
                                                colspan="7"
                                                class="text-center"
                                            >{{ __('Data not found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer d-flex justify-content-center">
                            {!! $manufactureProducts->withQueryString()->links() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</x-app>
