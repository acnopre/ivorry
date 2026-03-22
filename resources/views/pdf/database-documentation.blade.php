@php
    $title     = 'Database Documentation';
    $subtitle  = 'Complete schema reference — tables, columns, types, keys and relationships';
    $coverMeta = [
        ['value' => $totalTables,  'label' => 'Tables'],
        ['value' => $totalColumns, 'label' => 'Total Columns'],
        ['value' => $generatedAt,  'label' => 'Generated'],
    ];
@endphp

@extends('pdf.doc-layout', compact('title','subtitle','coverMeta','generatedAt'))

@section('body')

@foreach($schema as $table => $info)
@php
    $columns  = collect($info['columns']);
    $foreign  = collect($info['foreign']);
    $indexes  = collect($info['indexes']);
    $primary  = $indexes->firstWhere('primary', true);
    $fkCount  = $foreign->count();
    $idxCount = $indexes->count();
@endphp

<div class="db-table-block">
    <div class="db-table-title">
        <span class="tname">{{ $table }}</span>
        <span class="tmeta">
            {{ $columns->count() }} cols
            @if($fkCount) &nbsp;·&nbsp; {{ $fkCount }} FK @endif
            @if($idxCount) &nbsp;·&nbsp; {{ $idxCount }} indexes @endif
        </span>
    </div>

    <table class="db-cols">
        <thead>
            <tr>
                <th style="width:4%">Key</th>
                <th style="width:22%">Column</th>
                <th style="width:22%">Type</th>
                <th style="width:8%">Null</th>
                <th style="width:14%">Default</th>
                <th style="width:30%">Extra</th>
            </tr>
        </thead>
        <tbody>
            @foreach($columns as $col)
            @php
                $isPrimary = $primary && in_array($col['name'], $primary['columns'] ?? []);
                $fk = $foreign->first(fn($f) => in_array($col['name'], $f['columns'] ?? []));
                $type = $col['type'];
                $typeClass = str_contains($type,'int') ? 'badge-int'
                    : (str_contains($type,'varchar')||str_contains($type,'text') ? 'badge-str'
                    : (str_contains($type,'date')||str_contains($type,'time') ? 'badge-date'
                    : (str_contains($type,'decimal')||str_contains($type,'float') ? 'badge-dec'
                    : (str_contains($type,'enum') ? 'badge-enum' : 'badge-other'))));
            @endphp
            <tr>
                <td style="text-align:center">
                    @if($isPrimary) <span class="badge badge-pk">PK</span>
                    @elseif($fk)   <span class="badge badge-fk">FK</span>
                    @endif
                </td>
                <td><span class="col-name-mono">{{ $col['name'] }}</span></td>
                <td><span class="badge {{ $typeClass }}">{{ $type }}</span></td>
                <td style="text-align:center">
                    @if($col['nullable'])
                        <span class="badge badge-null">NULL</span>
                    @else
                        <span class="badge badge-notnull">NOT NULL</span>
                    @endif
                </td>
                <td style="font-family:monospace;font-size:8px;color:#6b7280">{{ $col['default'] ?? '—' }}</td>
                <td>
                    @if($col['auto_increment']) <span class="badge badge-ai">AUTO_INCREMENT</span> @endif
                    @if($fk) <span class="fk-ref">&#8594; {{ $fk['foreign_table'] }}.{{ implode(', ', $fk['foreign_columns'] ?? []) }}</span> @endif
                    @if($col['comment']) <span style="color:#6b7280;font-size:8px">{{ $col['comment'] }}</span> @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endforeach

@endsection
