# 🧠 SYSTEM OVERVIEW

This is a multi-tenant SaaS ERP system "TechFlow" for software/IT companies.

- One system used by multiple companies
- Each company has isolated data
- Super Admin (developer) has full control
- Company Admin manages users & permissions

---

# ⚙️ TECH STACK

- Backend: PHP v8.1 (Laravel 10.* MVC)
- Database: MySQL
- Frontend: Bootstrap, jQuery, AJAX, Blades
- Version Control: GitHub

---

# 🏢 MULTI-TENANCY RULES

- Every table must include:
  - tenant_id

- Data must ALWAYS be filtered by tenant_id

---

# 🗄️ DATABASE CONVENTIONS

- Table fields:
  - *_id (PK)
  - *_status (1=active, 2=inactive, 3=deleted)
  - *_created_at
  - *_created_by
  - *_updated_at
  - *_updated_by
  - *_deleted_at (soft delete)
  - *_deleted_by

Example:
- tenant_id
- employee_id
- lead_id

---

# 🧩 MODULE STRUCTURE

Modules include:

- CRM/Leads
- HR
- Accounts/Finance
- Company (core settings)
- Chat
- Projects

---

# 🔐 PERMISSIONS SYSTEM

- Role-based access
- Company admin assigns permissions
- Module-wise access control

---

# 🎯 CODING RULES

- Follow MVC structure
- Avoid duplicate queries
- Use reusable functions
- Create migrations for everything linked with DB 

---

# 🤖 AI INSTRUCTIONS

When generating code:
- Follow Laravel standards
- Use MySQL-compatible queries
- Add comments
- don't run migrations from start to fresh the database, always run only those migrations that are newly added and not ran still

When generating UI:
- Use Blades
- Keep clean SaaS-style layout
- Top navbar layout (for admin portal)
- Sidebar nav layout (for employee portal)

---

# 🚀 CURRENT TASK

"Build HR module with employee management, payroll, leaves"