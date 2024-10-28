<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Fabio Ananda">
    <title>Cetak Peminjaman</title>
    
    <style>
        /* General page style */
        body {
            font-family: Arial, sans-serif;
            color: #333;
            padding: 0;
            margin: 0;
            width: 100%;
            font-size: 12pt;
        }

        h2 {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        /* Container for centered content */
        .content {
            width: 100%;
            max-width: 190mm; /* A4 width minus margin */
            margin: 0 auto;
            padding: 0 10mm;
        }

        /* Table styling */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th, .table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
        }

        .table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        /* Footer section for additional notes */
        p {
            margin: 5px 0;
        }

        /* Print-specific styles */
        @media print {
            body, .content {
                margin: 0;
                width: 100%;
                padding: 0;
            }
            .table th, .table td {
                border-color: #333;
            }
        }
    </style>
</head>
<body>

<div class="content">
    <h2>Detail Peminjaman Barang</h2>
    <p><strong>User:</strong> {{ $pinjam->user->name }}</p>
    <p><strong>Tanggal Pinjam:</strong> {{ $pinjam->loan_date }}</p>
    <p><strong>Tanggal Pengembalian:</strong> {{ $pinjam->return_date }}</p>
    <p><strong>Status:</strong> {{ ucfirst($pinjam->status) }}</p>

    <table class="table">
        <thead>
            <tr>
                <th>Kode Item</th>
                <th>Nama Item</th>
                <th>Jumlah Pinjam</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pinjam->details as $detail)
                <tr>
                    <td>{{ $detail->item->kode_item }}</td>
                    <td>{{ $detail->item->nama_item }}</td>
                    <td>{{ $detail->qty }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p><strong>Keterangan Peminjam:</strong> {{ $pinjam->keterangan_peminjam }}</p>
    <p><strong>Keterangan Penyetuju:</strong> {{ $pinjam->keterangan_penyetuju }}</p>
</div>

</body>
</html>
