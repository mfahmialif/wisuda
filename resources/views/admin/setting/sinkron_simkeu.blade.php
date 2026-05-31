<div class="card card-info" id="card_sinkron_simkeu">
    <div class="card-header">
        <i class="fas fa-sync mr-2"></i> Sinkronisasi Pembayaran SIMKEU
        <div class="card-tools">
            <button type="button" class="btn btn-tool" id="card_refresh_sinkron_simkeu" data-card-widget="card-refresh"
                data-source="{{ url()->current() }}" data-source-selector="#card_refresh_content_sinkron_simkeu"
                data-load-on-init="false">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="maximize">
                <i class="fas fa-expand"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="card-body" id="card_refresh_content_sinkron_simkeu">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i>
            Sinkronkan semua data pembayaran peserta wisuda ke sistem SIMKEU V2.
            Data akan dikirim secara <strong>batch</strong> untuk menghindari timeout.
        </div>

        <form id="form_sinkron_simkeu">
            @csrf
            <div class="form-group">
                <label for="tahun_id_sinkron_simkeu">Tahun Akademik</label>
                <select class="form-control select2bs4 w-100" name="tahun_id" id="tahun_id_sinkron_simkeu" required>
                    @foreach ($tahun as $item)
                        <option value="{{ $item->id }}">{{ $item->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="prodi_id_sinkron_simkeu">Prodi Jenjang</label>
                <select class="form-control select2bs4 w-100" name="prodi_id" id="prodi_id_sinkron_simkeu">
                    <option value="">-- Semua Prodi --</option>
                    @foreach (['S1', 'S2', 'S3'] as $jenjang)
                        @php $prodiJenjang = $prodi->where('jenjang', $jenjang); @endphp
                        @if ($prodiJenjang->count())
                            <optgroup label="{{ $jenjang }}">
                                @foreach ($prodiJenjang as $p)
                                    <option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->jenjang }})</option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="batch_size_sinkron_simkeu">Batch Size (per kirim)</label>
                        <input type="number" name="batch_size" class="form-control" id="batch_size_sinkron_simkeu"
                            value="10" min="1" max="50" required>
                        <small class="text-muted">Jumlah data yang dikirim per batch (default: 10)</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Info Data</label>
                        <div id="info_sinkron_simkeu" class="form-control-plaintext">
                            <span class="badge badge-secondary">Pilih tahun akademik</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <button type="submit" class="btn btn-info w-100" id="form_submit_sinkron_simkeu">
                        <i class="fas fa-cloud-upload-alt mx-2"></i>Sinkron ke SIMKEU
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-primary w-100" data-toggle="modal"
                        data-target="#modal_log_sinkron_simkeu">
                        <i class="fas fa-file-alt mx-2"></i>Log Sinkronisasi
                    </button>
                </div>
            </div>
        </form>

        {{-- Progress bar --}}
        <div class="mt-3 d-none" id="progress_container_sinkron_simkeu">
            <label>Progress Sinkronisasi</label>
            <div class="progress" style="height: 25px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar"
                    id="progress_bar_sinkron_simkeu" style="width: 0%">
                    0%
                </div>
            </div>
            <div class="mt-2">
                <small>
                    <span class="text-success" id="progress_success_sinkron_simkeu">✓ Berhasil: 0</span> |
                    <span class="text-danger" id="progress_failed_sinkron_simkeu">✗ Gagal: 0</span> |
                    <span class="text-muted" id="progress_total_sinkron_simkeu">Total: 0/0</span>
                </small>
            </div>
        </div>
    </div>
</div>

{{-- Modal Log --}}
<div class="modal fade" id="modal_log_sinkron_simkeu" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h4 class="modal-title text-white"><i class="fas fa-file-alt mr-2"></i>Log Sinkronisasi SIMKEU</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                <div id="log_sinkron_simkeu_empty" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Belum ada log sinkronisasi</p>
                </div>
                <table class="table table-sm table-bordered d-none" id="table_log_sinkron_simkeu">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">NIM</th>
                            <th width="20%">Jumlah</th>
                            <th width="10%">Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="tbody_log_sinkron_simkeu"></tbody>
                </table>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm" id="btn_clear_log_sinkron_simkeu">
                    <i class="fas fa-trash mr-1"></i> Hapus Log
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('script')
    <script>
        $(document).ready(function() {
            initSinkronSimkeu();

            $('#card_refresh_sinkron_simkeu').on('overlay.removed.lte.cardrefresh', function() {
                initSinkronSimkeu();
            });
        });

        function initSinkronSimkeu() {
            // Initialize Select2
            $('#tahun_id_sinkron_simkeu, #prodi_id_sinkron_simkeu').select2({
                theme: 'bootstrap4',
                dropdownParent: $('#form_sinkron_simkeu')
            });

            // Hitung jumlah pembayaran saat tahun/prodi berubah
            $('#tahun_id_sinkron_simkeu, #prodi_id_sinkron_simkeu').change(function() {
                getCountSinkronSimkeu();
            });
            getCountSinkronSimkeu();

            // Form submit
            $('#form_sinkron_simkeu').submit(function(e) {
                e.preventDefault();
                sinkronSimkeu();
            });

            // Clear log
            $('#btn_clear_log_sinkron_simkeu').click(function() {
                $('#tbody_log_sinkron_simkeu').empty();
                $('#table_log_sinkron_simkeu').addClass('d-none');
                $('#log_sinkron_simkeu_empty').removeClass('d-none');
            });
        }

        function getCountSinkronSimkeu() {
            let tahunId = $('#tahun_id_sinkron_simkeu').val();
            let prodiId = $('#prodi_id_sinkron_simkeu').val();
            $.ajax({
                type: "GET",
                url: "{{ route('admin.setting.sinkronSimkeuCount') }}",
                data: { tahun_id: tahunId, prodi_id: prodiId },
                dataType: "json",
                success: function(response) {
                    if (response.status) {
                        $('#info_sinkron_simkeu').html(
                            `<span class="badge badge-info">${response.data} data pembayaran</span>`
                        );
                    }
                }
            });
        }

        function sinkronSimkeu() {
            let tahunId   = $('#tahun_id_sinkron_simkeu').val();
            let batchSize = parseInt($('#batch_size_sinkron_simkeu').val()) || 10;
            let prodiId   = $('#prodi_id_sinkron_simkeu').val();

            Swal.fire({
                title: 'Konfirmasi Sinkronisasi',
                text: 'Apakah Anda yakin ingin menyinkronkan semua data pembayaran ke SIMKEU?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#17a2b8',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Sinkronkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    execSinkronSimkeu(tahunId, batchSize, prodiId);
                }
            });
        }

        function execSinkronSimkeu(tahunId, batchSize, prodiId) {
            // Reset UI
            let $btn = $('#form_submit_sinkron_simkeu');
            let $progress = $('#progress_container_sinkron_simkeu');
            let $bar = $('#progress_bar_sinkron_simkeu');

            $btn.attr('disabled', true);
            $btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Sedang menyinkronkan...');
            $progress.removeClass('d-none');
            $bar.css('width', '0%').text('0%');
            $('#progress_success_sinkron_simkeu').text('✓ Berhasil: 0');
            $('#progress_failed_sinkron_simkeu').text('✗ Gagal: 0');
            $('#progress_total_sinkron_simkeu').text('Total: 0/0');

            // Show log table
            $('#table_log_sinkron_simkeu').removeClass('d-none');
            $('#log_sinkron_simkeu_empty').addClass('d-none');

            $.ajax({
                type: "POST",
                url: "{{ route('admin.setting.sinkronSimkeu') }}",
                data: JSON.stringify({
                    tahun_id: tahunId,
                    batch_size: batchSize,
                    prodi_id: prodiId
                }),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                timeout: 0, // no timeout on client
                success: function(response) {
                    if (response.status) {
                        let data = response.data;
                        let total = data.total;
                        let successCount = data.success;
                        let failedCount = data.failed;

                        // Update progress bar
                        $bar.css('width', '100%').text('100%');
                        $bar.removeClass('progress-bar-animated bg-info');
                        $bar.addClass(failedCount > 0 ? 'bg-warning' : 'bg-success');
                        $('#progress_success_sinkron_simkeu').text('✓ Berhasil: ' + successCount);
                        $('#progress_failed_sinkron_simkeu').text('✗ Gagal: ' + failedCount);
                        $('#progress_total_sinkron_simkeu').text('Total: ' + total + '/' + total);

                        // Fill log table
                        data.logs.forEach(function(log) {
                            let statusBadge = log.success
                                ? '<span class="badge badge-success">Berhasil</span>'
                                : '<span class="badge badge-danger">Gagal</span>';
                            let jumlah = new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                minimumFractionDigits: 0
                            }).format(log.jumlah);

                            $('#tbody_log_sinkron_simkeu').append(`
                                <tr class="${log.success ? '' : 'table-danger'}">
                                    <td>${log.no}</td>
                                    <td>${log.nim}</td>
                                    <td>${jumlah}</td>
                                    <td>${statusBadge}</td>
                                    <td><small>${log.message}</small></td>
                                </tr>
                            `);
                        });

                        swalToast(200, `Sinkronisasi selesai: ${successCount} berhasil, ${failedCount} gagal`);
                    } else {
                        swalToast(500, response.message || 'Gagal sinkronisasi');
                    }
                },
                error: function(xhr) {
                    swalToast(500, 'Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
                },
                complete: function() {
                    $btn.attr('disabled', false);
                    $btn.html('<i class="fas fa-cloud-upload-alt mx-2"></i>Sinkron ke SIMKEU');
                }
            });
        }
    </script>
@endpush
