<?php
namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Restaurant;
use App\Models\Menu;
use App\Models\ReservationMenu;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;


class ReservasiController extends Controller
{
    public function daftarReservasi()
{
    $reservasi = Reservation::with(['user', 'kursi'])
        ->orderBy('tanggal', 'desc')
        ->orderBy('waktu', 'asc')
        ->get()
        ->map(function ($item) {
            return [
                'id' => $item->id,
                'nama_pemesan' => $item->user->nama ?? '-',
                'tanggal' => \Carbon\Carbon::parse($item->tanggal)->translatedFormat('l, d F Y'),
                'waktu' => $item->waktu,
                'nomor_kursi' => $item->kursi->nomor_kursi ?? 'Belum dipilih',
                'status' => ucfirst($item->status),
            ];
        });

    return response()->json([
        'status' => 'success',
        'data' => $reservasi
    ]);
}

    public function konfirmasi($reservasi_id)
    {
        $reservasi = Reservation::findOrFail($reservasi_id);

        // Update status
        $reservasi->status = 'dikonfirmasi';
        $reservasi->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Reservasi berhasil ditandai sebagai selesai.',
            'data' => [
                'id' => $reservasi->id,
                'status' => $reservasi->status,
            ]
        ]);
    }

        public function batalkan($reservasi_id)
    {
        $reservasi = Reservation::findOrFail($reservasi_id);

        // Optional: validasi status sebelumnya
        if ($reservasi->status === 'dibatalkan') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Reservasi sudah dibatalkan.',
            ], 400);
        }

        $reservasi->status = 'dibatalkan';
        $reservasi->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Reservasi berhasil dibatalkan.',
        ]);
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
        $request->validate([
            'restoran_id'   => 'required|uuid|exists:restoran,id',
            'tanggal'       => 'required|date',
            'jam'           => 'required|date_format:H:i',
            'jumlah_orang'  => 'required|integer|min:1',
            'kursi_id'      => 'nullable|uuid|exists:meja,id',
        ]);

        $user = Auth::user();

        // Validasi restoran & kursi
        $restoran = Restaurant::findOrFail($request->restoran_id);
        $kursi = Table::where('id', $request->kursi_id)
            ->where('restoran_id', $restoran->id)
            ->first();

        if ($request->filled('kursi_id')) {
        $kursi = Table::where('id', $request->kursi_id)
            ->where('restoran_id', $restoran->id)
            ->first();

        if (!$kursi) {
            return response()->json([
                'message' => 'Kursi tidak ditemukan atau tidak milik restoran ini.'
            ], 404);
        }
    }

        // Simpan reservasi awal
        $reservasi = Reservation::create([
            'id'            => Str::uuid(),
            'pengguna_id'   => $user->id,
            'restoran_id'   => $restoran->id,
            'kursi_id'      => $request->kursi_id ?? null,
            'tanggal'       => $request->tanggal,
            'waktu'         => $request->jam,
            'jumlah_orang'  => $request->jumlah_orang,
            'status'        => 'menunggu',
        ]);

        return response()->json([
            'message' => 'Ketersediaan tersedia. Reservasi awal disimpan.',
            'data' => [
                'reservasi_id' => $reservasi->id
            ]
        ]);
    }


    public function GetMenu(Request $request)
{
    $request->validate([
        'restoran_id' => 'required|uuid|exists:restoran,id',
        'jenis'       => 'nullable|in:makanan,minuman',
    ]);

    $query = Menu::where('restoran_id', $request->restoran_id)
                 ->where('status', 'tersedia');

    if ($request->filled('jenis')) {
        $query->where('jenis', $request->jenis);
    }

    $menus = $query->get(['id', 'nama', 'deskripsi', 'jenis', 'harga', 'foto', 'highlight']);

    return response()->json([
        'message' => 'Daftar menu berhasil diambil',
        'data' => $menus,
    ]);
}



        public function PilihMenu(Request $request)
    {
        $request->validate([
            'reservasi_id'   => 'required|uuid|exists:reservasi,id',
            'menu'           => 'required|array|min:1',
            'menu.*.menu_id' => 'required|uuid|exists:menu,id',
            'menu.*.jumlah'  => 'required|integer|min:1',
        ]);

        try {
            $reservasi = Reservation::findOrFail($request->reservasi_id);

            DB::beginTransaction();

            foreach ($request->menu as $item) {
                $menu = Menu::findOrFail($item['menu_id']);
                ReservationMenu::create([
                    'id'            => Str::uuid(),
                    'reservasi_id'  => $reservasi->id,
                    'menu_id'       => $menu->id,
                    'jumlah'        => $item['jumlah'],
                    'subtotal'      => $menu->harga * $item['jumlah'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Menu berhasil ditambahkan ke reservasi.',
                'data' => $reservasi->load('menu.menu')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menambahkan menu ke reservasi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


            public function GetKursi(Request $request)
    {
        $request->validate([
            'reservasi_id' => 'required|uuid|exists:reservasi,id',
        ]);

        $reservasi = Reservation::with('restaurant')->findOrFail($request->reservasi_id);

        $semuaKursi = Table::where('restoran_id', $reservasi->restoran_id)->get();

        $kursiTerpakai = Reservation::where('restoran_id', $reservasi->restoran_id)
            ->where('tanggal', $reservasi->tanggal)
            ->where('waktu', $reservasi->waktu)
            ->where('id', '!=', $reservasi->id)
            ->pluck('kursi_id')
            ->toArray();

        $siapdipakai = $semuaKursi->map(function ($kursi) use ($kursiTerpakai, $reservasi) {
            return [
                'id' => $kursi->id,
                'nomor_kursi' => $kursi->nomor_kursi,
                'kapasitas' => $kursi->kapasitas,
                'tersedia' => !in_array($kursi->id, $kursiTerpakai),
            ];
        });


        return response()->json([
            'status' => 'success',
            'restoran' => $reservasi->restaurant->nama ?? null,
            'tanggal' => $reservasi->tanggal,
            'waktu' => $reservasi->waktu,
            'denah_meja' => $semuaKursi->first()->denah_meja ?? null,
            'siapdipakai' => $siapdipakai,
        ]);
    }




        public function PilihKursi(Request $request)
    {
        $request->validate([
            'reservasi_id' => 'required|uuid|exists:reservasi,id',
            'kursi_id'     => 'required|uuid|exists:kursi,id',
        ]);

        $reservasi = Reservation::findOrFail($request->reservasi_id);

        $sudahDipakai = Reservation::where('restoran_id', $reservasi->restoran_id)
            ->where('tanggal', $reservasi->tanggal)
            ->where('waktu', $reservasi->waktu)
            ->where('kursi_id', $request->kursi_id)
            ->where('id', '!=', $reservasi->id)
            ->exists();

        if ($sudahDipakai) {
            return response()->json([
                'message' => 'Kursi sudah digunakan di waktu tersebut.'
            ], 422);
        }

        $reservasi->kursi_id = $request->kursi_id;
        $reservasi->save();

        return response()->json([
            'message' => 'Kursi berhasil dipilih.',
            'data' => $reservasi->load('kursi')
        ]);
    }



    public function GetNota($id)
    {
        $reservasi = Reservation::with(['user', 'restaurant', 'reservationMenus.menu'])->findOrFail($id);

        // Format tanggal
        $hari = Carbon::parse($reservasi->tanggal)->translatedFormat('l');
        $tanggal = Carbon::parse($reservasi->tanggal)->translatedFormat('d F, Y');

        // Format total harga
        $totalHarga = $reservasi->reservationMenus->sum(function ($item) {
            return $item->menu->harga * $item->jumlah;
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'nama'     => $reservasi->user->nama,
                'no_hp'     => $reservasi->user->no_hp ?? '-', // sesuaikan dengan field
                'email'            => $reservasi->user->email,
                'nama_restoran'    => $reservasi->restaurant->nama,
                'tanggal'          => "$hari, $tanggal",
                'waktu'            => Carbon::parse($reservasi->waktu)->format('H:i') . ' - ' . Carbon::parse($reservasi->waktu)->addMinutes(60)->format('H:i') . ' WIB',
                'jumlah_orang'     => $reservasi->jumlah_orang,
                'total_harga'      => 'Rp ' . number_format($totalHarga, 0, ',', '.'),

            ]
        ]);
    }

        public function tambahCatatan(Request $request)
    {
        $request->validate([
            'reservasi_id' => 'required|uuid|exists:reservasi,id',
            'catatan' => 'nullable|string',
        ]);

        $reservasi = Reservation::with(['user', 'restaurant', 'reservationMenus.menu'])->findOrFail($request->reservasi_id);

        // Generate nomor_reservasi jika belum ada
        if (empty($reservasi->nomor_reservasi)) {
            $reservasi->nomor_reservasi = strtoupper(substr(md5($reservasi->id . now()), 0, 6));
        }

        // Hitung total harga dan simpan ke database
        $totalHarga = $reservasi->reservationMenus->sum(function ($item) {
            return $item->menu->harga * $item->jumlah;
        });
        $reservasi->total_harga = $totalHarga;

        // Simpan catatan
        $reservasi->catatan = $request->catatan;

        $reservasi->save();

        // Format tanggal ke Bahasa Indonesia
        $tanggalFormatted = \Carbon\Carbon::parse($reservasi->tanggal)->translatedFormat('l d F, Y');

        return response()->json([
            'status' => 'success',
            'nota' => [
                'nomor_reservasi' => $reservasi->nomor_reservasi,
                'nama' => $reservasi->user->nama ?? '-',
                'no_hp' => $reservasi->user->no_hp ?? '-',
                'email' => $reservasi->user->email ?? '-',
                'nama_restoran' => $reservasi->restaurant->nama ?? '-',
                'tanggal' => $tanggalFormatted,
                'waktu' => $reservasi->waktu,
                'jumlah_orang' => $reservasi->jumlah_orang,
                'catatan' => $reservasi->catatan,
                'total_harga' => 'Rp ' . number_format($totalHarga, 0, ',', '.'),
            ]
        ]);
    }





        public function PesananSaya()
    {
        $reservasiMenunggu = Reservation::with(['restaurant', 'reservationMenus.menu'])
        ->where('status', 'menunggu') // sesuaikan statusnya
        ->get()
        ->map(function ($reservasi) {
            $totalHarga = $reservasi->reservationMenus->sum(function ($item) {
                return $item->menu->harga * $item->jumlah;
            });

            return [
                'id_reservasi' => $reservasi->nomor_reservasi ?? substr($reservasi->id, 0, 5),
                'foto_restoran' => $reservasi->restaurant->foto ?? null,
                'nama_restoran' => $reservasi->restaurant->nama ?? '-',
                'tanggal' => \Carbon\Carbon::parse($reservasi->tanggal)->translatedFormat('l, d F Y'),
                'jumlah_orang' => $reservasi->jumlah_orang,
                'total_harga' => 'Rp ' . number_format($totalHarga, 0, ',', '.'),
            ];
        });

    return response()->json([
        'status' => 'success',
        'data' => $reservasiMenunggu,
    ]);
    }

        public function GetNotaRiwayat($reservasi_id)
    {
        $reservasi = Reservation::with(['user', 'restaurant', 'reservationMenus.menu'])->findOrFail($reservasi_id);

        // Generate nomor_reservasi jika belum ada
        if (empty($reservasi->nomor_reservasi)) {
            $reservasi->nomor_reservasi = strtoupper(substr(md5($reservasi->id . now()), 0, 6));
        }

        // Hitung total harga
        $totalHarga = $reservasi->reservationMenus->sum(function ($item) {
            return $item->menu->harga * $item->jumlah;
        });

        // Simpan nomor_reservasi dan total_harga jika ada perubahan
        if ($reservasi->isDirty('nomor_reservasi') || $reservasi->total_harga !== $totalHarga) {
            $reservasi->total_harga = $totalHarga;
            $reservasi->save();
        }

        // Format tanggal ke Bahasa Indonesia
        $tanggalFormatted = \Carbon\Carbon::parse($reservasi->tanggal)->translatedFormat('l d F, Y');

        return response()->json([
            'status' => 'success',
            'nota' => [
                'nomor_reservasi' => $reservasi->nomor_reservasi,
                'nama' => $reservasi->user->nama ?? '-',
                'no_hp' => $reservasi->user->no_hp ?? '-',
                'email' => $reservasi->user->email ?? '-',
                'nama_restoran' => $reservasi->restaurant->nama ?? '-',
                'tanggal' => $tanggalFormatted,
                'waktu' => $reservasi->waktu,
                'jumlah_orang' => $reservasi->jumlah_orang,
                'catatan' => $reservasi->catatan,
                'total_harga' => 'Rp ' . number_format($totalHarga, 0, ',', '.'),
                'status' => $reservasi->status,
            ]
        ]);
    }



    public function riwayatpesanan()
    {
        $reservasiSemua = Reservation::with(['restaurant', 'reservationMenus.menu'])
            ->get()
            ->map(function ($reservasi) {
                $totalHarga = $reservasi->reservationMenus->sum(function ($item) {
                    return $item->menu->harga * $item->jumlah;
                });

                return [
                    'id_reservasi' => $reservasi->nomor_reservasi ?? substr($reservasi->id, 0, 5),
                    'foto_restoran' => $reservasi->restaurant->foto ?? null,
                    'nama_restoran' => $reservasi->restaurant->nama ?? '-',
                    'tanggal' => \Carbon\Carbon::parse($reservasi->tanggal)->translatedFormat('l, d F Y'),
                    'jumlah_orang' => $reservasi->jumlah_orang,
                    'total_harga' => 'Rp ' . number_format($totalHarga, 0, ',', '.'),
                    'status' => $reservasi->status, // opsional kalau mau tahu statusnya
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $reservasiSemua,
        ]);
    }

}
