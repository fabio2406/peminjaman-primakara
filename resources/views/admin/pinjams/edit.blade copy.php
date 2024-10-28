@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Edit Peminjaman</h1>
    
    <form action="{{ route('admin.pinjams.update', $pinjam->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="loan_date">Tanggal Peminjaman</label>
            <input type="date" id="loan_date" name="loan_date" class="form-control" value="{{ $pinjam->loan_date }}" required>
        </div>

        <div class="form-group">
            <label for="return_date">Tanggal Pengembalian</label>
            <input type="date" id="return_date" name="return_date" class="form-control" value="{{ $pinjam->return_date }}" required>
        </div>

        <div class="form-group">
            <label for="keterangan_peminjam">Keterangan Peminjam</label>
            <textarea id="keterangan_peminjam" name="keterangan_peminjam" class="form-control" rows="3">{{ $pinjam->keterangan_peminjam }}</textarea>
        </div>

        <div class="form-group">
            <label for="keterangan_penyetuju">Keterangan Penyetuju</label>
            <textarea id="keterangan_penyetuju" name="keterangan_penyetuju" class="form-control" rows="3">{{ $pinjam->keterangan_penyetuju }}</textarea>
        </div>

        <h3>Detail Barang</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Item</th>
                    <th>Stock Available</th>
                    <th>Jumlah Pinjam</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="item-list">
                @foreach($pinjam->details as $detail)
                    <tr>
                        <td>{{ $detail->item->nama_item }}</td>
                        <td class="stock-available" data-item-id="{{ $detail->item_id }}">Loading...</td>
                        <td>
                            <input type="number" name="items[{{ $detail->item_id }}][qty]" min="1" class="qty-input form-control" value="{{ $detail->qty }}" required>
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger remove-item" data-item-id="{{ $detail->item_id }}">Hapus</button>
                        </td>
                        <input type="hidden" name="items[{{ $detail->item_id }}][item_id]" value="{{ $detail->item_id }}">
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Hidden input untuk menyimpan item yang dihapus -->
        <input type="hidden" name="deleted_items" id="deleted_items" value="">

        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Array untuk menyimpan item yang dihapus
        let deletedItems = [];

        // Fungsi untuk update stok
        function updateStockAvailability() {
            let loanDate = $('#loan_date').val();
            let returnDate = $('#return_date').val();

            if (loanDate && returnDate) {
                $.ajax({
                    url: "{{ route('admin.pinjams.getAvailableItems') }}",
                    method: "GET",
                    data: { loan_date: loanDate, return_date: returnDate },
                    success: function(response) {
                        $('#item-list tr').each(function() {
                            let itemId = $(this).find('.stock-available').data('item-id');
                            if (response[itemId] !== undefined) {
                                $(this).find('.stock-available').text(response[itemId]);
                            }
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr);
                        alert('Terjadi kesalahan saat memeriksa stok.');
                    }
                });
            }
        }

        // Update stok setiap kali tanggal berubah
        $('#loan_date, #return_date').on('change', function() {
            updateStockAvailability();
        });

        // Panggil fungsi untuk update stok saat halaman pertama kali dimuat
        updateStockAvailability();

        // Fungsi untuk menghapus item dari tampilan dan menyimpannya di array deletedItems
        $(document).on('click', '.remove-item', function() {
            let itemId = $(this).data('item-id');
            deletedItems.push(itemId);  // Tambahkan item yang dihapus ke array
            $('#deleted_items').val(deletedItems.join(',')); // Set nilai hidden input
            $(this).closest('tr').remove(); // Hapus baris dari tabel
        });
    });
</script>
@endsection
