# Bagisto E-Commerce — Project Setup & Flow Guide

A ready-to-demo online store built on **Bagisto v2.3.19** (Laravel 11 e-commerce framework).
This document explains how to run the project, the admin credentials, and the complete
customer + admin flow so a client can review everything end-to-end.

---

## 1. Tech Stack

| Layer        | Technology                                   |
|--------------|----------------------------------------------|
| Framework    | Laravel 11.48                                 |
| E-commerce   | Bagisto 2.3.19                                |
| Language     | PHP 8.2                                        |
| Database     | **MySQL 8.0** (Laragon build)                  |
| Frontend     | Blade + Vite (pre-compiled assets, Tailwind)  |
| Admin UI     | Bagisto Admin (Vue islands + Blade)           |

### Why MySQL and not SQLite?
SQLite was tested first, but Bagisto is **not SQLite-compatible at runtime**. Its admin
data grids, dashboard reporting, and product price/inventory indexers use MySQL-only SQL
functions (`CONCAT`, `GROUP_CONCAT`, `DATE_FORMAT`). On SQLite the install fails on the
`add_logo_path_column_to_locales` migration (`no such function: CONCAT`), and even after
patching, the product listing and admin panel throw SQL errors. **MySQL is the correct,
stable choice** — that is what this project uses.

---

## 2. Prerequisites (already present on this machine)

- PHP 8.2 (with `pdo_mysql`, `intl`, `gd`, `mbstring`, `openssl`, `curl`)
- Composer 2.x (dependencies already installed in `/vendor`)
- MySQL 8.0 (bundled with **Laragon** at `C:\laragon\bin\mysql\mysql-8.0.40-winx64`)

The database `bagisto` is already created and fully seeded — no fresh install needed.

---

## 3. How to Run (every time)

### Easiest way — double-click `start.bat`
It starts MySQL + the PHP server and prints the URLs and login.

### Manual way (two terminals)
**Terminal 1 — start MySQL:**
```bash
"C:\laragon\bin\mysql\mysql-8.0.40-winx64\bin\mysqld.exe" --datadir=C:/laragon/data/mysql-8 --port=3306 --console
```
> Or just open **Laragon** and click **Start All**.

**Terminal 2 — start Bagisto:**
```bash
cd "D:\github project\bagisto"
php artisan serve --host=127.0.0.1 --port=8000
```

---

## 4. URLs & Credentials

| What         | URL                                  |
|--------------|--------------------------------------|
| Storefront   | http://localhost:8000                |
| Admin Panel  | http://localhost:8000/admin/login    |

**Admin login**
- Email: `admin@example.com`
- Password: `admin123`

> Demo data loaded: **35 products** (all with images), **9 categories** (with images),
> **15 customers**, and store branding (**ShopHub** logo). Storefront shows full
> "Featured" / "New Products" carousels and populated category pages.

---

## 5. Customer Flow (Storefront)

1. **Landing / Home page** (`/`) — hero slider, Featured Products, New Products carousels.
2. **Browse** — top navigation categories → category listing with **filters** (price,
   color, size) and sorting.
3. **Product page** (`/{product-url-key}`) — gallery, price, variants, quantity,
   **Add to Cart**, **Add to Wishlist**, **Compare**, reviews.
4. **Cart** (`/checkout/cart`) — update quantities, apply coupon, see totals.
5. **Checkout** (`/checkout/onepage`) — address → shipping method → payment method
   → place order. (Default payment "Cash on Delivery" works without gateway keys.)
6. **Customer account** (`/customer/register`, `/customer/login`) — orders, addresses,
   wishlist, reviews, downloadable products.

## 6. Admin Flow (Backend — `/admin`)

1. **Dashboard** — sales, orders, customers, top-selling products (charts/reporting).
2. **Catalog → Products** — create/edit products (simple, configurable, virtual,
   downloadable, bundle, grouped, booking). Manage images, inventory, pricing, SEO.
3. **Catalog → Categories / Attributes / Families** — store structure.
4. **Sales → Orders / Invoices / Shipments / Refunds** — order lifecycle management.
5. **Customers** — customers, groups, reviews.
6. **Marketing** — promotions (cart rules / catalog rules), email templates, campaigns,
   newsletter, search SEO.
7. **CMS** — content pages (About, Privacy, etc.).
8. **Settings** — channels, currencies, locales, taxes, inventory sources, users/roles,
   themes, and **store configuration** (payment, shipping, email, etc.).

---

## 7. Project Architecture (where things live)

Bagisto is modular — each feature is a package under `packages/Webkul/`:

| Package        | Responsibility                                  |
|----------------|-------------------------------------------------|
| `Shop`         | Storefront (landing page, product page, cart)   |
| `Admin`        | Admin panel, data grids, dashboard reporting    |
| `Product`      | Product models, repositories, **indexers**      |
| `Category`     | Categories / navigation                          |
| `Checkout`     | Cart & checkout logic                            |
| `Sales`        | Orders, invoices, shipments, refunds            |
| `Customer`     | Customer accounts, addresses, wishlist          |
| `CMS`          | Content pages                                    |
| `Marketing`    | Promotions, campaigns, search synonyms          |
| `Core`         | Channels, currencies, locales, config           |
| `Installer`    | `php artisan bagisto:install` command + seeders |

Storefront views: `packages/Webkul/Shop/src/Resources/views/`
Admin views:      `packages/Webkul/Admin/src/Resources/views/`
Compiled assets:  `public/themes/shop/default/build` and `public/themes/admin/default/build`

---

## 8. Useful Commands

```bash
# Re-run product indexing (after adding/editing products)
php artisan indexer:index --mode=full

# Clear all caches
php artisan optimize:clear

# Reinstall from scratch (DROPS all data, re-seeds)
php artisan bagisto:install

# Generate extra fake products for testing
php artisan bagisto:fake

# Re-load the demo data used in this project (categories, products, customers,
# images, branding). Safe to re-run.
php artisan db:seed --class="Database\Seeders\DemoDataSeeder"
php artisan db:seed --class="Database\Seeders\DemoPolishSeeder"
php artisan indexer:index --mode=full
```

---

## 9. Configuration Reference (`.env`)

```
APP_URL=http://localhost:8000
APP_ADMIN_URL=admin            # admin panel lives at /admin

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bagisto
DB_USERNAME=root
DB_PASSWORD=                   # Laragon default = empty

MAIL_MAILER=log                # emails are written to storage/logs (no SMTP needed for demo)
```

---

## 10. Troubleshooting

| Problem | Fix |
|---------|-----|
| `Can't connect to MySQL (10061)` | MySQL isn't running — run `start.bat` or Laragon → Start All. |
| Storefront shows no products | Run `php artisan indexer:index --mode=full`. |
| Styles look broken | Assets are prebuilt in `public/themes/...`; run `php artisan optimize:clear`. |
| Changed `.env` not taking effect | `php artisan config:clear`. |
| Port 8000 busy | `php artisan serve --port=8001` and update `APP_URL`. |

---

### Summary for the client
- Open **`start.bat`** → wait a few seconds.
- Storefront: **http://localhost:8000**
- Admin: **http://localhost:8000/admin/login** (`admin@example.com` / `admin123`)
- Everything (landing page, product pages, cart/checkout, full admin backend) is set up
  and working on MySQL with sample data.
