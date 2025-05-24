<?php
namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Table;
use App\Models\Menu;

class ReservasiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user(); // user terautentikasi

        $reservasi = Reservation::with('kursi')
            ->where('pengguna_id', $user->id)
            ->orderBy('tanggal', 'desc')
            ->paginate(10);

        return response()->json($reservasi);
    }

    // Menampilkan detail reservasi tertentu
        public function show($id)
    {
        $reservasi = Reservation::with(['pengguna', 'kursi', 'restoran']) // sesuaikan dengan relasi yang ada
            ->findOrFail($id);

        return response()->json([
            'id' => $reservasi->id,
            'nama' => $reservasi->user->name,
            'no_hp' => $reservasi->user->no_hp,
            'email' => $reservasi->user->email,
            'nama_resto' => $reservasi->resto->nama,
            'tanggal' => $reservasi->tanggal,
            'waktu' => $reservasi->jam_mulai . ' - ' . $reservasi->jam_selesai,
            'orang' => $reservasi->jumlah_orang,
            'catatan' => $reservasi->catatan,
            'status' => $reservasi->status
        ]);
    }


    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:dikonfirmasi,dibatalkan'
        ]);

        $reservasi = Reservation::findOrFail($id);
        $reservasi->status = $request->status;
        $reservasi->save();

        return response()->json([
            'message' => 'Status reservasi diperbarui.',
            'data' => $reservasi
        ]);
    }

    public function cekKetersediaan(Request $request)
    {
        $request->validate([
            'jumlah_orang' => 'required|integer|min:1',
            'tanggal' => 'required|date',
            'waktu' => 'required|date_format:H:i',
        ]);

        $tanggal = $request->tanggal;
        $waktu = $request->waktu;
        $jumlah = $request->jumlah_orang;

        return response()->json([
            'message' => 'Meja tersedia',
            'jumlah_orang' => $jumlah,
            'tanggal' => $tanggal,
            'waktu' => $waktu,

        ]);
    }

        public function getMenu(Request $request)
    {
        $request->validate([
            'restoran_id' => 'required|uuid|exists:restos,id',
            'jumlah_orang' => 'required|integer|min:1',
            'tanggal' => 'required|date',
            'waktu' => 'required|date_format:H:i',
        ]);

        $menus = Menu::where('restoran_id', $request->restoran_id)->get();

        return response()->json([
            'menus' => $menus,
            'jumlah_orang' => $request->jumlah_orang,
            'tanggal' => $request->tanggal,
            'waktu' => $request->waktu
        ]);
    }



    public function tampilkanMenu(Request $request)
    {
        $request->validate([
            'restoran_id' => 'required|uuid|exists:restos,id',
            'jumlah_orang' => 'required|integer|min:1',
            'tanggal' => 'required|date',
            'waktu' => 'required|date_format:H:i',
        ]);

        // Simpan data sesi untuk ke langkah selanjutnya jika perlu
        session([
            'jumlah_orang' => $request->jumlah_orang,
            'tanggal' => $request->tanggal,
            'waktu' => $request->waktu,
            'restoran_id' => $request->restoran_id,
        ]);

        $menus = Menu::where('restoran_id', $request->restoran_id)->get();

        return response()->json([
            'menus' => $menus,
            'jumlah_orang' => $request->jumlah_orang,
            'tanggal' => $request->tanggal,
            'waktu' => $request->waktu
        ]);
    }

}
