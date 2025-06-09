<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Angsuran;
use App\Models\Anggota;
use App\Models\Pinjaman;
use Carbon\Carbon;

class AngsuranController extends Controller
{
    public function index()
    {
        $angsurans = Angsuran::with(['anggota', 'pinjaman'])

            ->join('pinjaman', 'angsuran.id_pinjaman', '=', 'pinjaman.id_pinjaman') 
            ->orderBy('pinjaman.created_at', 'desc') 
            ->select('angsuran.*') 
            ->get();

  
        $angsuransByPinjaman = $angsurans->groupBy('id_pinjaman')->map(function ($group) {
            $firstAngsuran = $group->first();
            return [
                'id_pinjaman' => $firstAngsuran->id_pinjaman,
                'anggota_nama' => $firstAngsuran->anggota->nama ?? '-',
                'total_jumlah_angsuran' => $group->sum('jumlah_angsuran'),
                'tenor' => $firstAngsuran->pinjaman->tenor ?? 0,
                'jumlah_angsuran_terbayar' => $group->count(),
                'details' => $group->sortBy('tanggal')->values()->toArray(),
            ];
        });

       
        return view('angsuran.index', compact('angsuransByPinjaman', 'angsurans'));
    }

    public function create()
    {
       
        $anggotaAktif = Anggota::where('status', 'aktif');
        $anggotaTidakAktifPinjaman = Anggota::where('status', 'tidak aktif')
            ->whereIn('id_anggota', function ($query) {
                $query->select('id_anggota')
                    ->from('pinjaman')
                    ->where('status', 'Belum Lunas');
            });

        $anggotas = $anggotaAktif->union($anggotaTidakAktifPinjaman)->get();

        $last = Angsuran::orderBy('created_at', 'desc')->first();
        $lastNumber = $last ? intval(substr($last->id_angsuran, 1)) : 0;
        $newId = 'A' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return view('angsuran.create', compact('anggotas', 'newId'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'id_angsuran'        => 'required|unique:angsuran,id_angsuran',
            'id_anggota'         => 'required|exists:anggota,id_anggota',
            'tanggal'            => 'required|date',
            'jumlah_angsuran'    => 'required|numeric|min:0',
        ]);

        $pinjaman = Pinjaman::where('id_anggota', $request->id_anggota)
            ->where('status', 'Belum Lunas')
            ->first();

        if (!$pinjaman) {
            return redirect()->route('angsuran.index')->withErrors(['error' => 'Anggota ini tidak memiliki pinjaman yang belum lunas.']);
        }

        $tenor = $pinjaman->tenor;
        $jumlahAngsuran = Angsuran::where('id_pinjaman', $pinjaman->id_pinjaman)->count();
        $bulanKe = $jumlahAngsuran + 1;

        $pinjamanTanggal = Carbon::parse($pinjaman->tanggal_pinjaman);
        $angsuranStartDate = $pinjamanTanggal->copy()->addMonth();
        $selectedDate = Carbon::parse($request->tanggal);

        if ($selectedDate->lt($angsuranStartDate) || $selectedDate->gt($angsuranStartDate->copy()->addMonths($tenor - 1))) {
            return redirect()->route('angsuran.index')->withErrors(['error' => 'Tanggal angsuran harus berada di antara bulan pertama dan terakhir pinjaman.']);
        }

        $alreadyExists = Angsuran::where('id_pinjaman', $pinjaman->id_pinjaman)
            ->whereMonth('tanggal', $selectedDate->month)
            ->whereYear('tanggal', $selectedDate->year)
            ->exists();

        if ($alreadyExists) {
            return redirect()->route('angsuran.index')->withErrors(['error' => 'Angsuran bulan ini sudah dibayar untuk pinjaman ini.']);
        }

        Angsuran::create([
            'id_angsuran'        => $request->id_angsuran,
            'id_anggota'         => $request->id_anggota,
            'id_pinjaman'        => $pinjaman->id_pinjaman,
            'jumlah_angsuran'    => $request->jumlah_angsuran,
            'tanggal'            => $request->tanggal,
        ]);

        $jumlahAngsuran = Angsuran::where('id_pinjaman', $pinjaman->id_pinjaman)->count();
        if ($jumlahAngsuran >= $tenor) {
            $pinjaman->update(['status' => 'Lunas']);
        }

        return redirect()->route('angsuran.index')->with('success', 'Data angsuran berhasil ditambahkan');
    }

    public function cekAngsuranBulan(Request $request)
    {
        $id_anggota = $request->query('id_anggota');
        $tanggal = $request->query('tanggal');
        $id_pinjaman = $request->query('id_pinjaman');

        if (!$id_anggota || !$tanggal || !$id_pinjaman) {
            return response()->json(['exists' => false]);
        }

        $tanggal = Carbon::parse($tanggal);

        $angsuranExist = Angsuran::where('id_anggota', $id_anggota)
            ->where('id_pinjaman', $id_pinjaman)
            ->whereMonth('tanggal', $tanggal->month)
            ->whereYear('tanggal', $tanggal->year)
            ->exists();

        return response()->json(['exists' => $angsuranExist]);
    }


    public function getTotalAngsuran()
    {
        $totalAngsuran = Angsuran::sum('jumlah_angsuran');
        return response()->json(['total_angsuran' => (int) $totalAngsuran]);
    }

    public function getLastAngsuran(Request $request)
    {
        $id_anggota = $request->query('id_anggota');
        $id_pinjaman = $request->query('id_pinjaman');

        if (!$id_anggota || !$id_pinjaman) {
            return response()->json(null);
        }

        $lastAngsuran = Angsuran::where('id_anggota', $id_anggota)
            ->where('id_pinjaman', $id_pinjaman)
            ->orderBy('tanggal', 'desc')
            ->first();

        if (!$lastAngsuran) {
            return response()->json(null);
        }

        return response()->json([
            'jumlah_angsuran' => $lastAngsuran->jumlah_angsuran,
            'tanggal' => $lastAngsuran->tanggal->format('Y-m-d'),
        ]);
    }
}