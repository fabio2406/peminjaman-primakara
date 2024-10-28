<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PinjamDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Category;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PinjamDetailsExport;



class ReportController extends Controller
{
    public function reports()
    {
        // Fetch all pinjam details with related data
        $pinjamDetails = PinjamDetail::with(['pinjam.user', 'item.category'])
            ->get();
            $categories = Category::all();
            $roles = ['admin', 'peminjam', 'penyetuju'];
        return view('admin.reports.reports', compact('pinjamDetails','categories','roles'));
    }
    
    public function filterReports(Request $request)
    {
        $query = PinjamDetail::with(['pinjam.user', 'item.category']);
    
        // Apply User Filters
        if ($request->searchUserName) {
            $query->whereHas('pinjam.user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->searchUserName . '%');
            });
        }
        if ($request->searchUserUsername) {
            $query->whereHas('pinjam.user', function ($q) use ($request) {
                $q->where('username', 'like', '%' . $request->searchUserUsername . '%');
            });
        }
        if ($request->searchUserPhone) {
            $query->whereHas('pinjam.user', function ($q) use ($request) {
                $q->where('phone', 'like', '%' . $request->searchUserPhone . '%');
            });
        }
        if ($request->roleFilter && $request->roleFilter !== 'all') {
            $query->whereHas('pinjam.user', function ($q) use ($request) {
                $q->where('role', $request->roleFilter);
            });
        }
    
        // Apply Item Filters
        if ($request->searchItemCode) {
            $query->whereHas('item', function ($q) use ($request) {
                $q->where('kode_item', 'like', '%' . $request->searchItemCode . '%');
            });
        }
        if ($request->searchItemName) {
            $query->whereHas('item', function ($q) use ($request) {
                $q->where('nama_item', 'like', '%' . $request->searchItemName . '%');
            });
        }
        if ($request->category && $request->category !== 'all') {
            $query->whereHas('item.category', function ($q) use ($request) {
                $q->where('id', $request->category);
            });
        }
    
        // Apply Date Filters
        if ($request->loan_date_start && $request->loan_date_end) {
            $query->whereHas('pinjam', function ($q) use ($request) {
                $q->whereBetween('loan_date', [$request->loan_date_start, $request->loan_date_end]);
            });
        }
        if ($request->return_date_start && $request->return_date_end) {
            $query->whereHas('pinjam', function ($q) use ($request) {
                $q->whereBetween('return_date', [$request->return_date_start, $request->return_date_end]);
            });
        }
    
        // Apply Status Filter
        if ($request->status_filter && $request->status_filter !== 'all') {
            $query->whereHas('pinjam', function ($q) use ($request) {
                $q->where('status', $request->status_filter);
            });
        }

        if ($request->status_warek_filter && $request->status_warek_filter !== 'all') {
            $query->whereHas('pinjam', function ($q) use ($request) {
                $q->where('status_warek', $request->status_warek_filter);
            });
        }
    
        // Apply Additional Text Filters
        if ($request->searchKeteranganPeminjam) {
            $query->whereHas('pinjam', function ($q) use ($request) {
                $q->where('keterangan_peminjam', 'like', '%' . $request->searchKeteranganPeminjam . '%');
            });
        }
        if ($request->searchKeteranganPenyetuju) {
            $query->whereHas('pinjam', function ($q) use ($request) {
                $q->where('keterangan_penyetuju', 'like', '%' . $request->searchKeteranganPenyetuju . '%');
            });
        }
    
        // Get the filtered results
        $pinjamDetails = $query->get();
    
        // Return the results as JSON
        return response()->json($pinjamDetails);
    }

    public function export(Request $request)
    {
        $filters = $request->all(); // Get all filter parameters from the request
        return Excel::download(new PinjamDetailsExport($filters), 'reports.xlsx');
    }
}
