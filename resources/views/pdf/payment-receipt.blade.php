<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pembayaran - {{ $code ?? 'N/A' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; background: #fff; }
        .container { padding: 10px; }
        .header { background: #1a56db; color: white; padding: 8px 10px; margin-bottom: 8px; border-radius: 3px; }
        .header h1 { font-size: 14px; margin-bottom: 3px; }
        .header p { font-size: 9px; opacity: 0.9; }
        .section { margin-bottom: 8px; }
        .section-title { font-size: 10px; font-weight: bold; color: #1a56db; margin-bottom: 3px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        td:first-child { width: 35%; color: #666; font-size: 9px; }
        td:last-child { color: #111; font-size: 10px; }
        .amount-box { background: #1a56db; color: white; text-align: center; padding: 8px; border-radius: 3px; margin-top: 6px; }
        .amount-box .label { font-size: 8px; opacity: 0.8; }
        .amount-box .amount { font-size: 16px; font-weight: bold; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8px; font-weight: bold; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 8px; text-align: center; font-size: 8px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $company_name ?? 'PERUSAHAAN' }}</h1>
            <p>{{ $company_address ?? '' }} {{ $company_email ? '| ' . $company_email : '' }} {{ $company_phone ? '| ' . $company_phone : '' }}</p>
        </div>

        <div class="section">
            <div class="section-title">Informasi Pembayaran</div>
            <table>
                <tr><td>Kode</td><td><strong>{{ $code ?? '-' }}</strong></td></tr>
                <tr><td>Invoice</td><td>{{ $invoice_number ?? '-' }}</td></tr>
                <tr><td>Provider</td><td>{{ ucfirst($provider ?? '-') }}</td></tr>
                <tr><td>Metode</td><td>{{ $payment_method === 'tunai' ? 'Tunai' : 'Transfer Manual' }}</td></tr>
                <tr><td>Tgl Bayar</td><td>{{ $payment_date ?? '-' }}</td></tr>
                <tr><td>Status</td><td>
                    @if(($status ?? '') === 'paid')
                        <span class="status status-paid">LUNAS</span>
                    @elseif(($status ?? '') === 'pending')
                        <span class="status status-pending">PENDING</span>
                    @else
                        <span class="status status-rejected">{{ strtoupper($status ?? '-') }}</span>
                    @endif
                </td></tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Informasi Pelanggan</div>
            <table>
                <tr><td>Nama</td><td><strong>{{ $customer_name ?? '-' }}</strong></td></tr>
                <tr><td>Kode Pelanggan</td><td>{{ $customer_code ?? '-' }}</td></tr>
                <tr><td>Email</td><td>{{ $customer_email ?? '-' }}</td></tr>
                <tr><td>Telepon</td><td>{{ $customer_phone ?? '-' }}</td></tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Informasi Paket</div>
            <table>
                <tr><td>Kode Paket</td><td>{{ $kode_paket ?? '-' }}</td></tr>
                <tr><td>Nama Paket</td><td>{{ $nama_paket ?? '-' }}</td></tr>
            </table>
        </div>

        <div class="amount-box">
            <div class="label">TOTAL PEMBAYARAN</div>
            <div class="amount">Rp {{ number_format($amount_paid ?? 0, 0, ',', '.') }}</div>
        </div>

        <div class="footer">
            Dicetak: {{ $created_at ?? now()->format('Y-m-d H:i') }} | ERP RT RW Net
        </div>
    </div>
</body>
</html>