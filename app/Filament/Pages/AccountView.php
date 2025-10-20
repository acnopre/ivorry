<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\Account;

class AccountView extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string $view = 'filament.pages.account-view';

    public ?Account $account = null;

    public function mount($record = null)
    {
        $this->account = Account::with('services')->find($record);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Account::query())
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->label('Company'),
                Tables\Columns\TextColumn::make('policy_code')->label('Policy Code'),
                Tables\Columns\TextColumn::make('status'),
            ]);
    }
}
