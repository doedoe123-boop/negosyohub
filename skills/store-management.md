# Store Management Skill

**Purpose:** Manage stores in the platform.

**Capabilities:**

- Approve or suspend a store.
- Assign `store_owner` to a user.
- Track store revenue and payout status.
- Configure store payout method (Bank Transfer, GCash, Maya).
- Restrict access: store owners only see their store data.

**Key Files:**

- Service: `app/Services/StoreService.php`
- Model: `app/Models/Store.php`
- Policy: `app/Policies/StorePolicy.php`
- Enum: `app/StoreStatus.php` (Pending, Approved, Suspended)
- Enum: `app/PayoutMethod.php` (BankTransfer, GCash, Maya)

**Phase 1 (Auth Foundation) - COMPLETED:**

- Role-based middleware `EnsureUserHasRole` gates store dashboard to `store_owner` role
- Store owner dashboard placeholder at `/store/dashboard`
- Lunar admin panel restricted to `admin` role via `canAccessPanel()`

**Phase 2 (Store Onboarding) - PLANNED:**

- Customer can register a store → becomes StoreOwner
- Store registration form (Livewire component)
- Admin approval flow in Lunar panel
- Store status lifecycle (Pending → Approved / Suspended)

**Phase 3 (Store Owner Dashboard) - PLANNED:**

- Product management (CRUD via Lunar)
- Order viewing and management
- Earnings and payout dashboard
- Payout configuration (see `skills/payout-configuration.md`)

**Notes:**

- Always filter products and orders by `store_id`.
- Use Policies for permissions.
- `StoreService::register()` creates store with Pending status and promotes user to StoreOwner.
