@extends('layouts.peminjam')

@section('title', 'Edit Peminjaman')
@section('content')
<a href="{{ url('peminjam/pinjams') }}" class="btn btn-primary mb-3">Kembali</a>
<h2>Edit Peminjaman Barang</h2>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

    <div class="">
        <label for="user_id" class="form-label">Meminjam sebagai : {{ $users->name }}</label>
    </div>

    <div class="mb-3">
        <label for="loan_date" class="form-label">Tanggal Pinjam</label>
        <input type="date" class="form-control" id="loan_date" name="loan_date" value="{{ $pinjam->loan_date }}" disabled required>
    </div>

    <div class="mb-3">
        <label for="return_date" class="form-label">Tanggal Pengembalian</label>
        <input type="date" class="form-control" id="return_date" name="return_date" value="{{ $pinjam->return_date }}" disabled required>
    </div>

    <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <input type="text" class="form-control"  value="{{ $pinjam->status }}" disabled >
    </div>

    <div class="mb-3">
        <label for="status" class="form-label">Status Warek II</label>
        <input type="text" class="form-control"  value="{{ $pinjam->status_warek }}" disabled >
    </div>

    <div class="mb-3">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal" disabled>Tambah Item</button>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Kode Item</th>
                <th>Nama Item</th>
                <th>Stock Available</th>
                <th>Jumlah Pinjam</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="item-list">
            @foreach($pinjam->details as $detail)
                <tr>
                    <td>{{ $detail->item->kode_item }}</td>
                    <td>{{ $detail->item->nama_item }}</td>
                    <td class="stock-available" data-item-id="{{ $detail->item_id }}">Loading...</td> <!-- Stok dinamis diisi oleh AJAX -->
                    <td>
                        <input type="number" name="items[{{ $detail->item_id }}][qty]" min="1" class="qty-input" value="{{ $detail->qty }}" data-stock="{{ $detail->qty }}" disabled required>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger remove-item" data-item-id="{{ $detail->item_id }}" disabled>Hapus</button>
                    </td>
                    <input type="hidden" name="items[{{ $detail->item_id }}][item_id]" value="{{ $detail->item_id }}">
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Hidden input untuk menyimpan item yang dihapus -->
    <input type="hidden" name="deleted_items" id="deleted_items" value="">

    <div class="mb-3">
        <label for="keterangan_peminjam" class="form-label">Keterangan Peminjam</label>
        <textarea class="form-control" id="keterangan_peminjam" name="keterangan_peminjam" disabled>{{ $pinjam->keterangan_peminjam }}</textarea>
    </div>
    <a href="{{ route('peminjam.pinjams.print', $pinjam->id) }}" class="btn btn-secondary">Cetak PDF</a>
    <a href="https://wa.me/{{ $whatsappNumber }}?text=Halo DPT, saya peminjam dengan NIM : {{ $users->name }}, mohon diubah status peminjaman saya dengan id : {{$pinjam->id}}.%0Ahttp://127.0.0.1:8000/admin/pinjams/{{$pinjam->id}}/edit " target="_blank" class="btn btn-success">Chat DPT di WhatsApp</a>
    <a href="https://wa.me/{{ $warekWhatsappNumber }}?text=Halo Warek II, saya peminjam dengan NIM : {{ $users->name }}, mohon diubah status peminjaman saya dengan id : {{$pinjam->id}}.%0Ahttp://127.0.0.1:8000/penyetuju/pinjams/{{$pinjam->id}}/edit " target="_blank" class="btn btn-success">Chat Warek II di WhatsApp</a>
@endsection
@section('scripts')
<script>
$(document).ready(function() {

    // Variabel untuk menyimpan nilai awal return_date
    let initialReturnDate = $('#return_date').val();
    let initialLoanDate = $('#loan_date').val();
    // Array untuk menyimpan item yang dihapus
    let deletedItems = [];
     // Fungsi untuk validasi tanggal kembali
    function validateDates() {
        let loanDate = new Date($('#loan_date').val());
        let returnDate = new Date($('#return_date').val());

        if (returnDate < loanDate) {
            alert('Tanggal kembali tidak boleh lebih awal dari tanggal pinjam.');
            $('#return_date').val(initialReturnDate); // Reset nilai input return_date
            $('#loan_date').val(initialLoanDate); // Reset nilai input loan_date
        }
    }
    

    // Fungsi untuk update stok
    function updateStockAvailability() {
        let loanDate = $('#loan_date').val();
        let returnDate = $('#return_date').val();

        if (loanDate && returnDate) {
            $.ajax({
                url: "{{ route('peminjam.pinjams.getAvailableItems') }}", // Pastikan route ini sesuai
                method: "GET",
                data: { loan_date: loanDate, return_date: returnDate },
                success: function(response) {
                    $('#item-list tr').each(function() {
                        let itemId = $(this).find('.stock-available').data('item-id');
                        if (response[itemId] !== undefined) {
                            $(this).find('.stock-available').text(response[itemId]);
                            $(this).find('.qty-input').data('stock', response[itemId]);
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

    $('#loan_date, #return_date').on('change', function() {
        validateDates()
        updateStockAvailability();
    });

    // Panggil fungsi untuk update stok saat halaman pertama kali dimuat
    updateStockAvailability();

    $('#itemModal').on('show.bs.modal', function () {
        let loanDate = $('#loan_date').val();
        let returnDate = $('#return_date').val();

        if (loanDate && returnDate) {
            $.ajax({
                url: "{{ route('peminjam.pinjams.getAvailableItems') }}",
                method: "GET",
                data: { loan_date: loanDate, return_date: returnDate },
                success: function(response) {
                    $('#available-items tr').each(function() {
                        let itemId = $(this).find('.stock-available').data('item-id');
                        let availableStock = response[itemId] !== undefined ? response[itemId] : 0;
                        $(this).find('.stock-available').text(availableStock);
                    });
                },
                error: function(xhr) {
                    console.error(xhr);
                    alert('Terjadi kesalahan saat memeriksa stok.');
                }
            });
        }
    });

   

    // Cek stok saat qty diinput
    $('#item-list').on('input', '.qty-input', function() {
        let qty = $(this).val();
        let stockAvailable = $(this).data('stock');

        if (qty > stockAvailable) {
            alert(`Stok tidak mencukupi untuk item ini. Stok tersedia: ${stockAvailable}`);
            $(this).val($(this).data('initial-value')); // Kembalikan nilai awal
        }
    });
});
</script>
@endsection
