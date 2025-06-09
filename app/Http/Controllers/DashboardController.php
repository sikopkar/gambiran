<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Anggota;
use App\Models\Simpanan;
use App\Models\Pinjaman;
use App\Models\Angsuran;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function getDashboardData()
    {
        $activeMembersCount = Anggota::where('status', 'aktif')->count();
        $totalSimpanan = Simpanan::sum('jumlah');
        $totalPinjaman = Pinjaman::sum('jumlah');
        $totalAngsuran = Angsuran::sum('jumlah');

        return response()->json([
            'activeMembersCount' => $activeMembersCount,
            'totalSimpanan' => $totalSimpanan,
            'totalPinjaman' => $totalPinjaman,
            'totalAngsuran' => $totalAngsuran,
        ]);
    }

    public function getChartData()
    {
        $tahun = date('Y');

        $bulanNama = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des'
        ];

        $initMonthlyArray = function () {
            return array_fill(1, 12, 0);
        };

        $simpananDataRaw = DB::table('simpanan')
            ->selectRaw('MONTH(tanggal) as month, SUM(jumlah) as total')
            ->whereYear('tanggal', $tahun)
            ->groupBy('month')
            ->pluck('total', 'month'); 

        $pinjamanDataRaw = DB::table('pinjaman')
            ->selectRaw('MONTH(tanggal_pinjaman) as month, SUM(jumlah) as total')
            ->whereYear('tanggal_pinjaman', $tahun)
            ->groupBy('month')
            ->pluck('total', 'month');

        $anggotaDataRaw = DB::table('anggota')
            ->selectRaw('MONTH(tanggal_daftar) as month, COUNT(*) as total')
            ->whereYear('tanggal_daftar', $tahun)
            ->groupBy('month')
            ->pluck('total', 'month');

        $simpananData = $initMonthlyArray();
        $pinjamanData = $initMonthlyArray();
        $anggotaData = $initMonthlyArray();

        foreach ($simpananDataRaw as $month => $total) {
            $simpananData[$month] = (float) $total;
        }

        foreach ($pinjamanDataRaw as $month => $total) {
            $pinjamanData[$month] = (float) $total;
        }

        foreach ($anggotaDataRaw as $month => $total) {
            $anggotaData[$month] = (int) $total;
        }

        $formatData = function ($dataArray) use ($bulanNama) {
            $result = [];
            for ($i = 1; $i <= 12; $i++) {
                $result[] = [
                    'month' => $bulanNama[$i],
                    'total' => $dataArray[$i] ?? 0
                ];
            }
            return $result;
        };

        $kasMasuk = DB::table('simpanan')
            ->join('anggota', 'simpanan.id_anggota', '=', 'anggota.id_anggota') 
            ->select('anggota.nama', DB::raw('SUM(simpanan.jumlah) as total'))
            ->groupBy('simpanan.id_anggota', 'anggota.nama')
            ->get();


        $donutData = [
            'anggota' => Anggota::count(),
            'pendapatan' => Simpanan::sum('jumlah'),
            'angsuran' => Angsuran::sum('jumlah_angsuran'),
            'pinjaman' => Pinjaman::sum('jumlah')
        ];

        return response()->json([
            'labels' => array_values($bulanNama), 
            'simpanan' => $formatData($simpananData),
            'pinjaman' => $formatData($pinjamanData),
            'anggota' => $formatData($anggotaData),
            'kas_masuk' => $kasMasuk,
            'donut' => $donutData,
        ]);
    }

    public function getKreditMacet()
    {
        try {
            $kreditMacet = DB::table('pinjaman')
                ->join('anggota', 'pinjaman.id_anggota', '=', 'anggota.id_anggota')
                ->select('anggota.nama', DB::raw('SUM(pinjaman.jumlah) as total'))
                ->where('anggota.status', 'tidak aktif')
                ->where('pinjaman.status', 'Belum Lunas')
                ->groupBy('anggota.nama')
                ->havingRaw('total > 0')
                ->get();

            $formatted = $kreditMacet->map(function ($item) {
                $item->total = 'Rp ' . number_format($item->total, 0, ',', '.');
                return $item;
            });
            return response()->json($kreditMacet);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getTotalBunga()
    {
        try {
            $totalBungaAccrued = 0;
            $bunga_per_bulan_rate = 0.0125; 
            $pinjaman = Pinjaman::has('angsuran')->get();

            foreach ($pinjaman as $pinjam) {
                $sisa_pokok_saat_ini = $pinjam->jumlah; 
                $angsuranDibayar = Angsuran::where('id_pinjaman', $pinjam->id_pinjaman)
                                        ->orderBy('tanggal')
                                        ->get();

                foreach ($angsuranDibayar as $angs) {
                    if ($sisa_pokok_saat_ini <= 0) {
                        break; 
                    }

                    $bunga_terhutang_periode_ini = round ($sisa_pokok_saat_ini * $bunga_per_bulan_rate);
                    $pembayaran_angsuran_aktual = $angs->jumlah_angsuran;
                    $bunga_yang_terbayar_dari_angsuran_ini = min($pembayaran_angsuran_aktual, $bunga_terhutang_periode_ini);
                    $totalBungaAccrued += $bunga_yang_terbayar_dari_angsuran_ini;
                    $pokok_yang_terbayar_dari_angsuran_ini = $pembayaran_angsuran_aktual - $bunga_yang_terbayar_dari_angsuran_ini;
                    $sisa_pokok_saat_ini -= $pokok_yang_terbayar_dari_angsuran_ini;

                    if ($sisa_pokok_saat_ini < 0) {
                        $sisa_pokok_saat_ini = 0;
                    }
                }
            }

            
            $formattedBunga = number_format($totalBungaAccrued, 0, ',', '.');

            return response()->json([
                'total_bunga' => $formattedBunga
            ]);

        } catch (\Exception $e) {
            \Log::error("Error in getTotalBunga: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    /*
    public function getTotalPotonganAsuransi()
    {
        try {
            $totalPotonganAsuransi = Pinjaman::sum(DB::raw('jumlah * 0.02'));
            $formattedAsuransi = number_format($totalPotonganAsuransi, 0, ',', '.');

            return response()->json([
                'total_potongan_asuransi' => $formattedAsuransi
            ]);
        } catch (\Exception $e) {
            \Log::error("Error in getTotalPotonganAsuransi: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    */
}
