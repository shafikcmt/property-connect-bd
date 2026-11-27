<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title')</title>
    <style>
        body { font: 14px/1.5 system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color: #1c1b19; margin: 0; background: #f4f3f1; }
        .page { max-width: 820px; margin: 24px auto; background: #fff; padding: 36px 40px; border: 1px solid #ddd; }
        h1 { font-size: 22px; margin: 0 0 4px; } h2 { font-size: 15px; margin: 24px 0 8px; text-transform: uppercase; letter-spacing: .04em; }
        table { width: 100%; border-collapse: collapse; } th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e4e1dc; } th { font-size: 12px; text-transform: uppercase; color: #555; }
        td.n, th.n { text-align: right; font-variant-numeric: tabular-nums; }
        .muted { color: #666; } .big { font-size: 26px; font-weight: 700; } .tag { display: inline-block; padding: 1px 8px; border: 1px solid #999; border-radius: 99px; font-size: 12px; }
        .bar { max-width: 820px; margin: 16px auto 0; text-align: right; } button { padding: 8px 16px; border: 1px solid #0a7459; background: #0a7459; color: #fff; border-radius: 6px; cursor: pointer; }
        .foot { margin-top: 32px; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
        @media print { body { background: #fff; } .page { border: 0; margin: 0; padding: 0; max-width: none; } .bar { display: none; } }
    </style>
</head>
<body>
<div class="bar"><button onclick="window.print()">Print</button></div>
<div class="page">@yield('content')</div>
</body>
</html>
