<?php

namespace App\Http\Controllers\Penyetuju;

use App\Models\Item;
use App\Models\User;
use App\Models\Pinjam;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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

        return view('penyetuju.pinjams.index', compact('pinjams'));
    }

    public function updateStatus(Request $request, $id)
    {
        $pinjam = Pinjam::findOrFail($id);
        $pinjam->status_warek = $request->status_warek;
        $pinjam->save();

        return redirect()->back()->with('success', 'Status updated successfully!');
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
        return view('penyetuju.pinjams.edit', compact('pinjam', 'items', 'users','adminWhatsappNumber','peminjamWhatsappNumber','noPeminjam'));
    }

    public function update(Request $request, $id)
    {

        $pinjam = Pinjam::findOrFail($id);

        // Update data pinjam (loan_date, return_date, dsb)
        $pinjam->update([
            'status_warek' => $request->status_warek
        ]);

        return redirect()->route('penyetuju.pinjams.index')->with('success', 'Data peminjaman berhasil diperbarui');
    }

}
