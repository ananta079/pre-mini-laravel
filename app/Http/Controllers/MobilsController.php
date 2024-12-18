<?php

namespace App\Http\Controllers;

use App\Models\Merk;
use App\Models\Mobil;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MobilsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $merk = Merk::all();
        $search = $request->input('search');
        $trashed = $request->input('trashed'); // Menyaring data yang dihapus


        $query = Mobil::query();

        if ($trashed) {
            $query->withTrashed(); // Menyertakan data yang dihapus
        }

        if ($search) {
            $query->search($search); // Pencarian
        }

        $mobil = $query->orderBy('created_at', 'desc')->paginate(4); // Paging

        return view('mobils.index', compact('mobil', 'search', 'merk', 'trashed'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'mobil' => 'required|max:255|',
            'merk' => 'required',
            'kursi' => 'required|max:5',
            'npolisi' => 'required|unique:mobils,nomor_polisi|max:100',
            'tahun' => 'required|min:4',
            'harga_per_hari' => 'required|max:150|min:6',
            'gambar' => 'required|mimes:jpeg,jpg,png,svg|max:2048'
        ], [
            'mobil.required' => 'Nama mobil harus di isi',
            'mobil.unique' => 'Mobil sudah terdaftar',
            'mobil.max'  => 'Maximal karakter adalah 255',
            'merk.required' => 'Merk mobil harus di isi',
            'kursi.required' => 'Kursi mobil harus di isi',
            'kursi.max' => 'Maximal anggka adalah 5',
            'npolisi.required'  => 'Nomor polisi harus di isi',
            'npolisi.unique' => 'Nomor polisi sudah terdaftar',
            'tahun.required' => 'Tahun mobil harus di isi',
            'tahun.min' => 'Minimal jumlah angka adalah 4',
            'harga_per_hari.required' => 'Harga per hari harus di isi',
            'harga_per_hari.min' => 'Minimal jumlah angka adalah 6',
            'gambar.required' => 'Gambar mobil harus ada',
            'gambar.mimes' => 'Format gambar harus sesuai',
            'gambar.max' => 'Ukuran gambar maksimal 2MB',
        ]);

        // Mengambil semua input kecuali file gambar
        $data = $request->except('gambar');

        if ($request->hasFile('gambar')) {
            $image = $request->file('gambar');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/images/mobils', $imageName); // Simpan gambar di folder 'public/images'
            $data['gambar'] = $imageName; // Simpan nama file gambar ke array data
        }

        // Simpan data ke database
        Mobil::create([
            'nama_m' => $validateData['mobil'],
            'merk_id' => $validateData['merk'],
            'kursi' => $validateData['kursi'],
            'nomor_polisi' => $validateData['npolisi'],
            'tahun' => $validateData['tahun'],
            'harga_per_hari' => $validateData['harga_per_hari'],
            'gambar' => $data['gambar'] ?? null // Set null jika tidak ada gambar
        ]);

        return redirect('mobils')->with('success', 'Mobil berhasil ditambah');
    }

    /**
     * Display the specified resource.
     */
    public function show(Mobil $mobil)
    {
        return view('mobils.show', compact('mobil'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mobil $mobil)
    {
        $merk = Merk::all();
        return view('mobils.edit', compact('mobil', 'merk'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mobil $mobil)
    {
        // Validasi input, gambar tidak diwajibkan
        $validateData = $request->validate([
            'mobil' => 'required|unique:mobils,nama_m,' . $mobil->id . '|max:255',
            'merk' => 'required',
            'kursi' => 'required|max:5|min:1',

            'npolisi' => 'required|unique:mobils,nomor_polisi,' . $mobil->id . '|max:100',
            'tahun' => 'required|min:4',
            'harga_per_hari' => 'required|max:150|min:6',
            'gambar' => 'nullable|mimes:jpeg,jpg,png,svg|max:2048'
        ], [
            'mobil.required' => 'Nama mobil harus di isi',
            'mobil.unique' => 'Mobil sudah terdaftar',
            'mobil.max'  => 'Maximal karakter adalah 255',
            'merk.required' => 'Merk mobil harus di isi',
            'kursi.required' => 'Kursi mobil harus di isi',

            'kursi.max' => 'Maximal angka adalah 5',
            'kursi.min' => 'Minimal angka adalah 1',

            'npolisi.required'  => 'Nomor polisi harus di isi',
            'npolisi.unique' => 'Nomor polisi sudah terdaftar',
            'tahun.required' => 'Tahun mobil harus di isi',
            'tahun.min' => 'Minimal jumlah angka adalah 4',
            'harga_per_hari.required' => 'Harga per hari harus di isi',
            'harga_per_hari.min' => 'Minimal jumlah angka adalah 6',
            'gambar.mimes' => 'Format gambar harus sesuai',
            'gambar.max' => 'Ukuran gambar maksimal 2MB',
        ]);

        // Ambil semua input kecuali gambar
        $data = $request->except('gambar');

        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($mobil->gambar) {
                Storage::delete('public/images/mobils' . $mobil->gambar);
            }

            // Simpan gambar baru
            $image = $request->file('gambar');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/images/mobils', $imageName); // Simpan gambar di folder 'public/images'
            $data['gambar'] = $imageName; // Simpan nama file gambar ke array data
        } else {
            // Pertahankan gambar lama jika tidak ada gambar baru
            $data['gambar'] = $mobil->gambar;
        }

        // Perbarui data mobil
        $mobil->update([
            'nama_m' => $validateData['mobil'],
            'merk_id' => $validateData['merk'],
            'kursi' => $validateData['kursi'],
            'nomor_polisi' => $validateData['npolisi'],
            'tahun' => $validateData['tahun'],
            'harga_per_hari' => $validateData['harga_per_hari'],
            'gambar' => $data['gambar'] // Set gambar baru atau lama
        ]);

        return redirect()->route('mobils.index')->with('success', 'Mobil berhasil diubah');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mobil $mobil)
    {

        // Cek apakah mobil sedang disewa
        if ($mobil->pesanan()->exists()) {
            return redirect()->back()->withErrors(['error' => 'Mobil ini sedang disewa dan tidak bisa dihapus']);
        }

        // Hapus gambar dari storage jika ada
        if ($mobil->gambar) {
            Storage::delete('public/images/' . $mobil->gambar);
        }

        // Soft delete mobil
        $mobil->delete();

        return redirect()->route('mobils.index')->with('success', 'Data Mobil berhasil dihapus');

        try {
            // Cek apakah mobil sedang/akan disewa
            if ($mobil->pesanan()->exists()) {
                return redirect()->back()->withErrors(['error' => 'Mobil ini sedang/akan disewa dan tidak bisa dihapus.']);
            }
            if ($mobil->riwayat()->exists()) {
                return redirect()->back()->withErrors(['error' => 'Mobil memiliki data history dan tidak bisa dihapus.']);
            }

            // Jika tidak digunakan, hapus gambar dari storage (jika ada)
            if ($mobil->gambar) {
                Storage::delete('public/images/' . $mobil->gambar);
            }

            // Hapus mobil
            $mobil->delete();

            return redirect()->route('mobils.index')->with('danger', 'Data Mobil berhasil dihapus');
        } catch (\Illuminate\Database\QueryException $ex) {
            // Menangkap kesalahan foreign key dan menampilkan pesan yang ramah pengguna
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus mobil. Pastikan tidak ada pesanan yang terkait.']);
        }
    }
}