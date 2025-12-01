<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // Role Constants
    public const SUPER_ADMIN       = 'Super Admin';
    public const CSR               = 'CSR';
    public const CLAIMS_PROCESSOR  = 'Claims Processor';
    public const ACCOUNT_MANAGER   = 'Account Manager';
    public const ACCREDITATION     = 'Accreditation';
    public const MIDDLE_MANAGEMENT = 'Middle Management';
    public const DENTIST           = 'Dentist';
    public const UPPER_MANAGEMENT  = 'Upper Management';
    public const MEMBER            = 'Member';

    // Optional: list of all roles in one array
    public const LIST = [
        self::SUPER_ADMIN,
        self::CSR,
        self::CLAIMS_PROCESSOR,
        self::ACCOUNT_MANAGER,
        self::ACCREDITATION,
        self::MIDDLE_MANAGEMENT,
        self::DENTIST,
        self::UPPER_MANAGEMENT,
        self::MEMBER,
    ];
}
