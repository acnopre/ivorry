# HPDAI Import Documentation

Complete guide for importing Accounts, Members, and Procedures into the HPDAI system.

---

## Table of Contents

1. [Account Import](#account-import)
2. [Member Import](#member-import)
3. [Procedure Import](#procedure-import)
4. [Import Logs & Monitoring](#import-logs--monitoring)
5. [Troubleshooting](#troubleshooting)

---

## Account Import

### Overview

The Account Import feature allows bulk creation and management of healthcare accounts including NEW accounts, RENEWALS, and AMENDMENTS.

### Access Requirements

**Permissions:**
- `account.import` - Required to access import functionality
- `account.import.migration-mode` - Required to enable migration mode (auto-approval)

**Location:** Navigate to **Accounts** → Click **Import XLS** button in header

---

### Excel File Format

#### Required Columns

| Column Name | Type | Description | Required | Example |
|------------|------|-------------|----------|---------|
| `company_name` | Text | Company/Account name | Yes | ABC Corporation |
| `policy_code` | Text | Unique policy identifier | Yes | POL-2024-001 |
| `hip` | Text | Health Insurance Provider | Yes | Maxicare |
| `card_used` | Text | Card type/identifier | No | Blue Card |
| `plan_type` | Text | INDIVIDUAL or SHARED | Yes | INDIVIDUAL |
| `coverage_type` | Text | ACCOUNT or MEMBER | Yes | ACCOUNT |
| `effective_date` | Date | Coverage start date | Conditional* | 2024-01-01 |
| `expiration_date` | Date | Coverage end date | Conditional* | 2025-01-01 |
| `mbl_type` | Text | Procedural or Fixed | No (default: Procedural) | Fixed |
| `mbl_amount` | Number | MBL amount for Fixed type | Required if Fixed | 50000 |
| `endorsement_type` | Text | NEW, RENEWAL, or AMENDMENT | No (default: NEW) | NEW |
| `remarks` | Text | Additional notes | No | Initial setup |

**Conditional Requirements:**
- `effective_date` and `expiration_date` are **required** when `coverage_type` = ACCOUNT
- `effective_date` and `expiration_date` must be **empty** when `coverage_type` = MEMBER

#### Service Columns (Dynamic)

After the required columns, add columns for each dental service using the service **slug** as the column header:

| Service Slug | Description | Value Format |
|-------------|-------------|--------------|
| `oral-prophylaxis` | Oral cleaning | Number or "unlimited" |
| `tooth-extraction` | Tooth extraction | Number or "unlimited" |
| `composite-restoration` | Filling | Number or "unlimited" |
| `root-canal-treatment` | Root canal | Number or "unlimited" |
| ... | (other services) | ... |

**Service Value Rules:**
- **Basic Services**: Always unlimited (value ignored)
- **Enhancement Services**: Enter number or "unlimited"
- **Special Services**: Enter number or "unlimited"
- Leave blank to exclude service from account

---

### Endorsement Types

#### 1. NEW Account

Creates a brand new account in the system.

**Required Fields:**
- `company_name`, `policy_code`, `hip`, `plan_type`, `coverage_type`
- `effective_date` and `expiration_date` (if coverage_type = ACCOUNT)
- Service columns with quantities

**Behavior:**
- Creates new account record
- Status: `inactive` (or `active` if migration mode enabled)
- Endorsement Status: `PENDING` (or `APPROVED` if migration mode enabled)
- Skips if account with same `company_name` + `policy_code` already exists

**Example Row:**
```
company_name: ABC Corporation
policy_code: POL-2024-001
hip: Maxicare
card_used: Blue Card
plan_type: INDIVIDUAL
coverage_type: ACCOUNT
effective_date: 2024-01-01
expiration_date: 2025-01-01
mbl_type: Fixed
mbl_amount: 50000
endorsement_type: NEW
oral-prophylaxis: unlimited
tooth-extraction: 4
composite-restoration: 8
```

---

#### 2. RENEWAL

Renews an existing account with new coverage dates.

**Required Fields:**
- `company_name`, `policy_code` (to identify existing account)
- `effective_date`, `expiration_date` (new coverage period)
- `endorsement_type`: RENEWAL

**Behavior:**
- Finds existing account by `company_name` + `policy_code`
- Creates `AccountRenewal` record
- Copies all existing services with original quantities
- Updates account `endorsement_type` to RENEWAL
- Updates account `endorsement_status` to PENDING (or APPROVED if migration mode)
- Deletes any pending renewals before creating new one
- **Validation**: `effective_date` must be before `expiration_date`
- **Validation**: `expiration_date` cannot be in the past

**Example Row:**
```
company_name: ABC Corporation
policy_code: POL-2024-001
effective_date: 2025-01-01
expiration_date: 2026-01-01
endorsement_type: RENEWAL
```

**Note:** Service columns are ignored for renewals - services are copied from existing account.

---

#### 3. AMENDMENT

Modifies an existing account's details or services.

**Required Fields:**
- `company_name`, `policy_code` (to identify existing account)
- `endorsement_type`: AMENDMENT
- Any fields to be amended

**Optional Fields (amendable):**
- `company_name`, `policy_code`, `hip`, `card_used`
- `effective_date`, `expiration_date`
- `coverage_type`, `mbl_type`, `mbl_amount`
- Service columns (to modify service quantities)
- `remarks`

**Behavior:**
- Finds existing account by `company_name` + `policy_code`
- Creates `AccountAmendment` record with changes
- Updates account `endorsement_type` to AMENDMENT
- Updates account `endorsement_status` to PENDING (or APPROVED if migration mode)
- Deletes any pending amendments before creating new one
- If `mbl_type` changes in migration mode, handles MBL balance conversion

**Example Row (changing MBL and services):**
```
company_name: ABC Corporation
policy_code: POL-2024-001
mbl_type: Procedural
mbl_amount: 
endorsement_type: AMENDMENT
tooth-extraction: 8
composite-restoration: unlimited
remarks: Upgraded plan
```

---

### Migration Mode

**Purpose:** For initial data migration from legacy systems. Bypasses approval workflows.

**When Enabled:**
- Accounts are set to `active` status immediately
- Endorsement status set to `APPROVED` automatically
- No manual approval required
- MBL type changes are processed immediately

**When Disabled (Normal Mode):**
- Accounts are set to `inactive` status
- Endorsement status set to `PENDING`
- Requires manual approval by authorized users
- Changes are queued for review

**How to Enable:**
1. Click **Import XLS** button
2. Upload Excel file
3. Toggle **Migration Mode** switch
4. Click **Submit**

---

### Validation Rules

#### Account Level
- `company_name` cannot be empty
- `policy_code` must be unique for NEW accounts
- `plan_type` must be INDIVIDUAL or SHARED
- `coverage_type` must be ACCOUNT or MEMBER
- `mbl_type` must be Procedural or Fixed
- If `mbl_type` = Fixed, `mbl_amount` is required

#### Coverage Period
- If `coverage_type` = ACCOUNT:
  - `effective_date` and `expiration_date` are required
  - `effective_date` must be before `expiration_date`
- If `coverage_type` = MEMBER:
  - `effective_date` and `expiration_date` must be empty

#### Endorsement Specific
- **RENEWAL**: Requires existing account, valid date range
- **AMENDMENT**: Requires existing account
- **NEW**: Account must not already exist

---

### Import Process

1. **File Upload**
   - Navigate to Accounts page
   - Click **Import XLS** button
   - Select Excel file (.xls or .xlsx)
   - Toggle Migration Mode if needed
   - Click Submit

2. **Processing**
   - System processes file in chunks of 500 rows
   - Creates ImportLog record to track progress
   - Processes asynchronously using queue system
   - Each row is validated before processing

3. **Results**
   - Success notification appears
   - Check **Import Logs** page for detailed results
   - View success/error/skipped counts
   - Download error details if needed

---

### Sample Excel Template

```
| company_name | policy_code | hip | card_used | plan_type | coverage_type | effective_date | expiration_date | mbl_type | mbl_amount | endorsement_type | oral-prophylaxis | tooth-extraction | composite-restoration |
|--------------|-------------|-----|-----------|-----------|---------------|----------------|-----------------|----------|------------|------------------|------------------|------------------|-----------------------|
| ABC Corp | POL-001 | Maxicare | Blue | INDIVIDUAL | ACCOUNT | 2024-01-01 | 2025-01-01 | Fixed | 50000 | NEW | unlimited | 4 | 8 |
| XYZ Inc | POL-002 | Intellicare | Gold | SHARED | ACCOUNT | 2024-02-01 | 2025-02-01 | Procedural | | NEW | unlimited | unlimited | unlimited |
| ABC Corp | POL-001 | | | | | 2025-01-01 | 2026-01-01 | | | RENEWAL | | | |
```

---

## Member Import

### Overview

Import members into existing accounts. Supports both new member creation and updating existing members.

### Access Requirements

**Permissions:**
- `member.import` - Required to access import functionality

**Location:** Navigate to **Members** → Click **Import XLS** button in header

---

### Excel File Format

#### Required Columns

| Column Name | Type | Description | Required | Example |
|------------|------|-------------|----------|---------|
| `account_name` | Text | Company name (must match existing account) | Yes | ABC Corporation |
| `first_name` | Text | Member's first name | Yes | Juan |
| `last_name` | Text | Member's last name | Yes | Dela Cruz |
| `middle_name` | Text | Member's middle name | No | Santos |
| `suffix` | Text | Name suffix (Jr., Sr., III) | No | Jr. |
| `member_type` | Text | PRINCIPAL or DEPENDENT | Yes | PRINCIPAL |
| `card_number` | Text | Unique card identifier | Yes | CARD-2024-001 |
| `birthdate` | Date | Date of birth | No | 1990-05-15 |
| `gender` | Text | male or female | No | male |
| `email` | Text | Email address | No | juan@example.com |
| `phone` | Text | Contact number | No | 09171234567 |
| `address` | Text | Complete address | No | 123 Main St, Manila |
| `status` | Text | ACTIVE or INACTIVE | Yes | ACTIVE |
| `inactive_date` | Date | Date when member became inactive | Conditional* | 2024-12-31 |
| `effective_date` | Date | Coverage start date | Conditional** | 2024-01-01 |
| `expiration_date` | Date | Coverage end date | Conditional** | 2025-01-01 |

**Conditional Requirements:**
- `inactive_date` is required when `status` = INACTIVE
- `effective_date` and `expiration_date` are **required** when account's `coverage_period_type` = MEMBER

---

### Member Types

#### PRINCIPAL
- Primary account holder
- **SHARED plans**: Only ONE principal allowed per account
- **INDIVIDUAL plans**: Each member is a principal
- If principal is set to INACTIVE in SHARED plan, all dependents are automatically set to INACTIVE

#### DEPENDENT
- Family member or dependent of principal
- Only allowed in SHARED plans
- Cannot add dependent without existing principal
- Inherits account benefits

---

### Import Behavior

#### New Member
If member doesn't exist (by `first_name` + `last_name` + `account_id`):
- Creates new member record
- Creates user account with "Member" role
- Default password: "password"
- Sets MBL balance (if account has Fixed MBL type)
- Sends welcome notification (if email provided)

#### Existing Member
If member exists (by `first_name` + `last_name` + `account_id`):
- Updates `status` field
- Updates `inactive_date` if provided
- Updates `effective_date` if provided
- Updates `expiration_date` if provided
- Does NOT update other fields (name, email, etc.)

---

### Validation Rules

#### Required Fields
- `account_name` must match existing account
- `first_name`, `last_name`, `member_type`, `card_number`, `status` are required

#### Member Type
- Must be PRINCIPAL or DEPENDENT
- SHARED plans: Only 1 principal allowed
- Cannot add DEPENDENT without existing PRINCIPAL

#### Status
- Must be ACTIVE or INACTIVE
- If INACTIVE, `inactive_date` should be provided

#### Email & Contact
- Email must be valid format if provided
- Email is optional but recommended

#### Age Validation
- Birthdate cannot be in the future
- Age cannot exceed 120 years

#### Coverage Period
- If account's `coverage_period_type` = MEMBER:
  - `effective_date` and `expiration_date` are required
- If account's `coverage_period_type` = ACCOUNT:
  - Member inherits account's coverage dates

---

### Import Process

1. **File Upload**
   - Navigate to Members page
   - Click **Import XLS** button
   - Select Excel file (.xls or .xlsx)
   - Click Submit

2. **Processing**
   - Processes in chunks of 500 rows
   - Creates ImportLog record
   - Validates each row before processing
   - Creates user accounts automatically

3. **Results**
   - Success notification with counts
   - Check Import Logs for details
   - Failed rows are logged with error messages

---

### Sample Excel Template

```
| account_name | first_name | last_name | middle_name | suffix | member_type | card_number | birthdate | gender | email | phone | status | effective_date | expiration_date |
|--------------|------------|-----------|-------------|--------|-------------|-------------|-----------|--------|-------|-------|--------|----------------|-----------------|
| ABC Corp | Juan | Dela Cruz | Santos | Jr. | PRINCIPAL | CARD-001 | 1990-05-15 | male | juan@email.com | 09171234567 | ACTIVE | 2024-01-01 | 2025-01-01 |
| ABC Corp | Maria | Dela Cruz | | | DEPENDENT | CARD-002 | 2015-03-20 | female | | | ACTIVE | 2024-01-01 | 2025-01-01 |
| XYZ Inc | Pedro | Santos | | | PRINCIPAL | CARD-003 | 1985-08-10 | male | pedro@email.com | 09181234567 | INACTIVE | | |
```

---

### Special Cases

#### Updating Member Status
To change a member from ACTIVE to INACTIVE:
```
account_name: ABC Corp
first_name: Juan
last_name: Dela Cruz
card_number: CARD-001
status: INACTIVE
inactive_date: 2024-12-31
```

#### SHARED Plan Principal Deactivation
When principal is set to INACTIVE:
- All dependents automatically set to INACTIVE
- All dependents get same `inactive_date`
- Cannot be reversed through import (requires manual intervention)

---

## Procedure Import

### Overview

Import historical or bulk procedure records. Supports migration mode for initial data loading.

### Access Requirements

**Permissions:**
- `procedure.import` - Required to access import functionality
- `procedure.import.migration-mode` - Required to enable migration mode

**Location:** Navigate to **Import Logs** → Click **Import Procedures** button

---

### Excel File Format

#### Required Columns

| Column Name | Type | Description | Required | Example |
|------------|------|-------------|----------|---------|
| `first_name` | Text | Member's first name | Yes | Juan |
| `last_name` | Text | Member's last name | Yes | Dela Cruz |
| `card_number` | Text | Member's card number | Yes | CARD-001 |
| `service_name` | Text | Service/procedure name (not slug) | Yes | Oral Prophylaxis |
| `clinic_name` | Text | Clinic name (must exist) | Yes | Bright Smile Dental Clinic |
| `availment_date` | Date | Date procedure was performed | Yes | 2024-01-15 |
| `quantity` | Number | Number of units/teeth | Yes | 1 |
| `applied_fee` | Number | Fee charged (optional) | No | 500.00 |
| `remarks` | Text | Additional notes | No | Migrated from old system |

---

### Field Details

#### Member Identification
- System finds member using: `first_name` + `last_name` + `card_number`
- All three fields must match exactly
- Member must exist in system

#### Service Name
- Use full service name, not slug
- Example: "Oral Prophylaxis" not "oral-prophylaxis"
- Service must exist in system
- Service must be available in member's account

#### Clinic Name
- Use exact clinic name as registered
- Clinic must exist in system
- Service must be available in clinic's service list

#### Applied Fee
- Optional field
- If empty, uses clinic's service fee
- If provided, uses specified amount
- Used for MBL deduction (Fixed type only)

#### Quantity
- Must be positive number
- Represents units/teeth/sessions
- Deducted from account service quantity

---

### Migration Mode

**Purpose:** For importing historical data from legacy systems.

#### When Enabled:
- Procedures set to `processed` status (no approval needed)
- **Account Service Quantity**: Deducted immediately (unless unlimited)
- **Member MBL Balance**: Deducted immediately (Fixed type only)
- All procedures marked with `is_migrated = true`
- No approval workflow triggered

#### When Disabled (Normal Mode):
- Procedures set to `pending` status
- No deductions applied
- Requires manual approval
- Goes through normal approval workflow

---

### MBL Deduction Logic

#### Fixed MBL Type
1. Deducts `quantity` from account service
2. Deducts `applied_fee` from member's `mbl_balance`
3. If `applied_fee` is empty, uses clinic service fee
4. Validates sufficient balance before processing

**Example:**
- Member MBL Balance: ₱50,000
- Applied Fee: ₱500
- After procedure: ₱49,500

#### Procedural MBL Type
1. Only deducts `quantity` from account service
2. No balance deduction from member
3. Tracks procedure count only

**Example:**
- Account Service: 4 extractions remaining
- Quantity: 1
- After procedure: 3 extractions remaining

---

### Validation Rules

#### Member Validation
- Member must exist (by first_name + last_name + card_number)
- Member must be ACTIVE
- Member's account must be ACTIVE

#### Service Validation
- Service must exist in system
- Service must be in member's account benefits
- Sufficient quantity available (unless unlimited)

#### Clinic Validation
- Clinic must exist in system
- Clinic must be ACTIVE/ACCREDITED
- Service must be available in clinic
- Clinic must have fee set for service

#### Balance Validation (Fixed MBL)
- Member must have sufficient MBL balance
- Balance >= applied_fee (or clinic service fee)

#### Quantity Validation
- Quantity must be positive number
- Account service must have sufficient quantity (unless unlimited)

#### Duplicate Prevention
- Checks for existing procedure with same:
  - Member + Service + Clinic + Availment Date
  - Only for migrated procedures
- Prevents duplicate imports

---

### Import Process

1. **File Upload**
   - Navigate to Import Logs page
   - Click **Import Procedures** button
   - Select Excel file (.xls or .xlsx)
   - Toggle **Migration Mode** if needed
   - Click Submit

2. **Processing**
   - Processes in chunks of 500 rows
   - Creates ImportLog record
   - Validates each row thoroughly
   - Applies deductions if migration mode enabled

3. **Results**
   - Success notification with counts
   - Check Import Logs for detailed results
   - Failed rows logged with specific error messages

---

### Sample Excel Template

```
| first_name | last_name | card_number | service_name | clinic_name | availment_date | quantity | applied_fee | remarks |
|------------|-----------|-------------|--------------|-------------|----------------|----------|-------------|---------|
| Juan | Dela Cruz | CARD-001 | Oral Prophylaxis | Bright Smile Dental Clinic | 2024-01-15 | 1 | 500.00 | Initial cleaning |
| Maria | Santos | CARD-002 | Tooth Extraction | ABC Dental Center | 2024-02-20 | 2 | | Wisdom teeth |
| Pedro | Reyes | CARD-003 | Composite Restoration | Bright Smile Dental Clinic | 2024-03-10 | 1 | 800.00 | Front tooth filling |
```

---

### Common Scenarios

#### Scenario 1: Basic Procedure Import (Migration Mode)
```
first_name: Juan
last_name: Dela Cruz
card_number: CARD-001
service_name: Oral Prophylaxis
clinic_name: Bright Smile Dental Clinic
availment_date: 2024-01-15
quantity: 1
applied_fee: 500.00
remarks: Migrated data
```

**Result:**
- Procedure created with status: `processed`
- Account service quantity: Deducted by 1
- Member MBL balance: Deducted by ₱500 (if Fixed type)

#### Scenario 2: Multiple Tooth Extraction
```
first_name: Maria
last_name: Santos
card_number: CARD-002
service_name: Tooth Extraction
clinic_name: ABC Dental Center
availment_date: 2024-02-20
quantity: 2
applied_fee: 
remarks: Wisdom teeth removal
```

**Result:**
- Uses clinic's service fee (e.g., ₱800 per tooth)
- Total deduction: ₱1,600 from MBL (if Fixed type)
- Quantity deducted: 2 from account service

#### Scenario 3: Unlimited Service
```
first_name: Pedro
last_name: Reyes
card_number: CARD-003
service_name: Oral Prophylaxis
clinic_name: Bright Smile Dental Clinic
availment_date: 2024-03-10
quantity: 1
applied_fee: 500.00
```

**Result (if Oral Prophylaxis is unlimited):**
- Procedure created
- No quantity deduction (unlimited)
- MBL balance deducted: ₱500 (if Fixed type)

---

## Import Logs & Monitoring

### Overview

All imports are tracked in the Import Logs system for auditing and troubleshooting.

### Accessing Import Logs

**Location:** Navigate to **Import Logs** in the admin panel

### Import Log Information

Each import log contains:
- **Filename**: Original uploaded file name
- **Import Type**: account, member, or procedure
- **Status**: processing, completed, partial, failed
- **User**: Who initiated the import
- **Timestamp**: When import was started
- **Statistics**:
  - Total Rows
  - Success Rows
  - Error Rows
  - Skipped Rows

### Viewing Details

Click on any import log to view:
- Row-by-row results
- Error messages for failed rows
- Raw data for each row
- Success/failure status per row

### Export Error Report

1. Open import log details
2. Click **Export Errors** button
3. Download Excel file with failed rows
4. Fix errors and re-import

---

## Troubleshooting

### Common Errors

#### Account Import

**Error:** "Company name is required"
- **Cause:** Empty company_name field
- **Solution:** Ensure all rows have company_name filled

**Error:** "Account already exists"
- **Cause:** Duplicate company_name + policy_code
- **Solution:** Use AMENDMENT or RENEWAL instead of NEW

**Error:** "Account not found for renewal"
- **Cause:** No existing account matches company_name + policy_code
- **Solution:** Verify account exists, check spelling

**Error:** "Invalid plan_type. Must be INDIVIDUAL or SHARED"
- **Cause:** Typo or invalid value in plan_type
- **Solution:** Use exactly "INDIVIDUAL" or "SHARED"

**Error:** "MBL amount is required when mbl_type is Fixed"
- **Cause:** mbl_type = Fixed but mbl_amount is empty
- **Solution:** Provide numeric mbl_amount value

**Error:** "Coverage type ACCOUNT requires effective_date and expiration_date"
- **Cause:** Missing dates for ACCOUNT coverage type
- **Solution:** Provide both dates in YYYY-MM-DD format

---

#### Member Import

**Error:** "Account 'XYZ Corp' not found"
- **Cause:** account_name doesn't match any existing account
- **Solution:** Verify account exists, check exact spelling

**Error:** "Required fields: first_name, last_name, member_type, card_number"
- **Cause:** One or more required fields are empty
- **Solution:** Fill all required fields

**Error:** "Invalid member_type. Must be PRINCIPAL or DEPENDENT"
- **Cause:** Typo or invalid value
- **Solution:** Use exactly "PRINCIPAL" or "DEPENDENT"

**Error:** "Account with SHARED plan type can only have 1 PRINCIPAL member"
- **Cause:** Trying to add second principal to SHARED plan
- **Solution:** Change member_type to DEPENDENT or use different account

**Error:** "Cannot add DEPENDENT member without a PRINCIPAL member in the account"
- **Cause:** No principal exists yet in SHARED plan
- **Solution:** Import principal first, then dependents

**Error:** "Invalid email format"
- **Cause:** Email doesn't match valid format
- **Solution:** Use valid email format (user@domain.com)

**Error:** "Birthdate cannot be in the future"
- **Cause:** Birthdate is after today's date
- **Solution:** Correct the birthdate

**Error:** "Effective date and expiration date are required when account coverage type is MEMBER"
- **Cause:** Missing dates for MEMBER coverage type
- **Solution:** Provide both dates

---

#### Procedure Import

**Error:** "Member not found"
- **Cause:** No member matches first_name + last_name + card_number
- **Solution:** Verify member exists, check spelling and card number

**Error:** "Service 'Tooth Extraction' not found"
- **Cause:** Service name doesn't match system records
- **Solution:** Use exact service name from system

**Error:** "Clinic 'ABC Dental' not found"
- **Cause:** Clinic name doesn't match system records
- **Solution:** Use exact clinic name from system

**Error:** "Service not available in member's account"
- **Cause:** Member's account doesn't include this service
- **Solution:** Add service to account first or use different service

**Error:** "Service 'Oral Prophylaxis' not available in clinic 'ABC Dental'"
- **Cause:** Clinic doesn't offer this service
- **Solution:** Add service to clinic or use different clinic

**Error:** "Insufficient MBL balance"
- **Cause:** Member's MBL balance < applied_fee
- **Solution:** Top up MBL balance or reduce applied_fee

**Error:** "Insufficient service quantity"
- **Cause:** Account service quantity < requested quantity
- **Solution:** Add more quantity to account service

**Error:** "Quantity must be a positive number"
- **Cause:** Quantity is 0, negative, or non-numeric
- **Solution:** Use positive number (1, 2, 3, etc.)

**Error:** "Procedure already exists for this member on [date]"
- **Cause:** Duplicate procedure for same member/service/clinic/date
- **Solution:** Check if already imported, adjust date if different procedure

---

### Best Practices

#### Before Importing

1. **Backup Database**: Always backup before large imports
2. **Test with Small File**: Import 5-10 rows first to validate format
3. **Verify Dependencies**: Ensure accounts exist before importing members
4. **Check Service Names**: Use exact names from system
5. **Validate Dates**: Use YYYY-MM-DD format or Excel date format

#### During Import

1. **Monitor Progress**: Check Import Logs page for status
2. **Don't Re-upload**: Wait for current import to complete
3. **Check Notifications**: System sends notifications on completion

#### After Import

1. **Review Import Log**: Check success/error counts
2. **Export Errors**: Download and fix failed rows
3. **Verify Data**: Spot-check imported records
4. **Re-import Failures**: Fix errors and re-import failed rows

---

### Date Format Guidelines

**Accepted Formats:**
- Excel date format (numeric): 45292
- ISO format: 2024-01-15
- Standard format: 01/15/2024

**Recommended:** Use Excel date format for consistency

---

### Performance Tips

1. **Chunk Size**: System processes 500 rows at a time
2. **Large Files**: Split files > 10,000 rows into smaller batches
3. **Queue System**: Imports run in background, don't wait on page
4. **Migration Mode**: Use only for initial data migration
5. **Off-Peak Hours**: Schedule large imports during low-traffic periods

---

### Support

For additional help:
1. Check Import Logs for specific error messages
2. Review this documentation
3. Contact system administrator
4. Check application logs: `storage/logs/laravel.log`

---

## Appendix

### Service Types

- **Basic Services**: Always unlimited (oral prophylaxis, consultation, etc.)
- **Enhancement Services**: Countable (extractions, fillings, etc.)
- **Special Services**: Advanced procedures (root canal, crowns, etc.)

### Account Statuses

- **active**: Account is currently active and usable
- **inactive**: Account created but not yet activated
- **expired**: Account past expiration date

### Endorsement Statuses

- **PENDING**: Awaiting approval
- **APPROVED**: Approved and active
- **REJECTED**: Rejected, not processed

### Member Statuses

- **active**: Member can avail services
- **inactive**: Member cannot avail services

### Procedure Statuses

- **pending**: Awaiting approval
- **approved**: Approved, awaiting processing
- **processed**: Completed and deductions applied
- **rejected**: Rejected, no deductions

---

**Document Version:** 1.0  
**Last Updated:** 2024  
**System:** HPDAI - Healthcare Plan Dental Administration Interface
