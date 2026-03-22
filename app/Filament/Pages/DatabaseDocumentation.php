<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseDocumentation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?string $navigationLabel = 'Database Documentation';
    protected static ?string $navigationGroup = 'Help & Documentation';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.database-documentation';

    const CACHE_KEY = 'db_documentation_schema';
    const CACHE_TTL = 60 * 60 * 24; // 24 hours

    public string $search = '';

    public function getViewData(): array
    {
        $schema = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn() => $this->buildSchema());

        $filtered = collect($schema)
            ->when($this->search, fn($c) => $c->filter(
                fn($_, $table) => str_contains(strtolower($table), strtolower($this->search))
            ))
            ->map(fn($t) => [
                'columns' => collect($t['columns']),
                'indexes' => collect($t['indexes']),
                'foreign' => collect($t['foreign']),
                'primary' => $t['primary'],
            ]);

        return [
            'schema'       => $filtered,
            'totalTables'  => count($schema),
            'totalColumns' => collect($schema)->sum(fn($t) => count($t['columns'])),
            'cachedAt'     => Cache::get(self::CACHE_KEY . '_at'),
        ];
    }

    private function buildSchema(): array
    {
        $tables = collect(DB::select('SHOW TABLES'))
            ->map(fn($row) => array_values((array) $row)[0])
            ->sort()
            ->values();

        $schema = $tables->mapWithKeys(function ($table) {
            $columns = collect(Schema::getColumns($table))->map(fn($col) => [
                'name'           => $col['name'],
                'type'           => $col['type'],
                'nullable'       => $col['nullable'],
                'default'        => $col['default'],
                'auto_increment' => $col['auto_increment'],
                'comment'        => $col['comment'] ?? null,
            ])->all();

            $indexes = collect(Schema::getIndexes($table))->all();
            $foreign = collect(Schema::getForeignKeys($table))->all();
            $primary = collect($indexes)->firstWhere('primary', true);

            return [$table => compact('columns', 'indexes', 'foreign', 'primary')];
        })->all();

        Cache::put(self::CACHE_KEY . '_at', now()->format('M d, Y h:i A'), self::CACHE_TTL);

        return $schema;
    }

    public function refreshSchema(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY . '_at');

        Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn() => $this->buildSchema());

        Notification::make()->title('Schema refreshed successfully.')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Schema')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('refreshSchema'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('documentation.view');
    }
}
