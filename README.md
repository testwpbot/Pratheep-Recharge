# Happy Pratheep Recharge — Laravel Edition

A Laravel 11 port of the Happy Pratheep Recharge static landing page.
The landing page's HTML, CSS, animations, imagery and copy are preserved exactly — they now live inside a real Laravel application so you can add recharge flows, auth, admin, payments, etc. on top.

## Requirements

- PHP 8.2+ with extensions: `mbstring`, `xml`, `curl`, `zip`, `bcmath` (typical Laravel stack)
- [Composer](https://getcomposer.org/)
- Node.js 18+ & npm (only needed if you want to use Vite for future JS/CSS builds)

## Quick start

```bash
# 1. Install PHP dependencies
composer install

# 2. Create your .env (copy of .env.example is already provided)
cp .env.example .env

# 3. Generate the app key
php artisan key:generate

# 4. (Optional) If you want a database, edit .env and run:
# php artisan migrate

# 5. Link storage for uploads (recharge slips, etc.)
php artisan storage:link

# 6. Serve
php artisan serve
```

Then visit http://localhost:8000 — your landing page will render exactly as it did in the static `index.html`.

## Project layout (key paths)

| Path | Purpose |
|------|---------|
| `public/index.php` | Laravel front controller |
| `public/assets/` | All original images, fonts, logos, hero art (unchanged) |
| `public/css/landing.css` | The entire original CSS (extracted verbatim from `index.html`) |
| `public/js/landing.js` | All original inline JS (clock, sticky nav, mobile drawer) |
| `resources/views/layouts/app.blade.php` | Master layout (`<head>`, topbar, nav, footer) |
| `resources/views/partials/` | `topbar`, `nav` (desktop + mobile drawer), `footer` |
| `resources/views/pages/home.blade.php` | The landing page — **every component & style from the original** |
| `resources/views/pages/placeholder.blade.php` | Temporary Coming-soon pages for routes wired to future features |
| `resources/views/components/icon.blade.php` | Reusable Blade component wrapping all inline SVGs |
| `app/Http/Controllers/PageController.php` | Serves the landing page; passes providers/services/step data |
| `routes/web.php` | All web routes (landing, placeholder pages, auth) |
| `database/schema.mysql.sql` | Ready-to-run MySQL SQL script for localhost |
| `reference/original-index.html` | The original static file kept for reference / diffing (not used at runtime) |

## Routes already wired

| Method | URI | Purpose |
|--------|-----|---------|
| GET | `/` | Landing page (100% original look & feel) |
| GET | `/mobile-reload`, `/postpaid`, `/data-packages` | Future recharge sections (placeholder) |
| GET | `/broadband`, `/electricity`, `/water`, `/tv`, `/gift-cards` | Future bill-payment sections (placeholder) |
| GET | `/support`, `/sign-in` | Support / Sign-in (placeholder) |
| GET | `/privacy`, `/terms`, `/refund` | Legal pages (placeholder) |
| GET/POST | `/login` | Sign in (guest only) |
| GET/POST | `/register` | Create account (guest only) |
| POST | `/logout` | Sign out |
| GET | `/up` | Laravel 11 health check |

## Setting up MySQL on localhost

Two options:

**Option A — run the SQL I prepared**
Open MySQL Workbench / phpMyAdmin / your terminal and import:
```
database/schema.mysql.sql
```
Or from the command line:
```bash
mysql -u root -p < database/schema.mysql.sql
```
That creates the database `pratheep_recharge` plus `users`, `password_reset_tokens`, and `sessions` tables.

Then edit your `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pratheep_recharge
DB_USERNAME=root
DB_PASSWORD=your_password
```
and run:
```bash
php artisan migrate   # confirms everything matches
```

**Option B — let Laravel create everything**
```sql
CREATE DATABASE pratheep_recharge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
Then set DB credentials in `.env` and run:
```bash
php artisan migrate
```

The registration form requires **name, email, phone (required), password + confirmation**. Phone numbers are normalized (e.g. `0771234567` → `+94771234567`). After signing up, users are redirected to `/login` with a success flash. After logging in they land back on `/` (homepage) and the nav shows their avatar + email + a Logout button.

## Building out features

Everything is plain Blade + the existing CSS custom properties, so you can:

- Add new Blade views under `resources/views/pages/` and point routes at controllers.
- Use `<x-icon name="..." />` to re-use the existing SVG icon set (see the names inside `resources/views/components/icon.blade.php`).
- Add form requests, auth scaffolding (`php artisan make:filament`, Breeze/Jetstream), models for orders/customers/transactions, admin panels, etc. without touching the landing page markup.
- Drop new CSS into `public/css/landing.css` or use Vite (`npm install && npm run dev`) for future assets.

## Original static file

The original `index.html` is kept at the project root for reference / diffing. It isn't used by Laravel — if you want to compare the output just run `php artisan serve` and open `/`.
