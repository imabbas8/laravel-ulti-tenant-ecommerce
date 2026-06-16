<p align="center">
  <a href="http://www.bagisto.com">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/bagisto/temp-media/0b0984778fae92633f57e625c5494ead1fe320c3/dark-logo-P5H7MBtx.svg">
      <source media="(prefers-color-scheme: light)" srcset="https://bagisto.com/wp-content/themes/bagisto/images/logo.png">
      <img src="https://bagisto.com/wp-content/themes/bagisto/images/logo.png" alt="Bagisto logo" width="220">
    </picture>
  </a>
</p>

<h1 align="center">Bagisto E-Commerce Store — Project</h1>

<p align="center">
  A ready-to-demo online store built on <b>Bagisto v2.3.19</b> (Laravel 11 e-commerce framework).<br>
  Pre-configured, seeded with sample data, and running on MySQL — just start it and go.
</p>

---

## 🚀 Quick Start (run in 1 step)

> **Double-click [`start.bat`](start.bat)** — it starts MySQL + the PHP server, then opens two windows and prints the URLs/login.

Then open:

| What         | URL                                  |
|--------------|--------------------------------------|
| 🛒 Storefront | http://localhost:8000                |
| 🔑 Admin Panel | http://localhost:8000/admin/login   |

**Admin login**
- Email: `admin@example.com`
- Password: `admin123`

> Demo data already loaded: **35 products** (with images), **9 categories**, **15 customers**, and store branding. The home page shows full Featured / New Products carousels.

📖 **Full walkthrough (customer + admin flow, architecture):** [SETUP_GUIDE.md](SETUP_GUIDE.md)

---

## 🧰 Tech Stack

| Layer       | Technology                                  |
|-------------|---------------------------------------------|
| Framework   | Laravel 11.48                               |
| E-commerce  | Bagisto 2.3.19                              |
| Language    | PHP 8.2                                     |
| Database    | **MySQL 8.0** (Laragon build)               |
| Frontend    | Blade + Vite (prebuilt assets, Tailwind)    |
| Admin UI    | Bagisto Admin (Vue islands + Blade)         |

> **Why MySQL and not SQLite?** Bagisto is **not SQLite-compatible at runtime** — its
> admin data grids, dashboard reporting, and product indexers use MySQL-only SQL
> (`CONCAT`, `GROUP_CONCAT`, `DATE_FORMAT`). On SQLite the install fails on the
> `add_logo_path_column_to_locales` migration. **MySQL is the correct, stable choice.**

---

## ✅ Prerequisites

Already present on this machine — listed here in case you set up a fresh PC:

- **PHP 8.2** with extensions: `pdo_mysql`, `intl`, `gd`, `mbstring`, `openssl`, `curl`
- **Composer 2.x** (dependencies already installed in `/vendor`)
- **MySQL 8.0** (bundled with **Laragon** at `C:\laragon\bin\mysql\mysql-8.0.40-winx64`)

The `bagisto` database is already created and fully seeded — **no fresh install needed**.

---

## ▶️ Running Manually (instead of `start.bat`)

Open **two terminals**:

```bash
# Terminal 1 — start MySQL (or just open Laragon → "Start All")
"C:\laragon\bin\mysql\mysql-8.0.40-winx64\bin\mysqld.exe" --datadir=C:/laragon/data/mysql-8 --port=3306 --console
```

```bash
# Terminal 2 — start Bagisto
cd "D:\github project\bagisto"
php artisan serve --host=127.0.0.1 --port=8000
```

> ⚠️ MySQL **must be running first**, otherwise the server throws `Can't connect to MySQL (10061)`.

---

## ⚙️ Configuration & Keys (`.env`)

The project ships with a working [`.env`](.env). The values that matter:

```env
APP_NAME=Bagisto
APP_URL=http://localhost:8000
APP_ADMIN_URL=admin            # admin panel lives at /admin
APP_KEY=base64:...             # already generated (see below if missing)

# Database — Laragon MySQL, default root / no password
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bagisto
DB_USERNAME=root
DB_PASSWORD=                   # Laragon default = empty

# Mail — written to storage/logs, no SMTP needed for the demo
MAIL_MAILER=log
```

### Setting up `.env` on a fresh machine

```bash
# 1. Copy the template
copy .env.example .env

# 2. Generate the app encryption key (fills APP_KEY)
php artisan key:generate

# 3. Set the DB values above, then install Bagisto (creates tables + seeds data)
php artisan bagisto:install
```

### Optional keys (only if you enable these features)

| Feature                  | Keys to fill in `.env`                                              |
|--------------------------|--------------------------------------------------------------------|
| Real email (SMTP)        | `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` |
| Amazon S3 file storage   | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_DEFAULT_REGION` |
| Redis cache/queue        | `CACHE_STORE=redis`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`   |
| Payment gateways         | Configure in **Admin → Settings → Configuration → Payment Methods** (PayPal etc.) |

> 💳 The default payment method is **Cash on Delivery**, which works **without any gateway keys** — so checkout is fully testable out of the box.

> 🔐 The `APP_KEY` and credentials in this repo are for **local demo only**. Generate fresh ones and never commit real secrets for production.

---

## 🧪 Useful Commands

```bash
# Re-run product indexing (after adding/editing products)
php artisan indexer:index --mode=full

# Clear all caches (config, route, view)
php artisan optimize:clear

# Clear only .env config cache (after editing .env)
php artisan config:clear

# Reinstall from scratch — DROPS all data and re-seeds
php artisan bagisto:install

# Generate extra fake products for testing
php artisan bagisto:fake
```

---

## 🩺 Troubleshooting

| Problem | Fix |
|---------|-----|
| `Can't connect to MySQL (10061)` | MySQL isn't running — run `start.bat` or Laragon → Start All. |
| Storefront shows no products | Run `php artisan indexer:index --mode=full`. |
| Styles look broken | Assets are prebuilt in `public/themes/...`; run `php artisan optimize:clear`. |
| Changed `.env` not taking effect | Run `php artisan config:clear`. |
| Port 8000 busy | `php artisan serve --port=8001` and update `APP_URL` in `.env`. |

---

## 🗂️ Project Structure (where things live)

Bagisto is modular — each feature is a package under `packages/Webkul/`:

| Package     | Responsibility                                |
|-------------|-----------------------------------------------|
| `Shop`      | Storefront (landing, product page, cart)      |
| `Admin`     | Admin panel, data grids, dashboard reporting  |
| `Product`   | Product models, repositories, **indexers**    |
| `Checkout`  | Cart & checkout logic                         |
| `Sales`     | Orders, invoices, shipments, refunds          |
| `Customer`  | Customer accounts, addresses, wishlist        |
| `Core`      | Channels, currencies, locales, config         |
| `Installer` | `php artisan bagisto:install` + seeders       |

- Storefront views: `packages/Webkul/Shop/src/Resources/views/`
- Admin views: `packages/Webkul/Admin/src/Resources/views/`
- Compiled assets: `public/themes/shop/default/build` and `public/themes/admin/default/build`

---

## 📚 About Bagisto

This project is built on **[Bagisto](https://bagisto.com/)**, an open-source Laravel +
Vue.js e-commerce framework (MIT licensed). For official docs and features:

➡️ [Website](https://bagisto.com/en/) · [Documentation](https://devdocs.bagisto.com/) · [Live Demo](https://demo.bagisto.com/) · [Forums](https://forums.bagisto.com/)
