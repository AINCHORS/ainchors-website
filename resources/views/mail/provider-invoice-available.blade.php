<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Confirmation &amp; Stripe Invoice</title>
</head>
<body style="margin:0;background:#f4f8f6;color:#202733;font-family:Arial,Helvetica,sans-serif;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">Your payment has been confirmed and your purchase is now available.</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f8f6;padding:28px 12px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border:1px solid #dfe9e4;border-radius:18px;overflow:hidden;">
            <tr><td align="center" style="padding:30px 34px 18px;">
                <img src="{{ $message->embed(public_path('assets/logo.webp')) }}" width="150" alt="AINCHORS Training &amp; Consulting" style="display:block;width:150px;max-width:100%;height:auto;border:0;">
            </td></tr>
            <tr><td align="center" style="padding:0 34px 26px;">
                <h1 style="margin:0;color:#161d24;font-size:30px;line-height:1.25;">Payment Confirmed</h1>
            </td></tr>
            <tr><td style="padding:0 42px 24px;font-size:16px;line-height:1.65;color:#4b5560;">
                <p style="margin:0 0 14px;">Dear {{ $order->user->first_name ?: ($order->user->full_name ?: 'Customer') }},</p>
                <p style="margin:0 0 14px;">Thank you for choosing AINCHORS Training &amp; Consulting.</p>
                <p style="margin:0;">We are pleased to confirm that your payment for <strong style="color:#202733;">{{ $item?->product_name }}</strong> has been successfully processed. Your {{ strtolower($purchaseLabel) }} is now available through your AINCHORS account.</p>
            </td></tr>
            <tr><td style="padding:0 42px 26px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#effbf6;border:1px solid #ccecdf;border-radius:12px;">
                    <tr><td colspan="2" style="padding:20px 22px 10px;color:#161d24;font-size:17px;font-weight:700;">Purchase Summary</td></tr>
                    <tr><td style="padding:8px 22px;color:#67717b;font-size:14px;">{{ $purchaseLabel }}</td><td align="right" style="padding:8px 22px;color:#202733;font-size:14px;font-weight:600;">{{ $item?->product_name }}</td></tr>
                    <tr><td style="padding:8px 22px;color:#67717b;font-size:14px;">Order Number</td><td align="right" style="padding:8px 22px;color:#202733;font-size:14px;font-weight:600;">{{ $order->order_number }}</td></tr>
                    <tr><td style="padding:8px 22px;color:#67717b;font-size:14px;">Payment Date</td><td align="right" style="padding:8px 22px;color:#202733;font-size:14px;font-weight:600;">{{ optional($stripePayment?->paid_at)->format('d M Y, H:i') ?: optional($order->placed_at)->format('d M Y, H:i') }}</td></tr>
                    <tr><td style="padding:8px 22px;color:#67717b;font-size:14px;">Payment Method</td><td align="right" style="padding:8px 22px;color:#202733;font-size:14px;font-weight:600;">Stripe</td></tr>
                    <tr><td style="padding:8px 22px;color:#67717b;font-size:14px;">Payment Status</td><td align="right" style="padding:8px 22px;color:#16835f;font-size:14px;font-weight:700;">Paid</td></tr>
                    <tr><td style="padding:8px 22px;color:#67717b;font-size:14px;">Amount Paid</td><td align="right" style="padding:8px 22px;color:#161d24;font-size:16px;font-weight:700;">{{ strtoupper($order->currency) }} {{ number_format((float) $order->total_amount, 2) }}</td></tr>
                    <tr><td style="padding:8px 22px;color:#67717b;font-size:14px;">Stripe Invoice Number</td><td align="right" style="padding:8px 22px;color:#202733;font-size:14px;font-weight:600;">{{ $invoice->invoice_number ?: $invoice->external_reference }}</td></tr>
                    <tr><td style="padding:8px 22px 20px;color:#67717b;font-size:14px;">Transaction Reference</td><td align="right" style="padding:8px 22px 20px;color:#202733;font-size:12px;font-weight:600;word-break:break-all;">{{ $stripePayment?->provider_transaction_id }}</td></tr>
                </table>
            </td></tr>
            <tr><td align="center" style="padding:0 42px 14px;">
                <a href="{{ $accessUrl }}" style="display:inline-block;min-width:210px;padding:14px 22px;border-radius:8px;background:#35b28a;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;">{{ $accessLabel }}</a>
            </td></tr>
            <tr><td style="padding:8px 42px 14px;font-size:14px;line-height:1.65;color:#4b5560;">
                <h2 style="margin:0 0 10px;color:#202733;font-size:18px;">Provider Invoice / Receipt</h2>
                <p style="margin:0;">Your official invoice/receipt has been generated and securely hosted by Stripe.</p>
            </td></tr>
            <tr><td align="center" style="padding:0 42px 18px;">
                <a href="{{ $invoiceUrl }}" style="display:inline-block;min-width:210px;padding:13px 21px;border:1px solid #35b28a;border-radius:8px;background:#ffffff;color:#16835f;text-decoration:none;font-size:15px;font-weight:700;">View Receipt</a>
            </td></tr>
            <tr><td style="padding:0 42px 28px;font-size:13px;line-height:1.6;color:#6b737c;">
                <p style="margin:0 0 12px;">You can use the Stripe page above to view, download or print the official invoice PDF for your records.</p>
                <p style="margin:0 0 12px;">Please note that this email serves as confirmation of your successful payment. The official invoice/receipt is issued and hosted by Stripe.</p>
                <p style="margin:0 0 20px;">If you have any questions regarding your purchase or course access, please contact us at <a href="mailto:info@ainchors.com" style="color:#16835f;">info@ainchors.com</a> and include your order number.</p>
                <p style="margin:0;">Yours sincerely,<br><strong style="color:#202733;">AINCHORS Training &amp; Consulting</strong><br><a href="https://www.ainchors.com" style="color:#16835f;">www.ainchors.com</a><br><a href="mailto:info@ainchors.com" style="color:#16835f;">info@ainchors.com</a></p>
            </td></tr>
            <tr><td style="background:#16221e;padding:24px 34px;text-align:center;color:#d9e4df;font-size:12px;line-height:1.6;">
                <p style="margin:0 0 10px;"><strong>Australia</strong><br>AI Anchor Solutions Pty Ltd<br>ACN 691 339 714 | ABN 99 691 339 714</p>
                <p style="margin:0 0 10px;"><strong>Malaysia</strong><br>AINCHORS Sdn Bhd<br>Registration No. 202001021528 (1377848K)</p>
                <p style="margin:0;color:#aebdb6;">Copyright © {{ now()->year }} AINCHORS Training &amp; Consulting. All rights reserved.</p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
