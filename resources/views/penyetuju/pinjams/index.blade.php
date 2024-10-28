@extends('layouts.penyetuju')

@section('title', 'List Peminjaman')

@section('content')
<h1>List Peminjaman</h1>

<!-- Form Pencarian -->
<div class="mb-3">
    <input type="text" id="search_all" class="form-control" placeholder="Cari ID, User, atau Keterangan">
</div>
<!-- Filter Tanggal dan Status -->

<label class="form-label flex-fill">Tanggal Peminjaman</label>
<div class="d-flex mb-3">
        <input type="date" id="loan_date_start" class="form-control flex-fill me-2" placeholder="Tanggal Pinjam Dari">
        <input type="date" id="loan_date_end" class="form-control flex-fill me-2" placeholder="Tanggal Pinjam Sampai">
</div>
<label class="form-label flex-fill">Tanggal Pengembalian</label>
<div class="d-flex mb-3">
    <input type="date" id="return_date_start" class="form-control flex-fill me-2" placeholder="Tanggal Kembali Dari">
    <input type="date" id="return_date_end" class="form-control flex-fill me-2" placeholder="Tanggal Kembali Sampai">
</div>
<div class="d-flex mb-3">
    <div class="flex-fill me-2">
        <select id="status_filter" class="form-select">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="returned">Returned</option>
        </select>
    </div>
    <div class="flex-fill me-2">
        <select id="status_warek_filter" class="form-select">
            <option value="">All Status Warek II</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-bordered" id="pinjams-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Tanggal Peminjaman</th>
                <th>Tanggal Pengembalian</th>
                <th>Tanggal Pengembalian (real)</th>
                <th>Status DPT</th>
                <th>Keterangan peminjam</th>
                <th>Keterangan Penyetuju</th>
                <th>Status Warek II</th>
                <th >Actions</th>
            </tr>
        </thead>
        <tbody id="pinjams_body">
            @foreach($pinjams as $pinjam)
                <tr>
                    <td>{{ $pinjam->id }}</td>
                    <td>{{ $pinjam->user->name }}</td>
                    <td>{{ $pinjam->loan_date }}</td>
                    <td>{{ $pinjam->return_date }}</td>
                    <td>{{ $pinjam->actual_return_date }}</td>
                    <td>
                        @if($pinjam->status == 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @elseif($pinjam->status == 'approved')
                            <span class="badge bg-success">Approved</span>
                        @elseif($pinjam->status == 'rejected')
                            <span class="badge bg-danger">Rejected</span>
                        @elseif($pinjam->status == 'returned')
                            <span class="badge bg-primary">Returned</span>
                        @endif
                    </td>
                    <td>{{ $pinjam->keterangan_peminjam }}</td>
                    <td>{{ $pinjam->keterangan_penyetuju }}</td>
                    <td>
                        @if($pinjam->status_warek == 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @elseif($pinjam->status_warek == 'approved')
                            <span class="badge bg-success">Approved</span>
                        @elseif($pinjam->status_warek == 'rejected')
                            <span class="badge bg-danger">Rejected</span>
                        @endif
                    </td>
                    <td class="d-flex justify-content-between">
                            <a href="{{ route('penyetuju.pinjams.edit', $pinjam->id) }}" class="btn btn-warning mx-1">Edit</a> 
                        @if($pinjam->status_warek == 'pending')
                            <!-- Approve Button -->
                            <form action="{{ route('penyetuju.pinjams.update-status', $pinjam->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status_warek" value="approved">
                                <button type="submit" class="btn btn-success mx-1">Approve</button>
                            </form>

                            <!-- Reject Button -->
                            <form action="{{ route('penyetuju.pinjams.update-status', $pinjam->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status_warek" value="rejected">
                                <button type="submit" class="btn btn-danger mx-1">Reject</button>
                            </form>
                        @elseif($pinjam->status_warek == 'approved' || $pinjam->status_warek == 'rejected')
                            <!-- Set to Pending Button -->
                            <form action="{{ route('penyetuju.pinjams.update-status', $pinjam->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status_warek" value="pending">
                                <button type="submit" class="btn btn-secondary mx-1">Set to Pending</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="pagination-container">
    {!! $pinjams->links('pagination::bootstrap-5') !!}
</div>
@endsection
@section('scripts')
<script>
$(document).ready(function() {
    $('#search_all, #loan_date_start, #loan_date_end, #return_date_start, #return_date_end, #status_filter, #status_warek_filter').on('input change', function() {
        filterPinjams();
    });

    function filterPinjams(url = "{{ route('penyetuju.pinjams.index') }}") {
        let search_all = $('#search_all').val();
        let loan_date_start = $('#loan_date_start').val();
        let loan_date_end = $('#loan_date_end').val();
        let return_date_start = $('#return_date_start').val();
        let return_date_end = $('#return_date_end').val();
        let status_filter = $('#status_filter').val();
        let status_warek_filter = $('#status_warek_filter').val();

        $.ajax({
            url: url,
            method: "GET",
            data: {
                search_all: search_all,
                loan_date_start: loan_date_start,
                loan_date_end: loan_date_end,
                return_date_start: return_date_start,
                return_date_end: return_date_end,
                status_filter: status_filter,
                status_warek_filter: status_warek_filter
            },
            success: function(data) {
                let rows = '';
                data.data.forEach(pinjam => {
                    rows += `
                        <tr>
                            <td>${pinjam.id}</td>
                            <td>${pinjam.user.name}</td>
                            <td>${pinjam.loan_date}</td>
                            <td>${pinjam.return_date}</td>
                            <td>${pinjam.actual_return_date || ''}</td>
                            <td>
                                <span class="badge bg-${getStatusClass(pinjam.status)}">${pinjam.status.charAt(0).toUpperCase() + pinjam.status.slice(1)}</span>
                            </td>
                            <td>${pinjam.keterangan_peminjam || ''}</td>
                            <td>${pinjam.keterangan_penyetuju || ''}</td>
                            <td>
                                <span class="badge bg-${getStatusClass2(pinjam.status_warek)}">${pinjam.status_warek.charAt(0).toUpperCase() + pinjam.status_warek.slice(1)}</span>
                            </td>
                                <td class="d-flex justify-content-between">
                                    <a href="/penyetuju/pinjams/${pinjam.id}/edit" class="btn btn-warning mx-1">Edit</a>
                                    ${pinjam.status_warek === 'pending' ? `
                                        <form action="/penyetuju/pinjams/${pinjam.id}/update-status" method="POST" style="display:inline-block;">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input type="hidden" name="_method" value="PUT">
                                            <input type="hidden" name="status_warek" value="approved">
                                            <button type="submit" class="btn btn-success mx-1">Approve</button>
                                        </form>
                                        <form action="/penyetuju/pinjams/${pinjam.id}/update-status" method="POST" style="display:inline-block;">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input type="hidden" name="_method" value="PUT">
                                            <input type="hidden" name="status_warek" value="rejected">
                                            <button type="submit" class="btn btn-danger mx-1">Reject</button>
                                        </form>` 
                                    : `<form action="/penyetuju/pinjams/${pinjam.id}/update-status" method="POST" style="display:inline-block;">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input type="hidden" name="_method" value="PUT">
                                            <input type="hidden" name="status_warek" value="pending">
                                            <button type="submit" class="btn btn-secondary mx-1">Set to Pending</button>
                                        </form>`}
                                </td>
                        </tr>
                    `;
                });
                $('#pinjams_body').html(rows);
                $('.pagination-container').html(data.pagination);
            },
            error: function(xhr) {
                console.error(xhr);
            }
        });
    }

    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        let url = $(this).attr('href');
        filterPinjams(url); // Pass the URL of the page to fetch
    });

    function getStatusClass(status) {
        switch(status) {
            case 'approved': return 'success';
            case 'rejected': return 'danger';
            case 'returned': return 'primary';
            default: return 'warning';
        }
    }
    function getStatusClass2(status_warek) {
        switch(status_warek) {
            case 'approved': return 'success';
            case 'rejected': return 'danger';
            case 'returned': return 'primary';
            default: return 'warning';
        }
    }
});

</script>
@endsection
