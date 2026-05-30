<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pembayaran - {{ $code ?? 'N/A' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            padding: 20px;
            background: #fff;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            border: 2px solid #1a56db;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1a56db;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            color: #1a56db;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 10px;
            color: #666;
        }
        .info-box {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 15px;
        }
        .info-box h2 {
            font-size: 12px;
            color: #1a56db;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 5px 0;
            vertical-align: top;
        }
        td:first-child {
            width: 40%;
            color: #6b7280;
        }
        td:last-child {
            color: #111827;
        }
        .amount-box {
            background: #1a56db;
            color: white;
            text-align: center;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        .amount-box .label {
            font-size: 10px;
            opacity: 0.8;
            margin-bottom: 3px;
        }
        .amount-box .amount {
            font-size: 20px;
            font-weight: bold;
        }
        .status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
        }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .two-col {
            display: flex;
            gap: 20px;
        }
        .two-col .col {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>BUKTI PEMBAYARAN</h1>
            <p>{{ $invoice_number ?? 'N/A' }}</p>
        </div>

        <div class="info-box">
            <h2>Informasi Pembayaran</h2>
            <table>
                <tr>
                    <td>Kode Pembayaran</td>
                    <td><strong>{{ $code ?? 'N/A' }}</strong></td>
                </tr>
                <tr>
                    <td>Kode Tagihan</td>
                    <td>{{ $invoice_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Provider</td>
                    <td>{{ ucfirst($provider ?? '-') }}</td>
                </tr>
                <tr>
                    <td>Metode Pembayaran</td>
                    <td>{{ $payment_method === 'tunai' ? 'Tunai' : 'Transfer Manual' }}</td>
                </tr>
                <tr>
                    <td>Tanggal Bayar</td>
                    <td>{{ $payment_date ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td>
                        @if(($status ?? '') === 'paid')
                            <span class="status status-paid">LUNAS</span>
                        @elseif(($status ?? '') === 'pending')
                            <span class="status status-pending">PENDING</span>
                        @else
                            <span class="status status-rejected">{{ strtoupper($status ?? '-') }}</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="info-box">
            <h2>Informasi Pelanggan</h2>
            <table>
                <tr>
                    <td>Nama Pelanggan</td>
                    <td><strong>{{ $customer_name ?? 'N/A' }}</strong></td>
                </tr>
                <tr>
                    <td>Kode Pelanggan</td>
                    <td>{{ $customer_code ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td>{{ $email ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Telepon</td>
                    <td>{{ $phone ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="info-box">
            <h2>Informasi Paket</h2>
            <table>
                <tr>
                    <td>Kode Paket</td>
                    <td>{{ $kode_paket ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Nama Paket</td>
                    <td>{{ $nama_paket ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="amount-box">
            <div class="label">TOTAL PEMBAYARAN</div>
            <div class="amount">Rp {{ number_format($amount_paid ?? 0, 0, ',', '.') }}</div>
        </div>

        <div class="footer">
            Dicetak pada: {{ $created_at ?? now()->format('Y-m-d H:i:s') }}<br>
            Bukti pembayaran ini dicetak secara resmi oleh sistem ERP RT RW Net
        </div>
    </div>
</body>
</html>