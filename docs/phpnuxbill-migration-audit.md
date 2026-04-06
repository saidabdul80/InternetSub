# PHPNuxBill Migration Audit

This document maps the legacy `phpnuxbill` feature set to the Laravel/Inertia app in `app/`.

## Legacy Scope

Admin-facing controllers in `phpnuxbill/system/controllers`:

- `dashboard.php`
- `customers.php`
- `plan.php`
- `voucher.php`
- `routers.php`
- `bandwidth.php`
- `reports.php`
- `settings.php`
- `message.php`
- `maps.php`
- `pages.php`
- `paymentgateway.php`
- `coupons.php`
- `pool.php`
- `services.php`
- `accounts.php`
- `logs.php`
- `mail.php`
- `plugin.php`
- `pluginmanager.php`
- `customfield.php`
- `search_user.php`
- `odp.php`

Member-facing controllers and templates:

- `home.php`
- `order.php`
- `login.php`
- `register.php`
- `forgot.php`
- `page.php`
- `customer/dashboard.tpl`
- `customer/profile.tpl`
- `customer/change-password.tpl`
- `customer/orderPlan.tpl`
- `customer/orderHistory.tpl`
- `customer/orderView.tpl`
- `customer/orderBalance.tpl`
- `customer/selectGateway.tpl`
- `customer/invoice-customer.tpl`
- `customer/inbox.tpl`
- `customer/activation*.tpl`
- `customer/custom_field.tpl`
- `customer/phone-update.tpl`
- `customer/email-update.tpl`

Excluded from this migration request:

- FreeRADIUS tables and flows
- `radius.php`
- `radius.sql`
- Radius NAS/admin screens

## Current App Scope

Current Laravel app features:

- Admin auth
- Dashboard summary
- Voucher inventory
- Manual purchase / direct activation
- Payment gateway checkout for hotspot subscription
- MikroTik user provisioning

Current Laravel data model:

- `users`
- `plans`
- `payments`
- `vouchers`
- `mikrotik_users`

## Missing Core Domains

The following domains exist in PHPNuxBill but not yet in the Laravel app:

- Customers
- Customer custom fields
- Routers
- Bandwidth profiles
- Recharges / active packages
- Transactions / invoices
- App settings
- Coupons
- Customer inbox/messages

## Migration Phases

1. Core domain model
2. Admin CRUD and operational flows
3. Member account and ordering flows
4. Reports, exports, maps, messaging, pages
5. Visual redesign and workflow hardening

## Recommended Module Order

Admin:

1. Customers
2. Plans
3. Routers
4. Recharges
5. Transactions
6. Settings
7. Reports
8. Messaging
9. Coupons
10. Maps / content pages

Member:

1. Login / registration alignment
2. Dashboard
3. Profile
4. Current packages / activations
5. Order plan
6. Gateway selection and checkout
7. Order history / invoices
8. Balance top-up and transfer
9. Inbox / notifications

## Design Direction

The replacement UI should not mimic PHPNuxBill's table-heavy Bootstrap layout.

Principles:

- Clear module landing pages
- Strong information hierarchy
- Mobile-friendly forms and data views
- Consistent status badges and action patterns
- Data-dense admin screens without looking dated
- A member panel that feels like a modern ISP self-service app
