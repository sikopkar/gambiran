<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Pendapatan Bunga</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 5px 30px 30px 30px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #444;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        tfoot td {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .right-align {
            text-align: right;
        }

        .kop {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .kop-logo {
            display: table-cell;
            width: 80px;
            vertical-align: top;
        }
        .kop-logo img {
            width: 70px;
            height: auto;
            margin-top: -12px;
        }
        .kop-text {
            display: table-cell;
            padding-left: 15px;
            vertical-align: top;
        }
        .kop-text h2 {
            margin: -6px;
            font-size: 18px;
            text-transform: uppercase;
        }
        .kop-text p {
            margin: 7px -6px;
            font-size: 12px;
        }

        hr {
            border: none;
            border-top: 2px solid #000;
            margin: 10px 0 20px 0;
        }

        h3 {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <div class="kop">
        <div class="kop-logo">
            <img src="{{ public_path('img/logo.png') }}" alt="Logo RSUD">
        </div>
        <div class="kop-text">
            <h2>RSUD GAMBIRAN KOTA KEDIRI</h2>
            <p>Jl. Kapten Piere Tendean No.16, Kota Kediri, Jawa Timur 64121</p>
            <p>Telp. (0354) 2810000 | Email: rsud.gambiran@kedirikota.go.id </p>
        </div>
    </div>
    <hr>

    <h3><u>Laporan Pendapatan Bunga</u></h2>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Jumlah Pinjaman</th>
                <th>Tenor</th>
                <th>Tanggal Pinjaman</th>
                <th>Total Bunga</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pinjamans as $index => $pinjaman)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $pinjaman->anggota->nama ?? '-' }}</td>
                <td class="right-align">Rp {{ number_format($pinjaman->jumlah, 0, ',', '.') }}</td>
                <td>{{ $pinjaman->tenor }} bulan</td>
                <td>{{ \Carbon\Carbon::parse($pinjaman->tanggal_pinjaman)->format('d/m/Y') }}</td>
                <td class="right-align">Rp {{ number_format(round($pinjaman->total_bunga_hitung ?? 0), 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="right-align">Total Keseluruhan Bunga</td>
                <td class="right-align">Rp {{ number_format(round($totalBunga ?? 0), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
