# UperLevel — Laravel starter (Auth, roles, dynamic modules, tenant impersonation)

This package is an **overlay** on top of a fresh Laravel 10 installation — it contains
only the application code we've discussed (models, controllers, migrations, seeders,
views, routes, CSS). You generate the framework itself locally with Composer (this
sandbox has no internet access to Packagist, so the `vendor/` folder can't be shipped
pre-built) — that takes 2 minutes and everything below drops straight into it.

## What's included

- Single login screen (`/login`) for every role, with the UperLevel logo
- Role-based redirect after login: `superadmin`, `owner`, `admin`, `manager` (HOD), `employee`
- Automatic attendance check-in the moment a company user logs in
- Super Admin → **Tenants** list → **"Login as company"** button that opens that
  tenant's Admin Portal instantly, with no extra credentials (a scoped support
  session, not a fake user login) — with an "Exit" banner to leave it
- Three DRY layouts sharing one CSS file and one profile-dropdown/check-in partial:
  - **Super Admin** — sidebar, indigo theme
  - **Admin Portal** (owner / admin / manager) — two-tier header (main nav + a
    contextual submodule bar that updates when you click a module), teal theme
  - **Employee Portal** — sidebar, blue theme
- **Modules & submodules are fully database-driven** (`modules` table, self-referencing
  for submodules, a `roles` column controlling who sees what, and a `tenant_module`
  pivot controlling which modules each company has switched on)

## 1. Create the base Laravel app

```bash
composer create-project laravel/laravel techflow "10.*"
cd techflow
```

(If you use the Laravel installer instead: `laravel new techflow` then select
"no starter kit" — we already provide our own login screen.)

## 2. Copy this overlay into it

Copy every file from this package into the matching path inside `techflow/`,
overwriting where prompted:

```
app/Http/Controllers/**        → techflow/app/Http/Controllers/**
app/Http/Middleware/**         → techflow/app/Http/Middleware/**
app/Http/Kernel.php            → techflow/app/Http/Kernel.php   (overwrite)
app/Models/**                  → techflow/app/Models/**          (overwrite User.php)
database/migrations/**         → techflow/database/migrations/**
database/seeders/**            → techflow/database/seeders/**   (overwrite DatabaseSeeder.php)
resources/views/**              → techflow/resources/views/**    (delete the default
                                  welcome.blade.php first, ok to overwrite the rest)
routes/web.php                 → techflow/routes/web.php        (overwrite)
public/css/app.css             → techflow/public/css/app.css
```

No `npm install` / Vite build is required — the CSS is a single plain stylesheet
loaded directly, no Tailwind/build step in this MVP.

## 3. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and set:

```
APP_NAME=UperLevel
DB_CONNECTION=sqlite      # simplest for local testing — or use mysql/pgsql
```

If you use SQLite (fastest way to try this out):

```bash
touch database/database.sqlite
```

and remove/comment the `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`,
`DB_PASSWORD` lines (SQLite doesn't need them) — or just set
`DB_DATABASE=/absolute/path/to/techflow/database/database.sqlite`.

## 4. Migrate & seed demo data

```bash
php artisan migrate --seed
```

This creates 3 demo companies (Pixelworks Media, Northline Studios, Bytecraft Dev —
the last one on a trial plan with Finance & CRM switched off, to demonstrate the
per-tenant module toggle) and one user per role, plus all nav modules/submodules.

## 5. Run it

```bash
php artisan serve
```

Visit **http://127.0.0.1:8000**

### Demo accounts (password for all: `password`)

| Role | Email | Lands on |
|---|---|---|
| Super Admin | `superadmin@uperlevel.tech` | Tenants list → "Login as company" |
| Owner | `owner@pixelworks.co` | Admin Portal (full modules) |
| Admin | `admin@pixelworks.co` | Admin Portal (no Finance/CRM by role) |
| Manager / HOD | `manager@pixelworks.co` | Admin Portal (no Finance/CRM) |
| Employee | `employee@pixelworks.co` | Employee Portal, auto checked-in |

Try logging in as Super Admin, opening **Tenants**, and clicking **"Login as company"**
next to Bytecraft Dev — you'll land in its Admin Portal and notice Finance/CRM are
missing from the nav because that tenant has them disabled, all pulled live from
the `tenant_module` table.

## How the DRY / dynamic-modules architecture works

- `SetTenantContext` middleware (runs on every request) figures out the current
  tenant (including superadmin impersonation) and the nav `$modules` for the
  logged-in role, then shares both with **every view** via `View::share()` — no
  controller has to pass them manually.
- `<x-module-nav :modules="$modules" type="sidebar|header" />` is one Blade
  component that renders either a sidebar tree or the two-tier header nav,
  reused across Super Admin, Admin Portal and Employee Portal — same data,
  different markup branch.
- `partials/profile-dropdown.blade.php` and `partials/checkin-banner.blade.php`
  are shared between the Admin and Employee layouts.
- Add a new module/submodule by inserting a row in the `modules` table (see
  `ModuleSeeder` for the pattern) — no blade or route changes needed for it to
  show up in the nav (link it to a real route later via the `route_name` column).

## What's deliberately not built yet (next screens)

Only Dashboards + Auth + Tenants list + dynamic nav are wired to real screens.
Every submodule (Invoices, Payroll, Pipeline, etc.) currently links to `#` until
we build those screens — the nav, permissions and layouts are ready for them.
