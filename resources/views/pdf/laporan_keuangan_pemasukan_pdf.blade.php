<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan Laundry</title>
    <style>
        body { font-family: sans-serif; }
        h2 { text-align: center; }
        table { width: 100%; margin-top: 30px; border-collapse: collapse; }
        td, th { padding: 10px; border: 1px solid #000; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>Laporan Keuangan Laundry</h2>
    <p><strong>Tanggal Export:</strong> {{ $tanggal }}</p>

    <table>
        <thead>
            <tr>
                <th>Jenis</th>
                <th class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Pemasukan Offline Tercatat</td>
                <td class="text-right">Rp {{ number_format($total_pemasukan_offline_tercatat, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pemasukan Online Terkonfirmasi</td>
                <td class="text-right">Rp {{ number_format($total_pemasukan_online_terkonfirmasi, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Pemasukan Keseluruhan</td>
                <td class="text-right">Rp {{ number_format($total_pemasukan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Pengeluaran</td>
                <td class="text-right">Rp {{ number_format($total_pengeluaran, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total Laba</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($total_laba, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>