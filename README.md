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
Every submodule (Invoices, Payroll, Pipeline, etc.) currently links to `#` until
we build those screens — the nav, permissions and layouts are ready for them.
