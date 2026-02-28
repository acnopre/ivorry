# Procedure Import Template

## Excel Column Headers

| Column Name | Description | Example | Required |
|------------|-------------|---------|----------|
| first_name | Member's first name | John | Yes |
| last_name | Member's last name | Doe | Yes |
| card_number | Member's card number | CARD-001 | Yes |
| service_name | Service/Procedure name | Oral Prophylaxis | Yes |
| clinic_name | Clinic name (must exist) | ABC Dental Clinic | Yes |
| availment_date | Date of procedure | 2024-01-15 | Yes |
| quantity | Quantity/count | 1 | Yes |
| applied_fee | Fee amount (optional, uses clinic service fee if empty) | 500.00 | No |
| remarks | Additional notes | Migrated from old system | No |

## Sample Data

```
first_name | last_name | card_number | service_name | clinic_name | availment_date | quantity | applied_fee | remarks
John | Doe | CARD-001 | Oral Prophylaxis | ABC Dental Clinic | 2024-01-15 | 1 | 500.00 | Initial migration
Jane | Smith | CARD-002 | Tooth Extraction | XYZ Dental Center | 2024-01-20 | 2 | | Migrated data
```

## Migration Mode

### When Enabled:
- Procedures are set to `processed` status (no approval needed)
- **Account Service Quantity**: Deducted (unless unlimited)
- **Member MBL Balance (Fixed type)**: Deducts `applied_fee` only
- **Member MBL Balance (Procedural type)**: No balance deduction
- All procedures marked with `is_migrated = true`

### When Disabled:
- Procedures are set to `pending` status
- No deductions applied
- Requires manual approval

## MBL Deduction Logic

### Fixed MBL Type:
- Deducts `quantity` from account service
- Deducts `applied_fee` from member's `mbl_balance`
- If `applied_fee` is empty, uses clinic service fee

### Procedural MBL Type:
- Only deducts `quantity` from account service
- No balance deduction

## Data Handling
- All whitespace at start/end of data is automatically trimmed
- All errors are captured and stored in import logs
- Member lookup uses: first_name + last_name + card_number
- Service lookup uses: service name (not code)

## Validations:
- Member must exist (by first_name, last_name, card_number)
- Service must exist (by service_name)
- Clinic must exist (by clinic_name)
- Service must be available in member's account
- Sufficient quantity in account service
- Sufficient MBL balance (for Fixed type, based on applied_fee + quantity)

## Usage

1. Go to **Import Logs** page in admin panel
2. Click **Import Procedures** button
3. Upload Excel file
4. Toggle **Migration Mode** if this is initial data migration
5. System will process and show results
6. Check Import Logs for detailed results

## Permissions Required

- `procedure.import` - To access import button
- `procedure.import.migration-mode` - To enable migration mode toggle
