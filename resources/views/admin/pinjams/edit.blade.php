@extends('layouts.admin')

@section('title', 'Edit Peminjaman')
@section('content')
<a href="{{ url('admin/pinjams') }}" class="btn btn-primary mb-3">Kembali</a>
<h2>Edit Peminjaman Barang</h2>


<form action="{{ route('admin.pinjams.update', $pinjam->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label for="user_id" class="form-label">Pilih User</label>
        <select name="user_id" id="user_id" class="form-select" required>
            <option value="">Pilih User</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" {{ $pinjam->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="loan_date" class="form-label">Tanggal Pinjam</label>
        <input type="date" class="form-control" id="loan_date" name="loan_date" value="{{ $pinjam->loan_date }}" required>
    </div>

    <div class="mb-3">
        <label for="return_date" class="form-label">Tanggal Pengembalian</label>
        <input type="date" class="form-control" id="return_date" name="return_date" value="{{ $pinjam->return_date }}" required>
    </div>

    <div class="mb-3">
        <label for="actual_return_date" class="form-label">Tanggal Pengembalian Asli (kosongkan untuk menjadi tanggal sekarang)</label>
        <input type="date" class="form-control" id="actual_return_date" name="actual_return_date" value="{{ $pinjam->actual_return_date }}" >
    </div>

    <div class="mb-3">
    <label for="status" class="form-label">Status DPT</label>
    <select name="status" id="status" class="form-select" style="color : white;" required>
        <option value="pending"  {{ $pinjam->status == 'pending' ? 'selected' : '' }}>Pending</option>
        <option value="approved" {{ $pinjam->status == 'approved' ? 'selected' : '' }}>Approved</option>
        <option value="rejected" {{ $pinjam->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
        <option value="returned" {{ $pinjam->status == 'returned' ? 'selected' : '' }}>Returned</option>
    </select>
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
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal">Tambah Item</button>
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
                        <input type="number" name="items[{{ $detail->item_id }}][qty]" min="1" class="qty-input" value="{{ $detail->qty }}" data-stock="{{ $detail->qty }}" required>
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

    <div class="mb-3">
        <label for="keterangan_peminjam" class="form-label">Keterangan Peminjam</label>
        <textarea class="form-control" id="keterangan_peminjam" name="keterangan_peminjam">{{ $pinjam->keterangan_peminjam }}</textarea>
    </div>
    <div class="mb-3">
        <label for="keterangan_penyetuju" class="form-label">Keterangan Penyetuju</label>
        <textarea id="keterangan_penyetuju" name="keterangan_penyetuju" class="form-control" >{{ $pinjam->keterangan_penyetuju }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    <a href="{{ route('admin.pinjams.print', $pinjam->id) }}" class="btn btn-secondary">Cetak PDF</a>
    <a href="https://wa.me/{{ $peminjamWhatsappNumber }}?text=Halo {{ $noPeminjam->name }}, status DPT peminjaman dengan id : {{ $pinjam->id }} telah diubah ke {{ $pinjam->status }}. " target="_blank" class="btn btn-success">Chat di WhatsApp</a>
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
                <div class="mb-3">
                    <label for="searchItem" class="form-label">Cari Item</label>
                    <input type="text" id="searchItem" class="form-control" placeholder="Cari item...">
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode Item</th>
                            <th>Nama Item</th>
                            <th>Stock Available</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="available-items">
                        @foreach($items as $item)
                            <tr>
                                <td class="item-name">{{ $item->kode_item }}</td>
                                <td class="item-name">{{ $item->nama_item }}</td>
                                <td class="stock-available" data-item-id="{{ $item->id }}">Loading...</td>
                                <td>
                                    <button class="btn btn-success add-item" data-id="{{ $item->id }}" data-name="{{ $item->nama_item }}" data-code="{{ $item->kode_item }}">Pilih</button>
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

// Get the select element
const statusSelect = document.getElementById('status');
const statusSelect2 = document.getElementById('status_warek');

// Function to change the background color
function updateBackgroundColor() {
    switch(statusSelect.value) {
        case 'pending':
            statusSelect.style.backgroundColor = '#fcc035';
            break;
        case 'approved':
            statusSelect.style.backgroundColor = '#35a74c';
            break;
        case 'rejected':
            statusSelect.style.backgroundColor = '#d83049';
            break;
        case 'returned':
            statusSelect.style.backgroundColor = '#1d7dfa';
            break;
        default:
            statusSelect.style.backgroundColor = ''; // Default or clear background
    }
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
statusSelect.addEventListener('change', updateBackgroundColor);
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
                url: "{{ route('admin.pinjams.getAvailableItems') }}", // Pastikan route ini sesuai
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
                url: "{{ route('admin.pinjams.getAvailableItems') }}",
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

    $('#searchItem').on('keyup', function() {
        let searchValue = $(this).val().toLowerCase();
        $('#available-items tr').filter(function() {
            $(this).toggle($(this).find('.item-name').text().toLowerCase().indexOf(searchValue) > -1);
        });
    });

    $('.add-item').on('click', function() {
        let itemId = $(this).data('id');
        let itemName = $(this).data('name');
        let itemCode = $(this).data('code');
        let stockAvailable = $(this).closest('tr').find('.stock-available').text();

        if ($('#item-list').find(`input[name="items[${itemId}][item_id]"]`).length > 0) {
            alert('Item ini sudah dipilih!');
            return;
        }

        $('#item-list').append(`
            <tr>
                <td>${itemCode}</td>
                <td>${itemName}</td>
                <td>${stockAvailable}</td>
                <td><input type="number" name="items[${itemId}][qty]" min="1" class="qty-input" data-stock="${stockAvailable}" required></td>
                <td><button type="button" class="btn btn-danger remove-item" data-item-id="${itemId}">Hapus</button></td>
                <input type="hidden" name="items[${itemId}][item_id]" value="${itemId}">
            </tr>   
        `);

        $('#itemModal').modal('hide');
    });
    
   
    
    // Menghapus item dan menyimpannya ke dalam input hidden
    $('#item-list').on('click', '.remove-item', function() {
        let itemId = $(this).data('item-id');
        deletedItems.push(itemId);  // Tambahkan item yang dihapus ke array
        $('#deleted_items').val(deletedItems.join(',')); // Set nilai hidden input
        $(this).closest('tr').remove(); // Hapus baris dari tabel
    });

    $('#item-list').on('focus', '.qty-input', function() {
    // Simpan nilai awal ketika input menerima fokus
    $(this).data('initial-value', $(this).val());
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
