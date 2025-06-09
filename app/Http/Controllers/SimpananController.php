<?php

namespace App\Http\Controllers;

use App\Models\Simpanan;
use App\Models\Anggota;
use Illuminate\Http\Request;

class SimpananController extends Controller
{
    public function index()
    {
        $dataSimpanan = Simpanan::with('anggota')
                                ->orderBy('tanggal', 'desc')
                                ->get();

        return view('simpanan.index', compact('dataSimpanan'));
    }

    public function create()
    {
        $anggotas = Anggota::where('status', 'aktif')->get();
        $newId = 'S' . str_pad(Simpanan::count() + 1, 3, '0', STR_PAD_LEFT);

        return view('simpanan.create', compact('anggotas', 'newId'));
    }


    public function store(Request $request)
    {
        $request->merge([
            'jumlah' => str_replace('.', '', $request->jumlah),
        ]);

        // Cek apakah anggota sudah memiliki Simpanan Pokok
        if ($request->jenis_simpanan === 'Simpanan Pokok') {
            $alreadyExists = Simpanan::where('id_anggota', $request->id_anggota)
                                    ->where('jenis_simpanan', 'Simpanan Pokok')
                                    ->exists();

            if ($alreadyExists) {
                return back()
                    ->withInput()
                    ->withErrors(['jenis_simpanan' => 'Anggota ini sudah memiliki Simpanan Pokok.']);
            }
        }

        $validated = $request->validate([
            'id_simpanan'    => 'required|unique:simpanan,id_simpanan',
            'id_anggota'     => 'required|exists:anggota,id_anggota',
            'jenis_simpanan' => 'required|string',
            'jumlah'         => 'required|numeric',
            'tanggal'        => 'required|date',
        ]);

        Simpanan::create($validated);

        return redirect()->route('simpanan.index')
                        ->with('success', 'Data Simpanan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $simpanan  = Simpanan::findOrFail($id);
        $anggotas  = Anggota::all();
        return view('simpanan.edit', compact('simpanan', 'anggotas'));
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'jumlah' => str_replace('.', '', $request->jumlah),
        ]);

        $validated = $request->validate([
            'id_anggota'     => 'required|exists:anggota,id_anggota',
            'jenis_simpanan' => 'required|string',
            'jumlah'         => 'required|numeric',
            'tanggal'        => 'required|date',
        ]);

        $simpanan = Simpanan::findOrFail($id);

        // Cegah update menjadi "Simpanan Pokok" jika sudah pernah dibuat oleh anggota lain
        if ($request->jenis_simpanan === 'Simpanan Pokok') {
            $sudahAda = Simpanan::where('id_anggota', $request->id_anggota)
                                ->where('jenis_simpanan', 'Simpanan Pokok')
                                ->where('id_simpanan', '!=', $simpanan->id_simpanan) // kecuali data ini sendiri
                                ->exists();

            if ($sudahAda) {
                return back()
                    ->withInput()
                    ->withErrors(['jenis_simpanan' => 'Anggota ini sudah memiliki Simpanan Pokok.']);
            }
        }

        $simpanan->update($validated);

        return redirect()->route('simpanan.index')
                        ->with('success', 'Data Simpanan berhasil diperbarui.');
    }


    public function destroy($id)
    {
        Simpanan::findOrFail($id)->delete();

        return redirect()->route('simpanan.index')
                         ->with('success', 'Data Simpanan berhasil dihapus.');
    }
    public function getTotalSimpanan()
    {
        $totalSimpanan = Simpanan::sum('jumlah');
        return response()->json(['total_simpanan' => (int) $totalSimpanan]); 
    }

    public function cekSimpananPokok($id_anggota)
    {
        $sudahAda = Simpanan::where('id_anggota', $id_anggota)
                            ->where('jenis_simpanan', 'Simpanan Pokok')
                            ->exists();

        return response()->json(['sudah_ada' => $sudahAda]);
    }

}