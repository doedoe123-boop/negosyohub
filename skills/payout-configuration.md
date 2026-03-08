# Payout Configuration Skill

**Purpose:** Manage how store owners receive their earnings from the platform.

## Architecture

Payments are fully centralized — the platform collects all customer payments via PayMongo, calculates commissions per order (`CommissionService`), and disburses earnings to store owners via the configured payout method.

## Payout Methods

Defined in `app/PayoutMethod.php` enum:

| Case | Value | Description |
|------|-------|-------------|
| BankTransfer | `bank_transfer` | Philippine bank account (BDO, BPI, Metrobank, etc.) |
| GCash | `gcash` | GCash e-wallet |
| Maya | `maya` | Maya (formerly PayMaya) e-wallet |

## Store Payout Fields

Two columns on the `stores` table:

- **`payout_method`** (`varchar`, nullable) — Stores the `PayoutMethod` enum value.
- **`payout_details`** (`text`, nullable, encrypted) — JSON object with account details. Encrypted at rest via Laravel's `encrypted:array` cast.

### Payout Details Schema

**Bank Transfer:**
```json
{
  "account_name": "Juan Dela Cruz",
  "account_number": "1234567890",
  "bank_name": "BDO"
}
```

**GCash / Maya:**
```json
{
  "account_name": "Juan Dela Cruz",
  "mobile_number": "09171234567"
}
```

## Key Files

| File | Purpose |
|------|---------|
| `app/PayoutMethod.php` | Enum with `BankTransfer`, `GCash`, `Maya` cases and `label()` method |
| `app/Models/Store.php` | `payout_method` (cast to `PayoutMethod`), `payout_details` (cast to `encrypted:array`) |
| `app/Models/Payout.php` | Payout records with status lifecycle (pending → processing → paid/failed) |
| `app/Filament/Pages/SetupWizard.php` | Step 4 "Payout Info" — store owners configure during initial setup |
| `app/Filament/Pages/StoreProfile.php` | "Payout Information" section — store owners update anytime |
| `app/Filament/Admin/Resources/PayoutResource.php` | Admin view — "Recipient Details" section shows store's payout config |
| `database/factories/StoreFactory.php` | `withPayout()` factory state for testing |

## Store Owner Flow

1. During **Setup Wizard** (Step 4), store owner selects payout method and enters account details.
2. On **Store Profile** page, store owner can update payout info anytime.
3. Form fields are conditional based on selected method:
   - Bank Transfer → Bank Name, Account Name, Account Number
   - GCash/Maya → Account Name, Mobile Number

## Admin Flow

1. When viewing a **Payout** record, the admin sees a "Recipient Details" section showing the store's configured payout method and account details.
2. Admin uses the "Mark as Paid" action after transferring funds externally.

## Security

- `payout_details` is stored encrypted in the database (`encrypted:array` cast).
- Only platform administrators can view payout details via the PayoutResource infolist.
- Store owners can only view/edit their own payout configuration.

## Testing

Use the `withPayout()` factory state:

```php
// GCash (default)
Store::factory()->withPayout()->create();

// Bank transfer
Store::factory()->withPayout(PayoutMethod::BankTransfer)->create();

// Maya
Store::factory()->withPayout(PayoutMethod::Maya)->create();
```
