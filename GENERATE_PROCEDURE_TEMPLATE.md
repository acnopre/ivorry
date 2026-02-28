# Generate Procedure Import Test Template

## Command

```bash
php artisan generate:procedure-template --rows=10
```

## Options

- `--rows=N` - Number of sample rows to generate (default: 10)

## Output

Generates an Excel file in `storage/app/public/imports/` with:
- Header row with all required columns
- Sample data rows (uses real data if available, otherwise generates sample data)

## Example Output

```
✅ Generated: /path/to/storage/app/public/imports/procedure_import_template_20260228220329.xlsx
📊 Rows: 5
Columns: first_name, last_name, card_number, service_name, clinic_name, availment_date, quantity, applied_fee, remarks
```

## Generated Columns

| Column | Sample Value |
|--------|-------------|
| first_name | John |
| last_name | Doe |
| card_number | CARD-001 |
| service_name | Oral Prophylaxis |
| clinic_name | Sample Dental Clinic |
| availment_date | 2024-02-27 |
| quantity | 1 |
| applied_fee | 500 |
| remarks | Sample data |

## Usage for Testing

1. Generate template:
   ```bash
   php artisan generate:procedure-template --rows=5
   ```

2. Edit the generated file with real data from your system

3. Import via **Import Logs** page in admin panel

4. Enable **Migration Mode** if testing initial data migration

## Notes

- If database has members/services/clinics, uses real data
- Otherwise generates sample data for testing
- File is saved in `storage/app/public/imports/`
- Filename includes timestamp to avoid conflicts
