<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
            background: #fff;
        }

        /* ─────────────────────────────────────────
           COVER PAGE  (full page, no header/footer)
        ───────────────────────────────────────── */
        .cover-page {
            width: 100%;
            height: 100%;
            page-break-after: always;
        }

        /* Top accent bar */
        .cover-accent-bar {
            height: 6px;
            background: #8B1C52;
        }

        /* Main cover body */
        .cover-body {
            background: #fff;
            padding: 52px 48px 40px;
            min-height: 680px;
        }

        /* Logo row */
        .cover-logo-row {
            margin-bottom: 48px;
        }
        .cover-logo-name {
            font-size: 36px;
            font-weight: bold;
            color: #8B1C52;
            letter-spacing: 0.08em;
            line-height: 1;
        }
        .cover-logo-tagline {
            font-size: 9px;
            color: #A63D6B;
            margin-top: 4px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
        }

        /* Doc type pill */
        .cover-pill {
            display: inline-block;
            background: #fdf2f7;
            border: 1px solid #D4789A;
            color: #8B1C52;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 18px;
        }

        /* Cover title */
        .cover-title {
            font-size: 32px;
            font-weight: bold;
            color: #1f2937;
            line-height: 1.2;
            margin-bottom: 14px;
        }

        /* Cover subtitle */
        .cover-subtitle {
            font-size: 11px;
            color: #6b7280;
            line-height: 1.6;
            max-width: 480px;
            margin-bottom: 40px;
        }

        /* Divider */
        .cover-divider {
            height: 1px;
            background: #e2e8f0;
            margin-bottom: 24px;
        }

        /* Stats row */
        .cover-stats {
            display: table;
            width: 100%;
        }
        .cover-stat {
            display: table-cell;
            vertical-align: top;
            padding-right: 32px;
        }
        .cover-stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #8B1C52;
            line-height: 1;
            margin-bottom: 4px;
        }
        .cover-stat-label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        /* Bottom strip */
        .cover-bottom {
            background: #fdf2f7;
            border-top: 2px solid #D4789A;
            padding: 14px 48px;
            display: table;
            width: 100%;
        }
        .cover-bottom-left {
            display: table-cell;
            vertical-align: middle;
            font-size: 9px;
            color: #A63D6B;
        }
        .cover-bottom-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            font-size: 9px;
            color: #A63D6B;
        }

        /* ─────────────────────────────────────────
           CONTENT PAGES
        ───────────────────────────────────────── */
        .page-header-fixed {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 40px;
            background: #fdf2f7;
            padding: 0 24px;
            display: table;
            width: 100%;
            border-bottom: 2px solid #8B1C52;
        }
        .page-header-fixed-inner {
            display: table-cell;
            vertical-align: middle;
        }
        .page-header-fixed .hdr-left {
            display: table-cell;
            vertical-align: middle;
        }
        .page-header-fixed .hdr-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }
        .page-header-fixed .sys-name {
            font-size: 14px;
            font-weight: bold;
            color: #8B1C52;
            letter-spacing: 0.08em;
        }
        .page-header-fixed .doc-title {
            font-size: 8px;
            color: #A63D6B;
            margin-top: 1px;
        }

        .page-footer-fixed {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 20px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 0 24px;
            display: table;
            width: 100%;
        }
        .page-footer-fixed-inner {
            display: table-cell;
            vertical-align: middle;
        }
        .footer-text  { font-size: 8px; color: #94a3b8; }
        .footer-right { float: right; font-size: 8px; color: #94a3b8; }

        .content {
            margin-top: 50px;
            padding: 12px 24px 30px;
        }

        /* ─────────────────────────────────────────
           TYPOGRAPHY
        ───────────────────────────────────────── */
        .section-heading {
            border-left: 3px solid #8B1C52;
            padding: 4px 0 4px 10px;
            margin: 18px 0 10px;
            page-break-after: avoid;
        }
        .section-heading h2 {
            font-size: 13px;
            font-weight: bold;
            color: #1a0a0f;
        }
        .section-heading p {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }

        .sub-heading {
            font-size: 10px;
            font-weight: bold;
            color: #1e293b;
            margin: 12px 0 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #e2e8f0;
            page-break-after: avoid;
        }

        /* ─────────────────────────────────────────
           TABLES
        ───────────────────────────────────────── */
        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9px;
        }
        table.info-table th {
            background: #f1f5f9;
            padding: 5px 8px;
            text-align: left;
            font-weight: bold;
            color: #1e293b;
            border: 1px solid #e2e8f0;
        }
        table.info-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            color: #374151;
            vertical-align: top;
        }
        table.info-table tr:nth-child(even) td { background: #f8fafc; }

        /* ─────────────────────────────────────────
           CALLOUTS
        ───────────────────────────────────────── */
        .callout {
            border-radius: 4px;
            padding: 10px 12px;
            margin: 8px 0;
            font-size: 9px;
        }
        .callout-blue   { background: #fdf2f7; border-left: 3px solid #8B1C52; }
        .callout-green  { background: #f0fdf4; border-left: 3px solid #16a34a; }
        .callout-yellow { background: #fefce8; border-left: 3px solid #ca8a04; }
        .callout-purple { background: #faf5ff; border-left: 3px solid #9333ea; }
        .callout-red    { background: #fef2f2; border-left: 3px solid #dc2626; }
        .callout-title  { font-weight: bold; margin-bottom: 5px; font-size: 10px; color: #0f172a; }
        .callout li     { margin-left: 14px; margin-bottom: 2px; }
        .callout ol li  { list-style: decimal; }
        .callout ul li  { list-style: disc; }

        /* ─────────────────────────────────────────
           BADGES
        ───────────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-pk      { background: #fef3c7; color: #78350f; }
        .badge-fk      { background: #fce7f0; color: #6b1039; }
        .badge-int     { background: #dbeafe; color: #1d4ed8; }
        .badge-str     { background: #dcfce7; color: #14532d; }
        .badge-date    { background: #f3e8ff; color: #581c87; }
        .badge-dec     { background: #ffedd5; color: #7c2d12; }
        .badge-enum    { background: #fce7f3; color: #831843; }
        .badge-other   { background: #f1f5f9; color: #334155; }
        .badge-null    { background: #f1f5f9; color: #64748b; }
        .badge-notnull { background: #fee2e2; color: #7f1d1d; }
        .badge-ai      { background: #fef9c3; color: #713f12; }
        .badge-status  { background: #e0e7ff; color: #1e1b4b; }

        /* ─────────────────────────────────────────
           STATUS DOTS
        ───────────────────────────────────────── */
        .status-row  { display: table; width: 100%; margin-bottom: 3px; }
        .status-dot  { display: table-cell; width: 8px; vertical-align: middle; }
        .status-dot span { display: inline-block; width: 6px; height: 6px; border-radius: 50%; }
        .status-name { display: table-cell; width: 80px; font-weight: bold; font-size: 9px; color: #1e293b; vertical-align: middle; padding-left: 4px; }
        .status-desc { display: table-cell; font-size: 9px; color: #64748b; vertical-align: middle; }

        /* ─────────────────────────────────────────
           TWO-COLUMN
        ───────────────────────────────────────── */
        .two-col   { display: table; width: 100%; margin-bottom: 10px; }
        .col-left  { display: table-cell; width: 48%; vertical-align: top; padding-right: 8px; }
        .col-right { display: table-cell; width: 48%; vertical-align: top; padding-left: 8px; }

        /* ─────────────────────────────────────────
           DB TABLE BLOCKS
        ───────────────────────────────────────── */
        .db-table-block { margin-bottom: 16px; page-break-inside: avoid; }
        .db-table-title {
            background: #fdf2f7;
            color: #1f2937;
            padding: 6px 12px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 4px 4px 0 0;
            display: table;
            width: 100%;
            border-bottom: 2px solid #8B1C52;
            border: 1px solid #D4789A;
        }
        .db-table-title .tname { display: table-cell; font-family: DejaVu Sans Mono, monospace; color: #8B1C52; }
        .db-table-title .tmeta { display: table-cell; text-align: right; font-size: 8px; font-weight: normal; color: #A63D6B; }

        table.db-cols {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            border-top: none;
            font-size: 9px;
        }
        table.db-cols thead tr { background: #fce7f3; }
        table.db-cols th {
            padding: 4px 8px;
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #8B1C52;
            border-bottom: 1px solid #C4547A;
            border-right: 1px solid #e2e8f0;
            font-weight: bold;
        }
        table.db-cols td {
            padding: 4px 8px;
            border-bottom: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        table.db-cols tr:last-child td { border-bottom: none; }
        table.db-cols tr:nth-child(even) td { background: #f8fafc; }
        .col-name-mono { font-family: DejaVu Sans Mono, monospace; font-weight: bold; color: #0f172a; font-size: 9px; }
        .fk-ref { font-size: 8px; color: #A63D6B; font-family: DejaVu Sans Mono, monospace; }

        code { font-family: DejaVu Sans Mono, monospace; background: #f1f5f9; padding: 1px 4px; border-radius: 2px; font-size: 9px; color: #0f172a; }
    </style>
</head>
<body>

{{-- ═══════════════════════════════════════
     COVER PAGE
═══════════════════════════════════════ --}}
<div class="cover-page">

    <div class="cover-accent-bar"></div>

    <div class="cover-body">

        {{-- Logo --}}
        <div class="cover-logo-row">
            <div class="cover-logo-name">IVORRY</div>
            <div class="cover-logo-tagline">Web Portal</div>
        </div>

        {{-- Doc type pill --}}
        <div class="cover-pill">Documentation</div>

        {{-- Title --}}
        <div class="cover-title">{{ $title }}</div>

        {{-- Subtitle --}}
        <div class="cover-subtitle">{{ $subtitle }}</div>

        <div class="cover-divider"></div>

        {{-- Stats --}}
        <div class="cover-stats">
            @foreach($coverMeta as $meta)
            <div class="cover-stat">
                <div class="cover-stat-value">{{ $meta['value'] }}</div>
                <div class="cover-stat-label">{{ $meta['label'] }}</div>
            </div>
            @endforeach
        </div>

    </div>

    <div class="cover-bottom">
        <div class="cover-bottom-left">IVORRY &mdash; Confidential Internal Documentation</div>
        <div class="cover-bottom-right">Generated: {{ $generatedAt }}</div>
    </div>

</div>

{{-- ═══════════════════════════════════════
     CONTENT PAGES
═══════════════════════════════════════ --}}
<div class="page-header-fixed">
    <table style="width:100%;height:40px">
        <tr>
            <td class="hdr-left" style="vertical-align:middle">
                <div class="sys-name">IVORRY</div>
                <div class="doc-title">{{ $title }}</div>
            </td>
            <td class="hdr-right" style="vertical-align:middle;text-align:right">
            </td>
        </tr>
    </table>
</div>

<div class="page-footer-fixed">
    <div class="page-footer-fixed-inner">
        <span class="footer-text">IVORRY &mdash; {{ $title }}</span>
        <span class="footer-right">Generated: {{ $generatedAt }}</span>
    </div>
</div>

<div class="content">
    @yield('body')
</div>

</body>
</html>
