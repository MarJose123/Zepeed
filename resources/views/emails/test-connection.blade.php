<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family:sans-serif;padding:32px;color:#1a1a1a;">
<h2 style="font-size:18px;margin-bottom:8px;">Zepeed — Connection test</h2>
<p style="color:#666;font-size:14px;margin-bottom:16px;">
    This is a test email from your <strong>{{ $provider->driver->label() }}</strong>
    integration configured in Zepeed.
</p>
<p style="color:#666;font-size:14px;">
    If you received this, your mail provider is working correctly.
</p>
<hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
<p style="color:#999;font-size:12px;">Sent from {{ $provider->from_address }} · {{ $provider->label }}</p>
</body>
</html>
