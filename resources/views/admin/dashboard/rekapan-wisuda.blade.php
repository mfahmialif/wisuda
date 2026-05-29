{{-- Jumlah Rekapan Wisuda --}}
<div class="card">
    <div class="card-header bg-primary">
        <h3 class="card-title">
            <i class="fas fa-chart-bar mr-1"></i>
            Jumlah Rekapan
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" id="card_refresh_rekapan" data-card-widget="card-refresh"
                data-source="{{ url()->current() }}" data-source-selector="#card_refresh_content_rekapan"
                data-load-on-init="false">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body" id="card_refresh_content_rekapan">
        {{-- Header: Total Pendaftar --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="p-4 rounded" style="background: linear-gradient(135deg, #b8c6db 0%, #c5d0e6 100%);">
                    <p class="mb-1 text-dark" style="font-size: 1rem;">Total Pendaftar Wisuda <span
                            id="rekapan_tahun"></span></p>
                    <h1 class="display-3 font-weight-bold text-dark mb-0" id="rekapan_total">0</h1>
                </div>
            </div>
        </div>

        {{-- Detail: Banin & Banat --}}
        <div class="row">
            {{-- BANIN (Laki-laki) --}}
            <div class="col-md-6 mb-3">
                <div class="p-3 rounded h-100"
                    style="background: linear-gradient(135deg, #a8edea 0%, #c1f5d3 100%); border-left: 5px solid #28a745;">
                    <h5 class="font-weight-bold text-dark mb-3">BANIN</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-dark">S1</span>
                        <strong class="text-dark" id="rekapan_banin_s1">0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-dark">Pasca (S2 & S3)</span>
                        <strong class="text-dark" id="rekapan_banin_pasca">0</strong>
                    </div>
                    <hr class="my-2" style="border-color: rgba(0,0,0,0.2);">
                    <div class="d-flex justify-content-between">
                        <span class="text-dark font-weight-bold">Total</span>
                        <strong class="text-dark" id="rekapan_banin_total" style="font-size: 1.25rem;">0</strong>
                    </div>
                </div>
            </div>

            {{-- BANAT (Perempuan) --}}
            <div class="col-md-6 mb-3">
                <div class="p-3 rounded h-100"
                    style="background: linear-gradient(135deg, #fbc2eb 0%, #e7c4e0 100%); border-left: 5px solid #e83e8c;">
                    <h5 class="font-weight-bold text-dark mb-3">BANAT</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-dark">S1</span>
                        <strong class="text-dark" id="rekapan_banat_s1">0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-dark">Pasca (S2 & S3)</span>
                        <strong class="text-dark" id="rekapan_banat_pasca">0</strong>
                    </div>
                    <hr class="my-2" style="border-color: rgba(0,0,0,0.2);">
                    <div class="d-flex justify-content-between">
                        <span class="text-dark font-weight-bold">Total</span>
                        <strong class="text-dark" id="rekapan_banat_total" style="font-size: 1.25rem;">0</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
    <script>
        $(document).ready(function () {
            initRekapan();

            $('#card_refresh_rekapan').on('overlay.removed.lte.cardrefresh', function () {
                initRekapan();
            });

            // Refresh when year filter changes
            $('#tahun_id').change(function (e) {
                initRekapan();
            });
        });

        function initRekapan() {
            $.ajax({
                type: "get",
                url: "{{ route('admin.dashboard.getRekapan') }}",
                data: {
                    tahun_id: $('#tahun_id').val()
                },
                success: function (response) {
                    $('#rekapan_tahun').text(response.tahun);
                    $('#rekapan_total').text(response.total);

                    $('#rekapan_banin_s1').text(response.banin.s1);
                    $('#rekapan_banin_pasca').text(response.banin.pasca);
                    $('#rekapan_banin_total').text(response.banin.total);

                    $('#rekapan_banat_s1').text(response.banat.s1);
                    $('#rekapan_banat_pasca').text(response.banat.pasca);
                    $('#rekapan_banat_total').text(response.banat.total);
                }
            });
        }
    </script>
@endpush