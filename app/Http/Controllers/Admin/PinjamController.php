<?php

namespace App\Http\Controllers\Admin;

use App\Models\Item;
use App\Models\User;
use App\Models\Pinjam;
use App\Models\PinjamDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;

class PinjamController extends Controller
{
    public function index(Request $request)
    {
        // Mengambil data peminjaman
        $query = Pinjam::with('user'); // Memuat relasi user

        // Pencarian berdasarkan ID, User, dan Keterangan
        if ($request->has('search_all') && !empty($request->search_all)) {
            $search = $request->search_all;
            $query->where(function($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function($query) use ($search) {
                      $query->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhere('keterangan_peminjam', 'LIKE', "%{$search}%")
                  ->orWhere('keterangan_penyetuju', 'LIKE', "%{$search}%");
            });
        }

        // Filter berdasarkan tanggal peminjaman
        if ($request->has('loan_date_start') && $request->loan_date_start) {
            $query->where('loan_date', '>=', $request->loan_date_start);
        }
        if ($request->has('loan_date_end') && $request->loan_date_end) {
            $query->where('loan_date', '<=', $request->loan_date_end);
        }

        // Filter berdasarkan tanggal pengembalian
        if ($request->has('return_date_start') && $request->return_date_start) {
            $query->where('return_date', '>=', $request->return_date_start);
        }
        if ($request->has('return_date_end') && $request->return_date_end) {
            $query->where('return_date', '<=', $request->return_date_end);
        }

        // Filter berdasarkan status
        if ($request->has('status_filter') && $request->status_filter) {
            $query->where('status', $request->status_filter);
        }

        if ($request->has('status_warek_filter') && $request->status_warek_filter) {
            $query->where('status_warek', $request->status_warek_filter);
        }

           // Pagination
        $pinjams = $query->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $pinjams->items(),
                'pagination' => (string) $pinjams->links('pagination::bootstrap-5')
            ]);
        }

        return view('admin.pinjams.index', compact('pinjams'));
    }

    public function create()
    {
        $items = Item::all(); // Ambil semua item untuk ditampilkan
        $users = User::all(); // Ambil semua pengguna untuk ditampilkan di dropdown
        return view('admin.pinjams.create', compact('items', 'users')); // Kirimkan ke view
    }

    public function store(Request $request)
    {
        $this->validateStoreRequest($request);

        // Cek ketersediaan stok untuk setiap item
        foreach ($request->items as $item) {
            // Cek ketersediaan stok untuk item yang dipinjam
            if (!$this->checkItemAvailability($item, $request)) {
                return back()->withErrors("Stok tidak mencukupi untuk item: " . Item::find($item['item_id'])->nama_item);
            }
        }    

        // Proses penyimpanan peminjaman
        $pinjam = Pinjam::create([
            'user_id' => $request->user_id,
            'loan_date' => $request->loan_date,
            'return_date' => $request->return_date,
            'status' => 'pending', // Default status
            'keterangan_peminjam'=> $request->keterangan_peminjam
        ]);

        // Simpan detail peminjaman
        foreach ($request->items as $item) {
            PinjamDetail::create([
                'pinjam_id' => $pinjam->id,
                'item_id' => $item['item_id'],
                'qty' => $item['qty'],
            ]);
        }

        return redirect()->route('admin.pinjams.index')->with('success', 'Peminjaman berhasil dibuat');
    }

    public function getAvailableItems(Request $request) : JsonResponse
    {
        $loanDate = $request->query('loan_date');
        $returnDate = $request->query('return_date');
    
        // Ambil semua item yang akan diperiksa
        $items = Item::all();
        $availableStocks = [];
    
        foreach ($items as $item) {
            // Hitung stok yang sedang dipinjam dengan status approved
            $borrowedQtyApproved = $this->getBorrowedQtyApproved($item, $loanDate, $returnDate);
        
            // Hitung stok yang tersedia
            $availableStocks[$item->id] = max(0, $item->stok - $borrowedQtyApproved);
        }
    
        return response()->json($availableStocks); // Mengembalikan stok yang tersedia dalam format JSON
    }

    public function edit($id)
    {
        $pinjam = Pinjam::with('details.item')->findOrFail($id);
        $items = Item::all(); // Semua item untuk modal
        $users = User::all(); // Semua user untuk dropdown
        $noPeminjam = User::where('id',  $pinjam->user_id )->first();
        $noAdmin = User::where('username', 'admin')->first();
        $peminjamWhatsappNumber = $noPeminjam ? $noPeminjam->phone : null;
        $adminWhatsappNumber = $noAdmin ? $noAdmin->phone : null;
        return view('admin.pinjams.edit', compact('pinjam', 'items', 'users','adminWhatsappNumber','peminjamWhatsappNumber','noPeminjam'));
    }

    public function update(Request $request, $id)
    {
        // Periksa apakah items kosong
        if (empty($request->items) || count($request->items) === 0) {
            return back()->withErrors('Harap tambahkan setidaknya satu item untuk dipinjam.');
        }

        $pinjam = Pinjam::findOrFail($id);

        // Jika status disetujui, cek ketersediaan stok
        if ($request->status == 'approved') {
            foreach ($request->items as $item) {
                // Cek ketersediaan stok untuk item yang dipinjam
                if (!$this->checkItemAvailability($item, $request)) {
                    return back()->withErrors("Stok tidak mencukupi untuk item: " . Item::find($item['item_id'])->nama_item);
                }
            }    
        }

        // Update data pinjam (loan_date, return_date, dsb)
        $pinjam->update([
            'user_id' => $request->user_id,
            'loan_date' => $request->loan_date,
            'return_date' => $request->return_date,
            'actual_return_date' => $request->actual_return_date,
            'keterangan_peminjam' => $request->keterangan_peminjam,
            'keterangan_penyetuju' => $request->keterangan_penyetuju,
            'status_warek' => $request->status_warek,
            'status' => $request->status,
        ]);

        // Jika peminjaman sudah dikembalikan, update tanggal pengembalian aktual
        if (is_null($pinjam->actual_return_date) && $pinjam->status == 'returned') {
            $pinjam->update(['actual_return_date' => now()]);
        }

        // Menghapus item yang dihapus oleh user
        if ($request->filled('deleted_items')) {
            $deletedItems = explode(',', $request->deleted_items);
            PinjamDetail::whereIn('item_id', $deletedItems)->where('pinjam_id', $pinjam->id)->delete();
        }

        // Update atau tambahkan item baru
        foreach ($request->items as $item) {
            PinjamDetail::updateOrCreate(
                ['pinjam_id' => $pinjam->id, 'item_id' => $item['item_id']],
                ['qty' => $item['qty']]
            );
        }

        return redirect()->route('admin.pinjams.index')->with('success', 'Data peminjaman berhasil diperbarui');
    }
    public function updateStatus(Request $request, $id, $status)
    {
        $pinjam = Pinjam::findOrFail($id);
        
        // Jika status diubah menjadi "approved", lakukan pengecekan stok
        if ($status == 'approved') {
            // Ambil semua item yang terkait dengan peminjaman ini
            $pinjamDetails = $pinjam->details;
    
            // Cek stok untuk setiap item
            foreach ($pinjamDetails as $detail) {
                // Cek ketersediaan stok untuk item yang dipinjam
                if (!$this->checkItemAvailabilityForDetail($detail, $pinjam)) {
                    return back()->withErrors("Stok tidak mencukupi untuk item: " . Item::find($detail->item_id)->nama_item);
                }
            }
        }
    
        // Ubah status peminjaman
        $pinjam->status = $status;
    
        // Jika status berubah menjadi "returned", set actual_return_date
        if ($status == 'returned') {
            $pinjam->actual_return_date = now();
        }
    
        // Update keterangan penyetuju jika ada
        if ($request->has('keterangan_penyetuju')) {
            $pinjam->keterangan_penyetuju = $request->keterangan_penyetuju;
        }
    
        $pinjam->save(); // Simpan perubahan

        
        return redirect()->route('admin.pinjams.index')->with('success', 'Status peminjaman berhasil diperbarui');
    }

    public function destroy($id)
    {
        $pinjam = Pinjam::findOrFail($id);
        $pinjam->details()->delete(); // Hapus detail peminjaman terkait
        $pinjam->delete(); // Hapus peminjaman utama

        return redirect()->route('admin.pinjams.index')->with('success', 'Peminjaman berhasil dihapus');
    }

    

    private function validateStoreRequest($request)
    {
        // Validasi permintaan penyimpanan
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'loan_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:loan_date',
            'items' => 'required|array|min:1', // Setidaknya satu item harus ada
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);
    }

    private function checkItemAvailability($item, $request)
    {
        // Cek ketersediaan item berdasarkan tanggal pinjam
        $borrowedQty = PinjamDetail::where('item_id', $item['item_id'])
            ->whereHas('pinjam', function($query) use ($request) {
                $query->where('status', 'approved') // Hanya ambil yang disetujui
                      ->where(function($q) use ($request) {
                          $q->whereBetween('loan_date', [$request->loan_date, $request->return_date])
                            ->orWhereBetween('return_date', [$request->loan_date, $request->return_date]);
                      });
            })
            ->sum('qty'); // Total qty yang sedang dipinjam

        // Ketersediaan item
        return ($borrowedQty + $item['qty']) <= Item::find($item['item_id'])->stok; // Cek ketersediaan stok
    }

    private function getBorrowedQtyApproved($item, $loanDate, $returnDate)
    {
        // Hitung total qty yang sedang dipinjam untuk item
        return PinjamDetail::where('item_id', $item->id)
            ->whereHas('pinjam', function($query) use ($loanDate, $returnDate) {
                $query->where('status', 'approved') // Hanya yang disetujui
                      ->where(function($q) use ($loanDate, $returnDate) {
                          $q->whereBetween('loan_date', [$loanDate, $returnDate])
                            ->orWhereBetween('return_date', [$loanDate, $returnDate]);
                      });
            })
            ->sum('qty'); // Total qty yang sedang dipinjam
    }

    private function checkItemAvailabilityForDetail($detail, $pinjam)
    {
        // Cek ketersediaan item untuk detail yang sudah ada
        $borrowedQty = PinjamDetail::where('item_id', $detail->item_id)
            ->whereHas('pinjam', function($query) use ($pinjam) {
                $query->where('status', 'approved')
                ->where(function($q) use ($pinjam) {
                    $q->whereBetween('loan_date', [$pinjam->loan_date, $pinjam->return_date])
                      ->orWhereBetween('return_date', [$pinjam->loan_date, $pinjam->return_date]);
                })->where('id', '<>', $pinjam->id); // Tidak termasuk peminjaman ini
            })
            ->sum('qty'); // Total qty yang sedang dipinjam

        // Cek ketersediaan stok
        return ($borrowedQty + $detail->qty) <= Item::find($detail->item_id)->stok;
    }

    public function print($id)
    {
        $pinjam = Pinjam::with(['user', 'details.item'])->findOrFail($id);
        $pdf = Pdf::loadView('admin.pinjams.print', compact('pinjam'))->setOption([
            'fontDir' => public_path('/fonts'),
            'fontCache' => public_path('/fonts'),
            'defaultFont' => 'XDPrime Bold'
        ]);
        return $pdf->download("Peminjaman_{$pinjam->id}.pdf");
    }

}
