@extends('layouts.admin.template')
@section('title', 'Admin | Pembayaran Semua')
@section('css')
@endsection
@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6 d-flex flex-row">
                        <h1>Pembayaran</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item active">/ Pembayaran
                            </li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                @if (env('PEMBAYARAN_CLOSED', false))
                    {{-- Halaman pembayaran ditutup --}}
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card card-warning card-outline mt-4">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-info-circle fa-4x text-warning mb-3"></i>
                                    <h4 class="text-warning font-weight-bold">Halaman Pembayaran Ditutup</h4>
                                    <p class="text-muted mt-3 mb-0" style="font-size: 16px;">
                                        Pembayaran wisuda telah dipindahkan ke website
                                        <strong>SIMKEU</strong> untuk jenjang <strong>S1</strong>,
                                        dan ke <strong>SIAKAD Pasca</strong> untuk jenjang <strong>S2/S3</strong>.
                                    </p>
                                    <hr>
                                    <p class="text-muted mb-0">
                                        <small>Silakan akses sistem terkait untuk melakukan transaksi pembayaran wisuda.</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- INFO --}}
                    @include('admin.pembayaran.info')

                    {{-- FILTER --}}
                    @include('admin.pembayaran.filter')

                    {{-- TABEL DATA --}}
                    @include('admin.pembayaran.tabel')
                    <!-- /.row -->
                @endif

            </div>
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->

        @unless (env('PEMBAYARAN_CLOSED', false))
            @include('admin.pembayaran.pembayaran')
            @include('admin.pembayaran.sarjana')
            @include('admin.pembayaran.pasca')
        @endunless
    </div>
@endsection

@unless (env('PEMBAYARAN_CLOSED', false))
@push('script')
    <script>
        var table1 = null;
    </script>
@endpush
@endunless