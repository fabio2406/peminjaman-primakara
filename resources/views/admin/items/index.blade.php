@extends('layouts.admin')

@section('title', 'Item Management')

@section('content')
<h1>Items</h1>

<div class="mb-3">
    <a href="{{ route('admin.items.create') }}" class="btn btn-primary">Create New Item</a>
</div>
<!-- Form Pencarian dan Filter -->
<div class="mb-3">
    <input type="text" id="search" class="form-control" placeholder="Search by Kode Item or Nama Item" value="{{ request('search') }}">
</div>

<div class="mb-3">
    <select id="category" class="form-select">
        <option value="all" {{ request('category') == 'all' ? 'selected' : '' }}>All Categories</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
        @endforeach
    </select>
</div>

<div class="table-responsive">
    <table class="table table-striped table-bordered" id="items-table">
        <thead>
            <tr>
                <th>Kode Item</th>
                <th>Nama Item</th>
                <th class="col-1">Stok</th>
                <th>Kategori</th>
                <th class="col-2">Actions</th>
            </tr>
        </thead>
        <tbody id="items-body">
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->kode_item }}</td>
                    <td>{{ $item->nama_item }}</td>
                    <td>{{ $item->stok }}</td>
                    <td>{{ $item->category->name }}</td>
                    <td class="d-flex justify-content-center">
                        <a href="{{ route('admin.items.edit', $item->id) }}" class="btn btn-warning mx-auto">Edit</a>
                        <form action="{{ route('admin.items.destroy', $item->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Pagination Container -->
<div class="pagination-container ">
    {!! $items->links('pagination::bootstrap-5') !!} <!-- Menampilkan pagination Bootstrap 5 -->
</div>

<!-- Include jQuery -->
@endsection
@section('scripts')

<script>
$(document).ready(function() {
    $('#search').on('keyup', function() {
        filterItems();
    });

    $('#category').on('change', function() {
        filterItems();
    });

    // Menyimpan nilai pencarian dan kategori
    function filterItems(url = "{{ route('admin.items.index') }}") {
        let searchValue = $('#search').val();
        let categoryValue = $('#category').val();

        $.ajax({
            url: url,
            method: "GET",
            data: { search: searchValue, category: categoryValue },
            success: function(data) {
                let rows = '';
                data.items.data.forEach(item => {
                    rows += `
                        <tr>
                            <td>${item.kode_item}</td>
                            <td>${item.nama_item}</td>
                            <td>${item.stok}</td>
                            <td>${item.category.name}</td>
                            <td class="d-flex justify-content-center">
                                <a href="/admin/items/${item.id}/edit" class="btn btn-warning mx-auto">Edit</a>
                                <form action="/admin/items/${item.id}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    `;
                });
                $('#items-body').html(rows);

                // Update pagination
                $('.pagination-container').html(data.pagination);
            },
            error: function(xhr, status, error) {
                console.error(xhr);
            }
        });
    }

    // Menghandle link pagination
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        let url = $(this).attr('href');
        filterItems(url);
    });
});
</script>
@endsection
