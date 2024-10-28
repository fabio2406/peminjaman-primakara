@extends('layouts.admin')

@section('title', 'Create Pinjam')

@section('content')
<h1>Create Peminjaman</h1>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form action="{{ route('admin.pinjam.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="user_id" class="form-label">User</label>
        <select name="user_id" id="user_id" class="form-select">
            @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="loan_date" class="form-label">Loan Date</label>
        <input type="date" name="loan_date" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="return_date" class="form-label">Return Date</label>
        <input type="date" name="return_date" class="form-control" required>
    </div>

    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal">
        Tambah Item
    </button>

    <h3>Items to Borrow</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Stock Available</th>
                <th>Qty</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="item-list">
            <!-- Item yang dipilih akan ditambahkan di sini -->
        </tbody>
    </table>

    <button type="submit" class="btn btn-success">Submit</button>
</form>

<!-- Modal untuk memilih item -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalLabel">Pilih Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Stock Available</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td>{{ $item->nama_item }}</td>
                                <td>{{ $item->stok }}</td>
                                <td>
                                    <button class="btn btn-success add-item" data-id="{{ $item->id }}" data-name="{{ $item->nama_item }}" data-stock="{{ $item->stok }}">Pilih</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
$(document).ready(function() {
    // Tambahkan item ke tabel ketika tombol "Pilih" ditekan
    $('.add-item').on('click', function() {
        let itemId = $(this).data('id');
        let itemName = $(this).data('name');
        let itemStock = $(this).data('stock');

        // Tambahkan baris baru di tabel item-list
        $('#item-list').append(`
            <tr>
                <td>${itemName}</td>
                <td>${itemStock}</td>
                <td><input type="number" name="items[${itemId}][qty]" min="1" class="qty-input" data-stock="${itemStock}" required></td>
                <td><button type="button" class="btn btn-danger remove-item">Hapus</button></td>
                <input type="hidden" name="items[${itemId}][item_id]" value="${itemId}">
            </tr>
        `);

        // Tutup modal
        $('#itemModal').modal('hide');
    });

    // Hapus item dari tabel
    $('#item-list').on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
    });

    // Cek stok saat qty diinput
    $('#item-list').on('input', '.qty-input', function() {
        let qty = $(this).val();
        let stockAvailable = $(this).data('stock');

        // Jika qty lebih dari stok, tampilkan alert
        if (qty > stockAvailable) {
            alert(`Stok tidak mencukupi untuk item ini. Stok tersedia: ${stockAvailable}`);
            $(this).val(''); // Reset nilai input qty
        }
    });
});
</script>
@endsection
