<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Role;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $roleGroups = [
            'all'               => ['label' => 'All',               'roles' => null],
            'upper_management'  => ['label' => 'Upper Management',  'roles' => [Role::UPPER_MANAGEMENT]],
            'middle_management' => ['label' => 'Middle Management', 'roles' => [Role::MIDDLE_MANAGEMENT]],
            'account_manager'   => ['label' => 'Account Manager',   'roles' => [Role::ACCOUNT_MANAGER]],
            'accreditation'     => ['label' => 'Accreditation',     'roles' => [Role::ACCREDITATION]],
            'csr'               => ['label' => 'CSR',               'roles' => [Role::CSR]],
            'claims_processor'  => ['label' => 'Claims Processor',  'roles' => [Role::CLAIMS_PROCESSOR]],
            'dentist'           => ['label' => 'Dentist',           'roles' => [Role::DENTIST]],
            'member'            => ['label' => 'Member',            'roles' => [Role::MEMBER]],
        ];

        $tabs = [];
        foreach ($roleGroups as $key => $group) {
            $query = \App\Models\User::query();
            if ($group['roles']) {
                $query->whereHas('roles', fn(Builder $q) => $q->whereIn('name', $group['roles']));
            }
            $count = $query->count();

            $tabs[$key] = Tab::make($group['label'])
                ->badge($count)
                ->modifyQueryUsing(function (Builder $query) use ($group) {
                    if ($group['roles']) {
                        $query->whereHas('roles', fn(Builder $q) => $q->whereIn('name', $group['roles']));
                    }
                });
        }

        return $tabs;
    }
}
