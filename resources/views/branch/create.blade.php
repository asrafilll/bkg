<x-app>
    @if ($errors->any())
        <script>
            toastr.error('{{ $errors->first() }}');
        </script>
    @endif
    @if (Session::has('success'))
        <script>
            toastr.success('{{ Session::get('success') }}');
        </script>
    @endif

    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container">
            <div class="row mb-2">
                <div class="col-auto">
                    <a
                        href="{{ url('/branches') }}"
                        class="btn btn-default"
                    >
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
                <div class="col-auto">
                    <h1 class="m-0">{{ __('New branch') }}</h1>
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
                    <form
                        action="{{ url('/branches') }}"
                        method="POST"
                        novalidate
                    >
                        @csrf
                        <div class="card">

                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">
                                        <span>{{ __('Name') }}</span>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ Request::old('name') }}"
                                    />
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="phone">
                                        <span>{{ __('Phone') }}</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="phone"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        value="{{ Request::old('phone') }}"
                                    />
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="order_number_prefix">
                                        <span>{{ __('Order Number Prefix') }}</span>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="order_number_prefix"
                                        class="form-control @error('order_number_prefix') is-invalid @enderror"
                                        value="{{ Request::old('order_number_prefix') }}"
                                    />
                                    @error('order_number_prefix')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="next_order_number">
                                        <span>{{ __('Next Order Number') }}</span>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        name="next_order_number"
                                        class="form-control @error('next_order_number') is-invalid @enderror"
                                        value="{{ Request::old('next_order_number') }}"
                                    />
                                    @error('next_order_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="purchase_number_prefix">
                                        <span>{{ __('Purchase Number Prefix') }}</span>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="purchase_number_prefix"
                                        class="form-control @error('purchase_number_prefix') is-invalid @enderror"
                                        value="{{ Request::old('purchase_number_prefix') }}"
                                    />
                                    @error('purchase_number_prefix')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="next_purchase_number">
                                        <span>{{ __('Next Purchase Number') }}</span>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        name="next_purchase_number"
                                        class="form-control @error('next_purchase_number') is-invalid @enderror"
                                        value="{{ Request::old('next_purchase_number') }}"
                                    />
                                    @error('next_purchase_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <div class="icheck-primary">
                                        <input
                                            type="checkbox"
                                            id="is_main"
                                            name="is_main"
                                            value="1"
                                            @if (Request::old('is_main')) checked @endif
                                        >
                                        <label for="is_main">
                                            {{ __('Set as main branch') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">{{ __('Users') }}</h5>
                            </div>
                            <div class="card-body">
                                @foreach ($users as $user)
                                    <div class="icheck-primary">
                                        <input
                                            type="checkbox"
                                            id="user_id_{{ $user->id }}"
                                            name="user_ids[]"
                                            value="{{ $user->id }}"
                                        />
                                        <label for="user_id_{{ $user->id }}">
                                            {{ $user->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >{{ __('Save') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</x-app>
