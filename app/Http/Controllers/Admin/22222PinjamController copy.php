<?php

namespace App\Http\Controllers\Admin;

use App\Models\Item;
use App\Models\User;
use App\Models\Pinjam;
use App\Models\PinjamDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;



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

        // Mendapatkan data yang telah difilter
        if ($request->ajax()) {
            // Jika permintaan AJAX, kembalikan data dalam format JSON
            return response()->json($query->get());
        }

        // Jika bukan permintaan AJAX, tampilkan halaman dengan data
        $pinjams = $query->paginate(10); // Menggunakan pagination jika diperlukan

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
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'loan_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:loan_date',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        // Cek ketersediaan stok untuk setiap item
        foreach ($request->items as $item) {
            // Hitung total item yang sudah dipinjam dan disetujui dalam rentang tanggal yang tumpang tindih
            $availableStock = PinjamDetail::where('item_id', $item['item_id'])
                ->whereHas('pinjam', function ($query) use ($request) {
                    $query->where(function($query) use ($request) {
                        // Periksa apakah rentang tanggal tumpang tindih
                        $query->where('loan_date', '<=', $request->return_date)
                              ->where('return_date', '>=', $request->loan_date)
                              ->whereIn('status', ['approved']);
                    });
                })
                ->sum('qty');
        
            // Ambil stok item dari database
            $itemModel = Item::find($item['item_id']);
        
            // Periksa apakah stok mencukupi
            if ($itemModel->stok - $availableStock < $item['qty']) {
                return back()->withErrors("Stok tidak mencukupi untuk item: " . $itemModel->nama_item);
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

    


    public function edit($id)
    {
        $pinjam = Pinjam::with('details.item')->findOrFail($id);
        $pinjams = Pinjam::all();
        $items = Item::all(); // Semua item untuk modal
        $users = User::all(); // Semua user untuk dropdown
        return view('admin.pinjams.edit', compact('pinjam', 'items', 'users'));
    }


    public function update(Request $request, $id)
    {
        // Periksa apakah items kosong
        if (empty($request->items) || count($request->items) === 0) {
            return back()->withErrors('Harap tambahkan setidaknya satu item untuk dipinjam.');
        }

        $pinjam = Pinjam::findOrFail($id);

        if($request->status == 'approved'){
            
                // Cek ketersediaan stok untuk setiap item
            foreach ($request->items as $item) {
                // Hitung total item yang sudah dipinjam dan disetujui dalam rentang tanggal yang tumpang tindih
                $availableStock = PinjamDetail::where('item_id', $item['item_id'])
                    ->whereHas('pinjam', function ($query) use ($request) {
                        $query->where(function($query) use ($request) {
                            // Periksa apakah rentang tanggal tumpang tindih
                            $query->where('loan_date', '<=', $request->return_date)
                                ->where('return_date', '>=', $request->loan_date)
                                ->whereIn('status', ['approved']);
                        });
                    })
                    ->sum('qty');
            
                // Ambil stok item dari database
                $itemModel = Item::find($item['item_id']);
            
                // Periksa apakah stok mencukupi
                if ($itemModel->stok - $availableStock < $item['qty']) {
                    return back()->withErrors("Stok tidak mencukupi untuk item: " . $itemModel->nama_item);
                }
            }    
        }

         
        
        // Update data pinjam (loan_date, return_date, dsb)
        $pinjam->update([
            'loan_date' => $request->loan_date,
            'return_date' => $request->return_date,
            'actual_return_date' => $request->actual_return_date,
            'keterangan_peminjam' => $request->keterangan_peminjam,
            'keterangan_penyetuju' => $request->keterangan_penyetuju,
            'status' => $request->status
        ]);

        
    
        if (is_null($pinjam->actual_return_date) && $pinjam->status == 'returned') {
            $pinjam->update([
                'actual_return_date' => now(),
            ]);
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


    public function destroy($id)
    {
        $pinjam = Pinjam::findOrFail($id);
        $pinjam->details()->delete(); // Hapus detail peminjaman terkait
        $pinjam->delete(); // Hapus peminjaman utama

        return redirect()->route('admin.pinjams.index')->with('success', 'Peminjaman berhasil dihapus');
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
                // Hitung total item yang sudah dipinjam dan disetujui dalam rentang tanggal yang tumpang tindih
                $availableStock = PinjamDetail::where('item_id', $detail->item_id)
                    ->whereHas('pinjam', function ($query) use ($pinjam) {
                        $query->where(function($query) use ($pinjam) {
                            // Periksa apakah rentang tanggal tumpang tindih
                            $query->where('loan_date', '<=', $pinjam->return_date)
                                ->where('return_date', '>=', $pinjam->loan_date)
                                ->whereIn('status', ['approved']);
                        });
                    })
                    ->sum('qty');
                
                // Ambil stok item dari database
                $item = Item::find($detail->item_id);
    
                // Periksa apakah stok mencukupi
                if ($item->stok - $availableStock < $detail->qty) {
                    return back()->withErrors("Stok tidak mencukupi untuk item: " . $item->nama_item);
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
        if ($request->filled('keterangan_penyetuju')) {
            $pinjam->keterangan_penyetuju = $request->keterangan_penyetuju;
        }
    
        $pinjam->save();
    
        return response()->json(['success' => true, 'message' => 'Status berhasil diperbarui']);
    }
    


}
