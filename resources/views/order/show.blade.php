<x-app>
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container">
            <div class="row mb-2">
                <div class="col-auto">
                    <a
                        href="{{ url('/orders') }}"
                        class="btn btn-default"
                    >
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
                <div class="col-auto">
                    <h1 class="m-0">{{ $order->order_number }}</h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <dl>
                                <dt>{{ __('Branch') }}</dt>
                                <dd>{{ $order->branch->name }}</dd>
                            </dl>
                            <dl>
                                <dt>{{ __('Order source') }}</dt>
                                <dd>{{ $order->orderSource->name }}</dd>
                            </dl>
                            <dl>
                                <dt>{{ __('Customer name') }}</dt>
                                <dd>{{ $order->customer_name }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">{{ __('Products') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Product') }}</th>
                                            <th
                                                width="100px"
                                                class="text-right"
                                            >{{ __('Quantity') }}</th>
                                            <th width="250px"class="text-right">{{ __('Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->orderLineItems as $orderLineItem)
                                            <tr>
                                                <td>
                                                    <div>{{ $orderLineItem->product_name }}</div>
                                                    <div>{{ $orderLineItem->idr_price }}</div>
                                                </td>
                                                <td class="text-right">{{ $orderLineItem->idr_quantity }}</td>
                                                <td class="text-right">{{ $orderLineItem->idr_total }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>{{ __('Sub Total') }}</th>
                                            <th class="text-right">{{ $order->idr_total_line_items_quantity }}</th>
                                            <th class="text-right">{{ $order->idr_total_line_items_price }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">{{ __('Summary') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <tr>
                                        <td>{{ __('Percentage Discount') }}</td>
                                        <td class="text-right">{{ $order->percentage_discount }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('Total Discount') }}</td>
                                        <td class="text-right">{{ $order->idr_total_discount }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('Sub Total') }}</td>
                                        <td class="text-right">{{ $order->idr_total_line_items_price }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('Total') }}</td>
                                        <td class="text-right">{{ $order->idr_total_price }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</x-app>