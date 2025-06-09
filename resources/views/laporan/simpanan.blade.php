@extends('layouts.app')

@section('title', 'Laporan Simpanan per Anggota')

@section('styles')
<style>
    body {
        background-color: #EDECFF;
    }

    .container-custom {
        background: white;
        padding: 20px;
        border-radius: 10px;
    }

    table thead th {
        background-color: #9288BC;
        color: white;
        text-align: center; 
        vertical-align: middle; 
    }

    table tbody td:first-child { 
        text-align: center;
    }

    table tbody td.text-center .btn {
        margin: 0 2px; 
    }


    .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
        color: white;
    }

    .btn-purple:hover {
        background-color: #5936a7;
        border-color: #5936a7;
        color: white;
    }

    h2, .text-dark {
        color: black !important;
    }

    .dataTables_wrapper .pagination .page-item.active .page-link {
        background-color: #e0e0e0 !important; 
        border-color: #c0c0c0 !important;   
        color: #4a4a4a !important;             
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .dataTables_wrapper .pagination .page-item.active .page-link:hover {
        background-color: #d0d0d0 !important; 
        border-color: #b0b0b0 !important;
        color: #3a3a3a !important;
    }

    .dataTables_wrapper .pagination .page-item:not(.active) .page-link:hover {
        background-color: #f0f0f0 !important; 
        color: #333 !important; 
    }

    .dataTables_empty {
        color: #6c757d !important;
        font-weight: normal !important;
    }
</style>
@endsection

@section('content')
<div class="container container-custom mt-4">
    <h2 class="mb-4 font-weight-bold">Laporan Simpanan</h2>

    {{-- Form Filter --}}
    <form action="{{ route('laporan.simpanan') }}" method="GET" class="mb-3" id="filterForm">
        <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
            <input type="date" name="tanggal_dari" id="tanggal_dari" value="{{ request('tanggal_dari') }}"
                class="form-control"
                placeholder="Dari"
                style="width: 170px; padding: 8px 12px; font-size: 16px;">

            <input type="date" name="tanggal_sampai" id="tanggal_sampai" value="{{ request('tanggal_sampai') }}"
                class="form-control"
                placeholder="Sampai"
                style="width: 170px; padding: 8px 12px; font-size: 16px;">

            <button type="submit" class="btn btn-purple d-flex align-items-center justify-content-center"
                style="height: 38px; width: 170px; font-size: 16px;">Tampilkan</button>
        </div>
    </form>

    {{-- Export Global --}}
    <div class="mb-3" style="margin-top: 50px;">
        <a href="{{ route('laporan.simpanan.pdf', request()->query()) }}" target="_blank" class="btn btn-purple mr-2">
            <i class="bi bi-upload"></i> Export ke PDF
        </a>
        <a href="{{ route('laporan.simpanan.excel', request()->query()) }}" class="btn btn-purple">
            <i class="bi bi-file-earmark-spreadsheet-fill"></i> Export ke Excel
        </a>
    </div>

    <div class="table-responsive">
        <table id="laporan-simpanan" class="table table-bordered table-hover text-dark">
            <thead class="text-white">
                <tr>
                    <th style="text-align: center;">No</th>
                    <th style="text-align: center;">Nama Anggota</th>
                    <th style="text-align: center;">Total Simpanan</th>
                    <th style="text-align: center;">Aksi</th> 
                </tr>
            </thead>
            <tbody class="text-dark">
                @forelse ($anggotaWithSimpanan as $anggota)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $anggota->nama ?? '-' }}</td>
                    <td>Rp {{ number_format($anggota->simpanan_sum_jumlah ?? 0, 0, ',', '.') }}</td>
                    <td class="text-center"> {{-- TAMBAHKAN KELAS text-center DI SINI --}}
                        <a href="{{ route('laporan.simpanan.detail', $anggota->id_anggota) }}" class="btn btn-purple btn-sm">Detail</a>
                        <a href="{{ route('laporan.simpanan.unduhPdf', $anggota->id_anggota) }}" class="btn btn-purple btn-sm" target="_blank">Unduh PDF</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">Tidak ada data simpanan anggota ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
    });
</script>
@endsection