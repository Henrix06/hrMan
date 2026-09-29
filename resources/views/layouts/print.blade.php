<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $title ?? config('app.name', 'uMsizi') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml"/>

    {{-- Deliberately self-contained: a document must render and print the same
         way whether or not the app's CSS bundle has been built. --}}
    <style>
        :root {
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #dcdfe4;
            --soft: #f6f7f9;
            --brand: #8b5e3c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #eceff3;
            color: var(--ink);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.55;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ---- Toolbar (screen only) ---- */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            max-width: 210mm;
            margin: 20px auto 0;
            padding: 12px 16px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
        }
        .toolbar .hint { color: var(--muted); font-size: 12px; }
        .toolbar-actions { display: flex; align-items: center; gap: 10px; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-primary { background: var(--brand); border-color: var(--brand); color: #fff; }

        /* ---- The sheet ---- */
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto 40px;
            padding: 16mm;
            background: #fff;
            box-shadow: 0 12px 32px rgba(15, 23, 42, .12);
        }

        /* ---- Document structure ---- */
        .doc-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 14px;
            border-bottom: 2px solid var(--ink);
        }
        .brand { font-size: 20px; font-weight: 800; letter-spacing: -.01em; }
        .doc-kind {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--brand);
        }
        .doc-ref { font-size: 15px; font-weight: 700; }
        .doc-meta { text-align: right; }
        .muted { color: var(--muted); }
        .small { font-size: 11.5px; }

        .badge {
            display: inline-block;
            padding: 2px 9px;
            border: 1px solid var(--line);
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: var(--soft);
        }
        .badge-draft { border-color: #e9c46a; background: #fdf6e3; color: #8a6116; }

        .notice {
            margin-top: 14px;
            padding: 9px 12px;
            border: 1px solid #e9c46a;
            border-radius: 8px;
            background: #fdf6e3;
            color: #8a6116;
            font-size: 12px;
        }

        .parties { display: flex; gap: 24px; margin: 18px 0 4px; }
        .party { flex: 1; }
        .party h3 {
            margin: 0 0 4px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .section { margin-top: 22px; }
        .section h3 {
            margin: 0 0 8px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
        }

        table { width: 100%; border-collapse: collapse; }
        .doc-table th {
            padding: 8px 10px;
            background: var(--soft);
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #4b5563;
            text-align: left;
        }
        .doc-table td { padding: 9px 10px; border-bottom: 1px solid var(--line); vertical-align: top; }
        .right { text-align: right; }
        .nowrap { white-space: nowrap; }

        .foot {
            display: flex;
            justify-content: flex-end;
            gap: 40px;
            margin-top: 4px;
        }
        .totals { width: 62mm; margin-top: 12px; margin-left: auto; }
        .totals div { display: flex; justify-content: space-between; padding: 5px 0; }
        .totals .grand {
            margin-top: 6px;
            padding-top: 8px;
            border-top: 2px solid var(--ink);
            font-size: 15px;
            font-weight: 800;
        }

        .signatures { display: flex; gap: 32px; margin-top: 34px; }
        .signature { flex: 1; }
        .signature .line { margin-top: 30px; border-top: 1px solid #9ca3af; }
        .signature .caption { font-size: 11px; color: var(--muted); padding-top: 4px; }

        .doc-footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 11px;
            display: flex;
            justify-content: space-between;
            gap: 16px;
        }

        /* ---- Screen sizes ---- */
        @media (max-width: 780px) {
            .sheet { width: 100%; min-height: auto; margin: 16px 0; padding: 20px; }
            .toolbar { margin: 12px; }
            .parties, .signatures { flex-direction: column; gap: 16px; }
        }

        /* ---- Paper ---- */
        @page { size: A4; margin: 14mm; }

        @media print {
            body { background: #fff; font-size: 11.5px; }
            .no-print { display: none !important; }
            .sheet { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
            .doc-head { page-break-after: avoid; }
            .section h3 { page-break-after: avoid; }
            tr { page-break-inside: avoid; }
            thead { display: table-header-group; }
            a { color: inherit; text-decoration: none; }
        }
    </style>

    @livewireStyles
</head>
<body>
    {{ $slot }}

    @livewireScripts
</body>
</html>
