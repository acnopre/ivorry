<!DOCTYPE html>
<html>
<head>
    <title>{{ $reportType }} Report</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #f4f4f4;
        }

        .meta {
            font-size: 11px;
            margin-bottom: 10px;
        }

    </style>
</head>
<body>
    <h2>{{ $reportType }} Report</h2>

    <div class="meta">
        @if($fromDate && $toDate)
        <strong>Date Range:</strong> {{ $fromDate }} - {{ $toDate }}<br>
        @endif
        <strong>Generated at:</strong> {{ now()->format('F d, Y h:i A') }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach(array_keys($data->first() ?? []) as $heading)
                <th>{{ Str::headline($heading) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
            <tr>
                @foreach($row as $cell)
                <td>{{ $cell }}</td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td colspan="100%">No records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
