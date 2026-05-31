<div class="card card-warning" id="card_tes_sinkron_simkeu">
    <div class="card-header">
        <i class="fas fa-flask mr-2"></i> Tes Sinkron SIMKEU (1 Data)
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="maximize">
                <i class="fas fa-expand"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="card-body">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Testing kirim <strong>1 data pembayaran</strong> ke SIMKEU V2.
            Pilih tahun akademik, lalu klik tombol <strong>Kirim</strong> pada data yang ingin dites.
        </div>

        {{-- Filter --}}
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Tahun Akademik</label>
                    <select class="form-control" id="tahun_id_tes_sinkron">
                        @foreach ($tahun as $item)
                            <option value="{{ $item->id }}">{{ $item->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Prodi Jenjang</label>
                    <select class="form-control" id="prodi_id_tes_sinkron">
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
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Cari NIM / Nama</label>
                    <input type="text" class="form-control" id="search_tes_sinkron"
                        placeholder="Ketik NIM atau Nama...">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Per Halaman</label>
                    <select class="form-control" id="per_page_tes_sinkron">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Tabel --}}
        <div id="loading_tes_sinkron" class="text-center text-muted py-3">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data...</p>
        </div>
        <div id="empty_tes_sinkron" class="text-center text-muted py-4 d-none">
            <i class="fas fa-inbox fa-3x mb-2"></i>
            <p>Tidak ada data pembayaran</p>
        </div>
        <div id="wrapper_table_tes_sinkron" class="d-none">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge badge-warning" id="count_tes_sinkron"></span>
                <small class="text-muted" id="showing_tes_sinkron"></small>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 13px;">
                    <thead class="thead-light">
                        <tr>
                            <th width="4%">No</th>
                            <th width="11%">NIM</th>
                            <th>Nama</th>
                            <th width="8%">Prodi</th>
                            <th width="5%">JK</th>
                            <th width="9%">Jenis Bayar</th>
                            <th width="8%">→ SIMKEU</th>
                            <th width="10%">Jumlah</th>
                            <th width="11%">Tanggal</th>
                            <th width="7%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody_tes_sinkron"></tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <small class="text-muted" id="page_info_tes_sinkron"></small>
                <nav><ul class="pagination pagination-sm mb-0" id="pagination_tes_sinkron"></ul></nav>
            </div>
        </div>

        {{-- Result --}}
        <div class="mt-3 d-none" id="result_tes_sinkron">
            <div class="card card-outline mb-0" id="result_card_tes_sinkron">
                <div class="card-header py-2">
                    <h6 class="card-title mb-0" id="result_title_tes_sinkron">Hasil</h6>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" onclick="$('#result_tes_sinkron').addClass('d-none')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="p-2" id="result_sections_tes_sinkron"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
var _tesSinkronPage = 1;
var _tesSinkronTimer = null;
var _jenisBayarMap = @json(\App\Http\Services\SimkeuApp::JENIS_PEMBAYARAN_MAP ?? []);

$(document).ready(function () {
    // Events
    $('#tahun_id_tes_sinkron, #per_page_tes_sinkron, #prodi_id_tes_sinkron').on('change', function () {
        _tesSinkronPage = 1;
        tesSinkronLoad();
    });
    $('#search_tes_sinkron').on('keyup', function () {
        clearTimeout(_tesSinkronTimer);
        _tesSinkronTimer = setTimeout(function () {
            _tesSinkronPage = 1;
            tesSinkronLoad();
        }, 400);
    });
    tesSinkronLoad();
});

function tesSinkronMapJenis(val) {
    if (!val) return 'cash';
    var key = val.toLowerCase().trim();
    return _jenisBayarMap[key] || 'cash';
}

function tesSinkronLoad(page) {
    if (page) _tesSinkronPage = page;
    $('#loading_tes_sinkron').removeClass('d-none');
    $('#empty_tes_sinkron, #wrapper_table_tes_sinkron').addClass('d-none');

    $.get("{{ route('admin.setting.tesSinkronSimkeuList') }}", {
        tahun_id: $('#tahun_id_tes_sinkron').val(),
        prodi_id: $('#prodi_id_tes_sinkron').val(),
        per_page: $('#per_page_tes_sinkron').val(),
        search: $('#search_tes_sinkron').val(),
        page: _tesSinkronPage
    }, function (res) {
        $('#loading_tes_sinkron').addClass('d-none');
        if (!res.status || !res.data || res.data.length === 0) {
            $('#empty_tes_sinkron').removeClass('d-none');
            return;
        }
        var pg = res.pagination;
        $('#count_tes_sinkron').text(pg.total + ' data');
        $('#showing_tes_sinkron').text(pg.from + ' - ' + pg.to + ' dari ' + pg.total);
        $('#wrapper_table_tes_sinkron').removeClass('d-none');

        var $tb = $('#tbody_tes_sinkron').empty();
        $.each(res.data, function (i, d) {
            var no = (pg.from || 1) + i;
            var jk = d.jenis_kelamin === 'Laki-Laki' ? 'L' : 'P';
            var mapped = tesSinkronMapJenis(d.jenis_pembayaran);
            $tb.append(
                '<tr id="row_tes_' + d.id + '">' +
                '<td class="text-center">' + no + '</td>' +
                '<td><strong>' + d.nim + '</strong></td>' +
                '<td>' + d.nama + '</td>' +
                '<td>' + (d.prodi_alias || d.prodi_nama) + '</td>' +
                '<td class="text-center">' + jk + '</td>' +
                '<td>' + (d.jenis_pembayaran || '-') + '</td>' +
                '<td><span class="badge badge-info">' + mapped + '</span></td>' +
                '<td class="text-right">' + d.jumlah_display + '</td>' +
                '<td>' + d.tanggal_display + '</td>' +
                '<td class="text-center">' +
                    '<button class="btn btn-xs btn-warning" onclick="tesSinkronKirim(' + d.id + ',this)">' +
                    '<i class="fas fa-paper-plane"></i></button>' +
                '</td></tr>'
            );
        });
        tesSinkronPagination(pg);
    }).fail(function () {
        $('#loading_tes_sinkron').addClass('d-none');
        $('#empty_tes_sinkron').removeClass('d-none');
    });
}

function tesSinkronPagination(pg) {
    var $ul = $('#pagination_tes_sinkron').empty();
    $('#page_info_tes_sinkron').text('Hal ' + pg.current_page + '/' + pg.last_page);
    if (pg.last_page <= 1) return;

    var items = '';
    items += '<li class="page-item ' + (pg.current_page === 1 ? 'disabled' : '') + '">' +
             '<a class="page-link" href="#" data-p="' + (pg.current_page - 1) + '">&laquo;</a></li>';

    var s = Math.max(1, pg.current_page - 2), e = Math.min(pg.last_page, pg.current_page + 2);
    if (s > 1) {
        items += '<li class="page-item"><a class="page-link" href="#" data-p="1">1</a></li>';
        if (s > 2) items += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    for (var i = s; i <= e; i++) {
        items += '<li class="page-item ' + (i === pg.current_page ? 'active' : '') + '">' +
                 '<a class="page-link" href="#" data-p="' + i + '">' + i + '</a></li>';
    }
    if (e < pg.last_page) {
        if (e < pg.last_page - 1) items += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        items += '<li class="page-item"><a class="page-link" href="#" data-p="' + pg.last_page + '">' + pg.last_page + '</a></li>';
    }
    items += '<li class="page-item ' + (pg.current_page === pg.last_page ? 'disabled' : '') + '">' +
             '<a class="page-link" href="#" data-p="' + (pg.current_page + 1) + '">&raquo;</a></li>';

    $ul.html(items);
    $ul.find('a.page-link').on('click', function (e) {
        e.preventDefault();
        var p = $(this).data('p');
        if (p >= 1 && p <= pg.last_page) tesSinkronLoad(p);
    });
}

function tesSinkronKirim(id, btn) {
    var $btn = $(btn), orig = $btn.html();
    $btn.attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

    $.ajax({
        type: "POST",
        url: "{{ route('admin.setting.tesSinkronSimkeu') }}",
        data: JSON.stringify({ pembayaran_id: id }),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        complete: function () { $btn.attr('disabled', false).html(orig); },
        success: function (r) { tesSinkronResult(r, id); },
        error: function (xhr) {
            tesSinkronResult({
                status: false,
                message: 'HTTP ' + xhr.status,
                data: xhr.responseJSON || xhr.responseText
            }, id);
        }
    });
}

function tesSinkronResult(r, id) {
    var $res = $('#result_tes_sinkron').removeClass('d-none');
    var $card = $('#result_card_tes_sinkron');
    var $title = $('#result_title_tes_sinkron');
    var $sections = $('#result_sections_tes_sinkron').empty();
    var $row = $('#row_tes_' + id).removeClass('table-success table-danger');

    if (r.status) {
        $card.removeClass('card-danger').addClass('card-success');
        $title.html('<i class="fas fa-check-circle text-success mr-2"></i>Berhasil — ' + (r.payload?.nim || ''));
        $row.addClass('table-success');
        swalToast(200, r.message || 'Berhasil');
    } else {
        $card.removeClass('card-success').addClass('card-danger');
        $title.html('<i class="fas fa-times-circle text-danger mr-2"></i>Gagal');
        $row.addClass('table-danger');
        swalToast(500, r.message || 'Gagal');
    }

    if (r.payload) {
        $sections.append('<small><strong>Payload dikirim:</strong></small>' +
            '<pre class="mb-2 mt-1 p-2 bg-light" style="font-size:12px">' +
            JSON.stringify(r.payload, null, 2) + '</pre>');
    }
    if (r.data) {
        $sections.append('<small><strong>Response SIMKEU:</strong></small>' +
            '<pre class="mb-2 mt-1 p-2 bg-light" style="font-size:12px;max-height:200px;overflow-y:auto">' +
            JSON.stringify(r.data, null, 2) + '</pre>');
    }
    if (r.debug) {
        $sections.append('<small><strong>Debug Info:</strong></small>' +
            '<pre class="mb-0 mt-1 p-2 bg-dark text-white" style="font-size:12px;max-height:300px;overflow-y:auto">' +
            JSON.stringify(r.debug, null, 2) + '</pre>');
    }

    $('html,body').animate({ scrollTop: $res.offset().top - 100 }, 300);
}
</script>
@endpush
