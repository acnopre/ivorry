# HPDAI System Documentation
## Healthcare Plan Dental Administration Interface

---

## Table of Contents

1. [System Overview](#system-overview)
2. [User Roles & Permissions](#user-roles--permissions)
3. [Dashboard](#dashboard)
4. [Module: Accounts & Members](#module-accounts--members)
   - [Accounts](#accounts)
   - [Members](#members)
5. [Module: Dental Management](#module-dental-management)
   - [Clinic Details](#clinic-details)
   - [Dentists](#dentists)
   - [My Procedures (Dentist View)](#my-procedures-dentist-view)
   - [Service Fee Approval](#service-fee-approval)
6. [Module: Claims Management](#module-claims-management)
   - [Search Claims](#search-claims)
   - [Generated ADC](#generated-adc)
7. [Module: Search](#module-search)
   - [Search Member](#search-member)
   - [Search Clinics](#search-clinics)
8. [Module: Reports](#module-reports)
9. [Module: Imports](#module-imports)
10. [Reference Data (Admin)](#reference-data-admin)

---

## System Overview

HPDAI is a dental healthcare administration system that manages the full lifecycle of dental health plans — from account creation and member enrollment, to clinic accreditation, procedure recording, claims processing, and SOA (Statement of Accounts) generation.

---

## User Roles & Permissions

| Role | Description |
|---|---|
| Super Admin | Full system access |
| Upper Management | Approvals, reports, dashboard overview |
| Middle Management | Account approvals |
| Account Manager | Manage accounts and members |
| Claims Processor | Process and print ADC claims |
| Accreditation | Manage clinic accreditation |
| CSR (Customer Service Rep) | Search members, add procedures, sign claims |
| Dentist | View own procedures, sign procedures |
| Member | View own profile and benefits |

---

## Dashboard

The dashboard is role-based and shows different widgets depending on the logged-in user's role.

| Role | Widgets Shown |
|---|---|
| Super Admin / Upper Management | Overall stats, account stats, recent claims, activity timeline |
| Account Manager | Account statistics |
| Claims Processor | Claims statistics |
| Accreditation | Accreditation statistics |
| Dentist | Dentist-specific stats |
| Member | Member benefit stats |
| CSR | CSR-specific stats |

---

## Module: Accounts & Members

### Accounts

**Navigation:** Accounts & Members → Accounts

Manages corporate/group dental health plan accounts.

#### Creating an Account

Fill in the following sections:

**Account Information**
| Field | Description |
|---|---|
| Company Name | Name of the corporate client |
| Policy Code | Unique identifier for the policy (must be unique) |
| HIP | Health Insurance Provider |
| Card Used | Type of card used |
| Plan Type | `Individual` — each member has their own MBL; `Shared` — members share a pool |
| Coverage Period Type | `Account` — all members share the account's effective/expiration dates; `Member` — each member has individual dates |
| MBL Type | `Procedural` — limits by service quantity; `Fixed` — limits by peso amount |
| MBL Amount | Required only when MBL Type is `Fixed` |

**Contract Information**
| Field | Description |
|---|---|
| Effective Date | Start date of coverage. Auto-calculates expiration date (1 year minus 1 day) |
| Valid Until | Expiration date (auto-filled) |
| Endorsement Type | `NEW` on creation. On edit: `RENEWAL`, `AMENDMENT` available |

**Endorsement Types Explained**
- `NEW` — Initial account creation
- `RENEWAL` — Extends the account for another period. Resets all service quantities to defaults. Effective date must differ from current.
- `AMENDMENT` — Modifies account details, services, or members without changing the period

**Services**

Three service categories are configured per account:
- **Basic Dental Services** — Always unlimited; remarks can be added on amendment
- **Plan Enhancements** — Can be set to a specific quantity or unlimited
- **Special Procedures** — Can be set to a specific quantity or unlimited

#### Account List & Filters

The accounts list can be filtered by:
- Endorsement Type (NEW, RENEWAL, AMENDMENT)
- Endorsement Status (Pending, Approved, Rejected)
- Account Status (Active, Inactive, Expired)
- Plan Type (Individual, Shared)

#### Account Statuses

| Status | Meaning |
|---|---|
| Pending | Awaiting approval |
| Approved | Account is active and usable |
| Rejected | Account was not approved |
| Active | Account is within coverage period |
| Inactive | Account is not yet active |
| Expired | Account coverage has ended |

#### Bulk Actions

- **Approve Selected** — Approves multiple `NEW` accounts at once (sets to Approved + Active). Renewal/Amendment accounts must be approved individually.

#### Excel Import

Users with `account.import` permission can bulk-import accounts via Excel.
- Upload `.xls` or `.xlsx` file
- Optional **Migration Mode**: Auto-approves imported accounts (requires `account.import.migration-mode` permission)
- Import progress is tracked in Import Logs

---

### Members

**Navigation:** Accounts & Members → Members

Manages individual members enrolled under an account.

#### Creating a Member

**Member Information**
| Field | Description |
|---|---|
| Account | Select an active account |
| Card Number | Unique member card number |
| COC Number | Alternative to card number (toggle "Enable COC Number") |
| First / Last / Middle Name | Member's full name |
| Suffix | e.g., Jr., Sr. |
| Member Type | `Principal` or `Dependent` |
| Status | `Active` or `Inactive` |
| Inactive Date | Visible when status is Inactive |
| Birthdate | Member's date of birth |
| Gender | Male / Female |

**Contact Details**
- Email, Phone

**Contract Information** *(visible only when account's Coverage Period Type is `Member`)*
- Effective Date — auto-calculates expiration (1 year minus 1 day)
- Expiration Date

#### Member List & Filters

Filter by:
- Status (Active / Inactive)

#### Excel Import

Users with `member.import` permission can bulk-import members via Excel.

---

## Module: Dental Management

### Clinic Details

**Navigation:** Dental Management → Clinic Details

Manages accredited dental clinics.

#### Creating a Clinic

**Clinic Information**
- Name on signage, Registered Name
- Location: Region → Province → City/Municipality → Barangay → Street (cascading dropdowns)
- Contact: Landline, Mobile, Viber, Email
- Alternative Address

**PTR Information**
- PTR Number, Date Issued

**Accreditation & Tax**
- Registered Name (per BIR 2303), TIN
- Is Branch toggle
- Complete Address
- BIR Form 1903 Update Type
- VAT Type, Withholding Tax, Business Type
- SEC Registration No.

**Clinic Staff**
- Staff Name, Mobile, Viber, Email

**Bank Information**
- Bank Account Name/Number, Bank Name/Branch, Account Type, Remarks

**Associate Dentists**
- Add multiple dentists with: Last Name, First Name, M.I., PRC License No., PRC Expiry Date, Is Owner toggle, Specializations
- Only one dentist can be marked as Owner

**Services**
- Basic Dental Services: Set fee per service
- Plan Enhancements: Set fee and new fee (for fee update requests)

**Status**
| Accreditation Status | Description |
|---|---|
| ACTIVE | Clinic is fully accredited |
| INACTIVE | Clinic is not currently accredited |
| SILENT | Clinic is on hold |
| SPECIFIC HIP | Accredited only for a specific HIP |
| SPECIFIC ACCOUNT | Accredited only for a specific account |

#### Excel Import

Users with `clinic.import` permission can bulk-import clinics via Excel.

---

### Dentists

**Navigation:** Dental Management → Dentists

Manages individual dentists associated with clinics.

| Field | Description |
|---|---|
| Clinic | The clinic this dentist belongs to |
| Last Name / First Name / M.I. | Dentist's name |
| PRC License No. | Professional Regulation Commission license number |
| PRC Expiration Date | License expiry date |
| Is Owner | Marks the dentist as the clinic owner |
| Specializations | Multiple specializations can be assigned |

> Dentist users only see dentists from their own clinic.

---

### My Procedures (Dentist View)

**Navigation:** Dental Management → My Procedures

Dentists can view and manage procedures submitted from their clinic.

#### Procedure Statuses

| Status | Description |
|---|---|
| Pending | Submitted, awaiting dentist signature |
| Signed | Dentist has signed; awaiting claims review |
| Valid | Approved by claims processor |
| Invalid | Rejected by claims processor |
| Returned | Sent back to dentist for correction |
| Processed | Included in a generated ADC |
| Cancelled | Cancelled by dentist or CSR |

#### Available Actions

- **Cancel** — Cancel a pending procedure (requires reason)
- **Sign Procedure** — Opens the signing page for pending procedures
- **Resubmit** — Resubmit a returned procedure back to `Signed` status

---

### Service Fee Approval

**Navigation:** Dental Management → Service Fee Approval

Displays clinics with pending service fee update requests.

- Shows current fee, proposed new fee, and the difference per service
- Users with `fee.approval` permission can approve fee changes
- On approval:
  - If processed procedures exist for the service, a fee adjustment procedure is automatically created
  - The clinic's fee is updated and approval status is set to `APPROVED`

> Navigation badge shows the count of clinics with pending fee approvals.

---

## Module: Claims Management

### Search Claims

**Navigation:** Search → Search Claims

Used by Claims Processors to search, review, validate, and generate ADC (Acknowledgment of Dental Claims) documents.

#### Search Filters

| Filter | Description |
|---|---|
| Approval Code | Search by specific approval code |
| Member Name | Search by first or last name |
| Clinic | Required — select the clinic |
| Claim Status | Filter by procedure status |
| Availment From / To | Required — date range for availment |

#### Claim Actions (from View modal)

For `Signed` procedures:
- **Valid** — Mark the claim as valid (requires `claims.valid` permission)
- **Rejected** — Reject the claim with a reason; returns the service quantity to the account (requires `claims.reject` permission)
- **Return** — Send the claim back to the dentist with a reason (requires `claims.return` permission)

#### Generating ADC (Print Claims)

The **Print ADC** button processes all `Valid` procedures in the current search results:
1. Calculates fees including VAT and EWT (withholding tax) per clinic settings
2. Creates a `GeneratedSoa` record
3. Generates two PDF copies: **Original** (sent to printer) and **Duplicate** (available for download)
4. Marks all included procedures as `Processed` with an ADC number
5. Logs the print activity

The **Generate Return** button processes `Returned` procedures similarly.

> ADC sequence number format: `ADC0000000001`

---

### Generated ADC

**Navigation:** Claims Management → Generated ADC

Lists all generated ADC documents.

#### Columns

| Column | Description |
|---|---|
| ADC ID | Unique identifier |
| Clinic | Clinic that submitted the claims |
| From / To | Availment date range covered |
| Total | Total amount in the ADC |
| Status | ADC status |
| Generated By | User who generated the ADC |
| Generated At | Timestamp |
| Print Count | Number of times printed |
| Original Request | Status of original copy request |

#### Actions

| Action | Description | Permission |
|---|---|---|
| Request Original | Request an original printed copy | `generated_adc.request` |
| Approve Original | Approve the original copy request | `generated_adc.approve` |
| Print Original ADC | Print the original copy (after approval, once only) | `generated_adc.print_original` |
| Download Duplicate | Download the duplicate PDF copy | Available to all with view access |

#### Filters

- ADC Status (Pending, Approved, Rejected)
- Clinic
- Generated Date range (From / Until)

---

## Module: Search

### Search Member

**Navigation:** Search → Search Member

Used by Dentists and CSRs to find members and add dental procedures.

#### Search Fields

| Field | Notes |
|---|---|
| Card Number | Required for Dentist role |
| First Name | Optional |
| Last Name | Optional |

> Dentist role: limited to 1 result. CSR role: returns all matches.
> Clinics with `SPECIFIC ACCOUNT` accreditation only see members from their assigned account.

#### Member Card Display

Each found member shows:
- Full name, card number, account name, HIP
- Coverage dates (effective / expiration)
- MBL balance (for Fixed MBL type) or service quantities (for Procedural type)
- Active procedures

#### Adding a Procedure

Click **Add Procedure** on a member card. The button is disabled if:
- Member is not Active
- Member coverage has not started or has expired
- Account is not Active
- Account coverage has not started or has expired

**Procedure Form Fields**

| Field | Description |
|---|---|
| Clinic | Auto-filled for Dentist; selectable for CSR without assigned clinic |
| Service | Filtered by account's available services with remaining quantity |
| Fee | Visible for Fixed MBL accounts; editable by CSR only |
| Unit Type | Displayed based on selected service |
| Quantity | Limited by service balance and max-per-date rule |
| Units | Tooth / Quadrant / Arch / Canal / Surface (depends on service unit type) |
| Availment Date | CSR: ±3–5 days from today; Dentist: today only |

#### Business Rules Enforced

- **Special services** can only be added by CSR (Dentist must call HPDAI)
- **Multiple clinic restriction**: Member cannot have procedures at different clinics on the same date
- **Exclusive services**: Consultation and Simple Tooth Extraction cannot be combined with other procedures on the same date/tooth
- **Service pair restrictions**: e.g., Oral Prophylaxis cannot be done with Treatment of Sores or Desensitization on the same date
- **Tooth-specific rules**:
  - Temporary and Permanent fillings cannot be on the same tooth on the same date
  - Extraction can only be done once per tooth
  - No other services on an already-extracted tooth
- **MBL balance check**: For Fixed MBL, total fee must not exceed remaining balance
- **Quantity check**: For Procedural MBL, service must have remaining quantity

After saving, an **Approval Code** is generated and displayed.

#### Cancelling a Procedure

Pending procedures can be cancelled from the member card with a reason.

---

### Search Clinics

**Navigation:** Search → Search Clinics

Search for accredited clinics by location or dentist.

#### Search Filters

| Filter | Description |
|---|---|
| Region | Filter by region |
| Province | Filter by province (depends on region) |
| City / Municipality | Filter by city (depends on province) |
| Dentist Last Name | Search by dentist surname |
| Specialization | Filter by one or more specializations |
| Accreditation Status | Visible to Super Admin and CSR only |
| HIP | Visible when Accreditation Status is `SPECIFIC HIP` |
| Account | Visible when Accreditation Status is `SPECIFIC ACCOUNT` |

Results show clinic details including associated dentists and their specializations.

---

## Module: Reports

**Navigation:** Reports → Reports

Generate and export reports for various data types.

#### Report Types

| Report | Description |
|---|---|
| Member Status Report | Members with status, type, account, HIP, dates |
| Dentist List Report | Dentists with clinic, specialization, location |
| Clinic Accreditation Status Report | Clinics with accreditation, VAT, business type, location |
| Availment Report | Procedures with member, clinic, service, fee, approval code |
| Account Status Report | Accounts with plan type, HIP, coverage dates, status |

#### How to Generate a Report

1. Select a **Report Type**
2. Apply relevant filters (vary by report type)
3. Click **Generate Report**
4. Results appear in the table below
5. Click **Export XLS** to download as Excel

#### Common Filters

- Date range (Created From / To, or Availment From / To for procedures)
- HIP, Account, Clinic, Status
- Location (Region, Province, Municipality) for Dentist and Clinic reports
- Member Type, Import Source for Member reports
- Procedure Status for Availment reports

> At least one filter must be applied before generating.

---

## Module: Imports

**Navigation:** Imports → Import Logs

Tracks all Excel import operations performed in the system.

#### Import Log Columns

| Column | Description |
|---|---|
| Filename | Original uploaded file name |
| Import Type | `account`, `member`, or `procedure` |
| Batch Status | `active` or `deleted` |
| Imported By | User who performed the import |
| Status | `processing`, `partial`, `failed` |
| Total Rows | Total rows in the file |
| Success Rows | Successfully imported rows |
| Skipped Rows | Rows skipped (duplicates, etc.) |
| Error Rows | Rows that failed |
| Created At | Import timestamp |

#### Actions

| Action | Description | Permission |
|---|---|---|
| View | See row-level import details | `import-logs.details.view` |
| Delete Batch | Soft-delete all records from this import | `import.batch.delete` |
| Restore Batch | Restore soft-deleted records from this import | `import.batch.restore` |

> Delete Batch and Restore Batch are available for `account` and `member` import types only.

---

## Reference Data (Admin)

The following reference tables are managed by administrators:

| Module | Description |
|---|---|
| Account Types | Types of bank accounts for clinics |
| Accreditation Statuses | Clinic accreditation status options |
| Business Types | Business classification types |
| HIP | Health Insurance Providers |
| MBL Types | Maximum Benefit Limit types (Procedural / Fixed) |
| Services | Dental services (Basic, Enhancement, Special) |
| Tax Types | Withholding tax rate options |
| Unit Types | Service unit types (Tooth, Quadrant, Arch, etc.) |
| Units | Individual units per unit type |
| Roles & Permissions | User roles and permission assignments |
| Users | System user accounts |
| Activity Log | Audit trail of all system actions |
| System Version | Current system version tracking |

---

*Documentation generated for HPDAI v1.0 — Laravel 12 / Filament 3*
