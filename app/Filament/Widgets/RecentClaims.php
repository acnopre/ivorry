<?php

namespace App\Filament\Widgets;

use App\Models\Claim;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentClaims extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Claim::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Claim ID'),
                Tables\Columns\TextColumn::make('member.name')->label('Member'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ]);
    }
}
