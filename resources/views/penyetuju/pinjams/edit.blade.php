@extends('layouts.penyetuju')

@section('title', 'Edit Peminjaman')
@section('content')
<a href="{{ url('penyetuju/pinjams') }}" class="btn btn-primary mb-3">Kembali</a>
<h2>Edit Peminjaman Barang</h2>

<form action="{{ route('penyetuju.pinjams.update', $pinjam->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label for="user_id" class="form-label">Pilih User</label>
        <select name="user_id" id="user_id" class="form-select" disabled required>
            <option value="">Pilih User</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" {{ $pinjam->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
            @endforeach
        </select>
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
    <label for="status_warek" class="form-label">Status Warek II</label>
    <select name="status_warek" id="status_warek" class="form-select" style="color : white;" required>
        <option value="pending"  {{ $pinjam->status_warek == 'pending' ? 'selected' : '' }}>Pending</option>
        <option value="approved" {{ $pinjam->status_warek == 'approved' ? 'selected' : '' }}>Approved</option>
        <option value="rejected" {{ $pinjam->status_warek == 'rejected' ? 'selected' : '' }}>Rejected</option>
    </select>
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
        <label for="keterangan_peminjam" class="form-label">Keterangan peminjam</label>
        <textarea class="form-control" id="keterangan_peminjam" name="keterangan_peminjam" disabled>{{ $pinjam->keterangan_peminjam }}</textarea>
    </div>
    <div class="mb-3">
        <label for="keterangan_penyetuju" class="form-label">Keterangan peminjam</label>
        <textarea class="form-control" id="keterangan_penyetuju" name="keterangan_penyetuju" disabled>{{ $pinjam->keterangan_penyetuju }}</textarea>
    </div>
    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    <a href="{{ route('penyetuju.pinjams.print', $pinjam->id) }}" class="btn btn-secondary">Cetak PDF</a>
    <a href="https://wa.me/{{ $peminjamWhatsappNumber }}?text=Halo {{ $noPeminjam->name }}, status Warek II peminjaman dengan id : {{ $pinjam->id }} telah diubah ke {{ $pinjam->status_warek }}." target="_blank" class="btn btn-success">Chat di WhatsApp</a>
</form>
@endsection
@section('scripts')
<script>
$(document).ready(function() {

    // Get the select element
const statusSelect2 = document.getElementById('status_warek');

// Function to change the background color
function updateBackgroundColor() {
    switch(statusSelect2.value) {
        case 'pending':
            statusSelect2.style.backgroundColor = '#fcc035';
            break;
        case 'approved':
            statusSelect2.style.backgroundColor = '#35a74c';
            break;
        case 'rejected':
            statusSelect2.style.backgroundColor = '#d83049';
            break;
        case 'returned':
            statusSelect2.style.backgroundColor = '#1d7dfa';
            break;
        default:
            statusSelect2.style.backgroundColor = ''; // Default or clear background
    }
}

// Update background color on page load
updateBackgroundColor();

// Update background color on change event
statusSelect2.addEventListener('change', updateBackgroundColor);

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
                url: "{{ route('penyetuju.pinjams.getAvailableItems') }}", // Pastikan route ini sesuai
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
                url: "{{ route('penyetuju.pinjams.getAvailableItems') }}",
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
