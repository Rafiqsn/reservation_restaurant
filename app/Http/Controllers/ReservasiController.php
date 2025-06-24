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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class ReservasiController extends Controller
{
        public function daftarReservasi()
    {
        try {
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
                'message' => 'Data reservasi berhasil diambil.',
                'data' => $reservasi,
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil daftar reservasi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data reservasi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


        public function konfirmasi($reservasi_id)
    {
        try {
            $reservasi = Reservation::findOrFail($reservasi_id);
            $reservasi->status = 'dikonfirmasi';
            $reservasi->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Reservasi berhasil dikonfirmasi.',
                'data' => [
                    'id' => $reservasi->id,
                    'status' => $reservasi->status,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reservasi tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Gagal mengkonfirmasi reservasi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengkonfirmasi reservasi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


        public function batalkan($reservasi_id)
    {
        try {
            $reservasi = Reservation::findOrFail($reservasi_id);

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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reservasi tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Gagal membatalkan reservasi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat membatalkan reservasi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

        public function GetNotaRiwayat($reservasi_id)
    {
        try {
            $reservasi = Reservation::with(['user', 'restaurant', 'reservationMenus.menu'])->findOrFail($reservasi_id);

            // Generate nomor_reservasi jika belum ada
            if (empty($reservasi->nomor_reservasi)) {
                $reservasi->nomor_reservasi = strtoupper(substr(md5($reservasi->id . now()), 0, 6));
            }

            // Hitung total harga
            $totalHarga = $reservasi->reservationMenus->sum(function ($item) {
                return $item->menu->harga * $item->jumlah;
            });

            // Simpan jika ada perubahan
            if ($reservasi->isDirty('nomor_reservasi') || $reservasi->total_harga !== $totalHarga) {
                $reservasi->total_harga = $totalHarga;
                $reservasi->save();
            }

            // Format tanggal
            $tanggalFormatted = \Carbon\Carbon::parse($reservasi->tanggal)->translatedFormat('l d F, Y');

            return response()->json([
                'status' => 'success',
                'message' => 'Nota berhasil diambil.',
                'nota' => [
                    'reservasi_id' => $reservasi->id,
                    'restoran_id' => $reservasi->restoran_id,
                    'kursi_id' => $reservasi->kursi_id,
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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reservasi tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil nota reservasi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil nota reservasi.',
                'error' => $e->getMessage(),
            ], 500);
        }
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

    public function pesanreservasi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'restoran_id'    => 'required|uuid|exists:restoran,id',
            'tanggal'        => 'required|date',
            'jam'            => 'required|date_format:H:i',
            'jumlah_orang'   => 'required|integer|min:1',
            'kursi_id'       => 'required|uuid|exists:kursi,id',
            'menu'           => 'required|array|min:1',
            'menu.*.menu_id' => 'required|uuid|exists:menu,id',
            'menu.*.jumlah'  => 'required|integer|min:1',
            'catatan'        => 'nullable|string',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Validasi gagal. Periksa input Anda.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Unauthorized. Silakan login.'], 401);
        }

        try {
            DB::beginTransaction();

            $restoran = Restaurant::findOrFail($request->restoran_id);

            // Validasi kursi
            $kursi = Table::where('id', $request->kursi_id)
                        ->where('restoran_id', $restoran->id)
                        ->first();

            if (!$kursi) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Kursi tidak valid untuk restoran ini.'
                ], 422);
            }

            if ($request->jumlah_orang > $kursi->kapasitas) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Jumlah orang melebihi kapasitas kursi. Maksimum kapasitas kursi adalah ' . $kursi->kapasitas . ' orang.',
                ], 422);
            }

            // Cek bentrok jadwal kursi
            $waktuMulai   = Carbon::parse($request->jam);
            $waktuSelesai = (clone $waktuMulai)->addHours(2);

            $bentrok = Reservation::where('restoran_id', $restoran->id)
                ->where('tanggal', $request->tanggal)
                ->where('kursi_id', $request->kursi_id)
                ->where(function ($query) use ($waktuMulai, $waktuSelesai) {
                    $query->whereTime('waktu', '<', $waktuSelesai->format('H:i'))
                        ->whereRaw("ADDTIME(waktu, '2:00') > ?", [$waktuMulai->format('H:i')]);
                })
                ->exists();

            if ($bentrok) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Kursi sudah dipesan pada waktu tersebut.'
                ], 422);
            }

            // Simpan reservasi
            $reservasi = Reservation::create([
                'id'              => Str::uuid(),
                'pengguna_id'     => $user->id,
                'restoran_id'     => $restoran->id,
                'kursi_id'        => $request->kursi_id,
                'tanggal'         => $request->tanggal,
                'waktu'           => $request->jam,
                'jumlah_orang'    => $request->jumlah_orang,
                'status'          => 'menunggu',
                'catatan'         => $request->catatan,
                'nomor_reservasi' => strtoupper(substr(md5(Str::uuid()), 0, 6)),
            ]);

            $totalHarga = 0;

            // Tambahkan menu yang dipesan
            foreach ($request->menu as $item) {
                $menu = Menu::findOrFail($item['menu_id']);
                $subtotal = $menu->harga * $item['jumlah'];
                $totalHarga += $subtotal;

                ReservationMenu::create([
                    'id'            => Str::uuid(),
                    'reservasi_id'  => $reservasi->id,
                    'menu_id'       => $menu->id,
                    'jumlah'        => $item['jumlah'],
                    'subtotal'      => $subtotal,
                ]);
            }

            $reservasi->total_harga = $totalHarga;
            $reservasi->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Reservasi berhasil dibuat.',
                'data'    => $reservasi->load('menu.menu', 'kursi', 'restaurant')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Gagal membuat reservasi: ' . $e->getMessage(), [
                'status' => 'Error',
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
                'input' => $request->all()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => 'Terjadi kesalahan saat memproses reservasi.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


            public function PesananSaya()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User belum login',
                    'error' => 'Unauthorized',
                ], 401);
            }

            $reservasiMenunggu = Reservation::with(['restaurant', 'reservationMenus.menu'])
                ->where('pengguna_id', $user->id)
                ->where('status', 'menunggu')
                ->get()
                ->map(function ($reservasi) {
                    $totalHarga = $reservasi->reservationMenus->sum(function ($item) {
                        return $item->menu->harga * $item->jumlah;
                    });

                    return [
                        'reservasi_id' => $reservasi->id,
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
                'message' => 'Data reservasi menunggu berhasil diambil',
                'data' => $reservasiMenunggu,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data reservasi menunggu',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    public function riwayatpesanan()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User belum login',
                    'error' => 'Unauthorized',
                ], 401);
            }

            $reservasiSemua = Reservation::with(['restaurant', 'reservationMenus.menu'])
                ->where('pengguna_id', $user->id)
                ->get()
                ->map(function ($reservasi) {
                    $totalHarga = $reservasi->reservationMenus->sum(function ($item) {
                        return $item->menu->harga * $item->jumlah;
                    });

                    return [
                        'reservasi_id' => $reservasi->id,
                        'restoran_id' => $reservasi->restoran_id,
                        'id_reservasi' => $reservasi->nomor_reservasi ?? substr($reservasi->id, 0, 5),
                        'foto_restoran' => $reservasi->restaurant->foto ?? null,
                        'nama_restoran' => $reservasi->restaurant->nama ?? '-',
                        'tanggal' => \Carbon\Carbon::parse($reservasi->tanggal)->translatedFormat('l, d F Y'),
                        'jumlah_orang' => $reservasi->jumlah_orang,
                        'total_harga' => 'Rp ' . number_format($totalHarga, 0, ',', '.'),
                        'status' => $reservasi->status,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'message' => 'Data riwayat pesanan berhasil diambil',
                'data' => $reservasiSemua,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data riwayat pesanan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
