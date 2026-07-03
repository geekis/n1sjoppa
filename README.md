# N1 "Sjoppan" — kiosk price calculator & sales tracking

Fast, mobile-first kiosk for shop staff to tally a customer's purchase and see a
running total. **Not a payment system** — it only calculates the amount to charge
and records what was sold, for reporting on sales per item and per staff member.

Currency is Icelandic króna (ISK), formatted as whole numbers with a dot
thousands separator and `kr.` suffix (e.g. `1.250 kr.`).

## Stack

Laravel 13 · Livewire 4 · Flux UI 2 (Pro) · Tailwind CSS v4 · MySQL · Vite.

## Setup

1. **Install dependencies**

   ```bash
   composer install
   npm install
   ```

2. **Environment / database.** Copy `.env.example` to `.env` if needed, set the
   app key, and point at MySQL:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_n1sjoppa
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   ```bash
   php artisan key:generate      # only if APP_KEY is empty
   mysql -u root -h 127.0.0.1 -e "CREATE DATABASE db_n1sjoppa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

3. **Migrate + seed** (staff, categories, ~29 products with realistic ISK prices,
   and an admin login):

   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Build assets**

   ```bash
   npm run build      # or: npm run dev  (for local development)
   ```

The app is served by Laravel Herd at **http://n1sjoppa.test**.

## Flux Pro

This build uses Flux **Pro** components (tables, modals, forms, badges). The
license credentials live in `auth.json`. If you need to (re)activate on a new
machine:

```bash
php artisan flux:activate     # or set credentials in auth.json / FLUX_LICENSE_KEY
composer install
```

If Pro is ever unavailable, swap Pro components for free Flux components + plain
Tailwind — the kiosk itself already uses plain Tailwind for its custom controls.

## URLs

| Area            | URL                                | Auth            |
|-----------------|------------------------------------|-----------------|
| **Kiosk (QR)**  | `http://n1sjoppa.test/kiosk`       | none            |
| Staff select    | `http://n1sjoppa.test/kiosk/staff` | none            |
| Admin / reports | `http://n1sjoppa.test/admin`       | admin login     |

**QR code → put `http://n1sjoppa.test/kiosk` in it.** First visit sends staff to
the name-select screen; picking a name starts a kiosk session (no device login).

### Admin login (seeded)

- **Email:** `admin@n1.is`
- **Password:** `password`

Change these before any real use.

## How it works

- **Kiosk flow:** scan QR → pick your name → tap featured items or browse
  category tabs → items land in the sticky cart with live ISK total → **Ljúka /
  Finish** records the sale and resets for the next customer (staff stays logged
  in). Tap the name chip in the header to switch staff.
- **Price snapshots:** when an item is added to a sale, its name and price are
  copied onto the sale line. Later price/name changes never alter historical
  sales.
- **Admin:** date-ranged reports (sales by item, by staff, overall totals; today
  / week / month presets) plus CRUD for products, categories, and staff. Staff
  are **deactivated, not deleted**, to preserve sales history.

## Theming

Brand tokens live in `resources/css/app.css` under `@theme`:

```css
--color-n1-red: #e30613;      /* primary brand red — CONFIRM */
--color-n1-red-dark: #b8040f; /* pressed/hover red — CONFIRM */
--color-n1-dark: #16181d;     /* header / near-black — CONFIRM */
```

These are **placeholders derived visually from the N1 logo** — confirm the exact
hex against the brand guide. The N1 logo is stored locally at
`public/images/n1-logo.svg`.

## Tests

```bash
php artisan test --compact
```

Covers ISK formatting, model relationships + price snapshots, the full kiosk
flow (staff session, cart, finish, price-snapshot integrity), admin CRUD, and
report aggregation.
