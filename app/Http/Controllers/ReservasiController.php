<?php
namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

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

}
