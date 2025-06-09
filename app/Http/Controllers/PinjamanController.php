<?php

namespace App\Http\Controllers;

use App\Models\Pinjaman;
use App\Models\Anggota;
use Illuminate\Http\Request;


class PinjamanController extends Controller
{
    public function index()
    {
        $pinjamans = Pinjaman::with('anggota')
                            ->orderBy('tanggal_pinjaman', 'desc')
                            ->get();

        return view('pinjaman.index', compact('pinjamans'));
    }

    public function create()
    {
        $anggotas = Anggota::where('status', 'aktif')->get();

        $last = Pinjaman::orderBy('created_at', 'desc')->first();
        $lastNumber = $last ? intval(substr($last->id_pinjaman, 1)) : 0;  
        $newId = 'P' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return view('pinjaman.create', compact('anggotas', 'newId'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'id_pinjaman'       => 'required|unique:pinjaman,id_pinjaman',
            'id_anggota'        => 'required|exists:anggota,id_anggota',
            'jumlah'            => 'required|numeric',
            'tenor'             => 'required|integer',
            'tanggal_pinjaman'  => 'required|date',
            'status'            => 'required|in:Belum Lunas,Lunas',
        ]);

        $anggota = Anggota::find($request->id_anggota);
        
        $pinjamanBelumLunas = $anggota->pinjamans()->where('status', 'Belum Lunas')->exists();

        if ($pinjamanBelumLunas) {
            return redirect()->back()->withErrors(['error' => 'Anggota ini masih memiliki pinjaman yang statusnya belum lunas.']);
        }

        Pinjaman::create([
            'id_pinjaman'       => $request->id_pinjaman,
            'id_anggota'        => $request->id_anggota,
            'jumlah'            => $request->jumlah,
            'tenor'             => $request->tenor,
            'tanggal_pinjaman'  => $request->tanggal_pinjaman,
            'status'            => $request->status,
        ]);

        return redirect()->route('pinjaman.index')->with('success', 'Data pinjaman berhasil ditambahkan');
    }

    public function convertToWords($number)
    {
        $words = [
            0 => 'Nol', 1 => 'Satu', 2 => 'Dua', 3 => 'Tiga', 4 => 'Empat', 5 => 'Lima',
            6 => 'Enam', 7 => 'Tujuh', 8 => 'Delapan', 9 => 'Sembilan', 10 => 'Sepuluh',
            11 => 'Sebelas', 12 => 'Dua belas', 13 => 'Tiga belas', 14 => 'Empat belas',
            15 => 'Lima belas', 16 => 'Enam belas', 17 => 'Tujuh belas', 18 => 'Delapan belas',
            19 => 'Sembilan belas', 20 => 'Dua puluh', 30 => 'Tiga puluh', 40 => 'Empat puluh',
            50 => 'Lima puluh', 60 => 'Enam puluh', 70 => 'Tujuh puluh', 80 => 'Delapan puluh',
            90 => 'Sembilan puluh'
        ];

        if ($number <= 20) {
            return $words[$number];
        }

        $tens = floor($number / 10) * 10;
        $ones = $number % 10;

        return $words[$tens] . ' ' . $words[$ones];
    }

    public function simulasi()
    {
        return view('pinjaman.simulasi');
    }

    public function hasil(Request $request)
    {
        $request->validate([
            'jumlah' => 'required|numeric',
            'tenor' => 'required|integer',
        ]);

        $jumlahPinjaman = $request->jumlah;
        $tenor = $request->tenor; 
        $bungaPerBulan = 0.0125; 

        $angsuranPokok = $jumlahPinjaman / $tenor; 
        $angsuran = []; 
        $bunga = []; 
        $sisaPinjaman = $jumlahPinjaman; 

        for ($i = 1; $i <= $tenor; $i++) {
            $bungaBulanIni = $sisaPinjaman * $bungaPerBulan; 
            $angsuranBulanIni = $angsuranPokok + $bungaBulanIni; 

            $angsuran[] = $angsuranBulanIni;
            $bunga[] = $bungaBulanIni;

            $sisaPinjaman -= $angsuranPokok;
        }

        $bulanKe = [];
        for ($i = 1; $i <= $tenor; $i++) {
            $bulanKe[] = $this->convertToWords($i); 
        }

        return view('pinjaman.hasil', compact('angsuran', 'bunga', 'bulanKe', 'tenor'));
    }

    public function bukti($id)
    {
        $pinjaman = Pinjaman::with('anggota')->findOrFail($id);

        return view('pinjaman.bukti', compact('pinjaman'));
    }

    public function cekStatusPinjaman($id_anggota)
    {
        $anggota = Anggota::findOrFail($id_anggota);
        $pinjamanBelumLunas = $anggota->pinjamans()->where('status', 'Belum Lunas')->exists();

        return response()->json([
            'status' => $pinjamanBelumLunas
        ]);
    }

    public function getPinjamanDetails($id)
    {
        $pinjaman = Pinjaman::with('anggota')->findOrFail($id);
        return response()->json($pinjaman);
    }

    public function getPinjamanData($id_anggota)
    {
        $anggota = Anggota::find($id_anggota);
        if (!$anggota) {
            return response()->json(['error' => 'Anggota tidak ditemukan.'], 404);
        }

        $pinjaman = Pinjaman::where('id_anggota', $id_anggota)
                            ->where('status', 'Belum Lunas')   
                            ->first();

        if ($pinjaman) {
            return response()->json([
                'jumlah' => $pinjaman->jumlah,
                'tenor' => $pinjaman->tenor,
                'tanggal_pinjaman' => $pinjaman->tanggal_pinjaman,
                'status' => $pinjaman->status,
            ]);
        }

        return response()->json(['error' => 'Pinjaman tidak ditemukan.'], 404);
    }
    
    public function getPinjamanAktif($id_anggota)
    {
        $pinjaman = Pinjaman::where('id_anggota', $id_anggota)
                            ->where('status', 'Belum Lunas')
                            ->first();

        if ($pinjaman) {
            return response()->json([
                'id_pinjaman' => $pinjaman->id_pinjaman,
                'jumlah' => $pinjaman->jumlah,
                'tenor' => $pinjaman->tenor,
                'tanggal_pinjaman' => $pinjaman->tanggal_pinjaman,
            ]);
        }

        return response()->json([]);
    }


    public function getTotalPinjaman()
    {
        $totalPinjaman = Pinjaman::sum('jumlah');
        return response()->json(['total_pinjaman' => (int) $totalPinjaman]);
    }
}
