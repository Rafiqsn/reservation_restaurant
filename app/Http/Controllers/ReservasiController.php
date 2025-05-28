<?php
namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Restaurant;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
            'status' => 'required|in:dikonfirmasi,dibatalkan,menunggu'
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
        // Validasi request
        $request->validate([
            'restoran_id'   => 'required|uuid|exists:restoran,id',
            'tanggal'       => 'required|date',
            'jam'           => 'required|date_format:H:i',
            'jumlah_orang'  => 'required|integer|min:1',
        ]);

        $user = Auth::user();

        // Ambil data restoran
        $restoran = Restaurant::find($request->restoran_id);
        if (!$restoran) {
            return response()->json([
                'message' => 'Restoran tidak ditemukan.'
            ], 404);
        }

        // Validasi apakah kursi milik restoran tersebut
        $kursi = Table::where('id', $request->kursi_id)
                    ->where('restoran_id', $restoran->id)
                    ->first();

        if (!$kursi) {
            return response()->json([
                'message' => 'Kursi tidak ditemukan atau tidak milik restoran ini.'
            ], 404);
        }

        // Simpan ke database
        $reservasi = Reservation::create([
            'id'            => Str::uuid(),
            'pengguna_id'   => $user->id,
            'restoran_id'   => $restoran->id,
            'kursi_id'      => $kursi->id,
            'tanggal'       => $request->tanggal,
            'waktu'         => $request->jam,
            'jumlah_orang'  => $request->jumlah_orang,
            'status'        => 'menunggu',
        ]);

        return response()->json([
            'message' => 'Reservasi berhasil disimpan.',
            'data' => $reservasi
        ]);
    }


}
