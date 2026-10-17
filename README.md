# ⚡ ApexTask Pro - Enterprise Laravel Platform with Material UI

A pure **Laravel 12 (PHP 8.2+)** full-stack **Task Management System** built with **Material UI (Material Design 3)** components, 3-tier Role-Based Access Control (RBAC), multi-file attachments (up to 10MB), collaboration comments, and executive analytics.

> 💡 **Pure Laravel Platform:** Completely removes any Node.js/Next.js/React runtime server dependency. Deploys natively to **Hostinger**, Apache, Nginx, or local WAMP/XAMPP environments with zero-build pure PHP and CSS.

---

## 🌟 Key Features

### 1. Material UI (Material Design 3) Design System Across All Pages
- **Top App Bar:** Elevation shadows, active page breadcrumb, role badge, user profile chip, notifications icon, and mobile drawer hamburger toggle.
- **Navigation Drawer:** Sleek M3 drawer with rounded pill indicators, section grouping, and footer user card.
- **Material Cards (`md-card`):** Elevation levels (dp1 - dp4), surface containers, rounded-2xl geometry.
- **Floating Action Button (FAB):** Quick-action FAB for instant task creation.
- **Form Controls:** Outlined text fields, elevated selects, and multi-file upload zones.
- **Chips & Badges:** Status chips (`To Do`, `In Progress`, `Completed`), priority chips (`High`, `Medium`, `Low`), and role chips.
- **Material Dialogs & Modals:** Smooth animated dialogs for creating/editing tasks, managing users, and resetting passwords.
- **Live Snackbars:** Material toast notifications for instant feedback.

---

### 2. 3-Tier Role-Based Access Control (RBAC)
- 👑 **Super Admin (`super_admin`):**
  - Full system oversight & control.
  - Can manage all tasks, re-assign workloads, and delete tasks.
  - Can manage all users (create/edit/deactivate/delete Super Admins, Admins, and Staff).
  - Can configure global system settings (company name, storage quotas, file permissions).
- 🛡️ **Admin (`admin`):**
  - Can manage all tasks across all departments and re-assign tasks to staff.
  - Can create and manage **Staff members** (Name, Email, Department, Status, Password Resets).
  - Restricted from creating or altering Admin/Super Admin accounts.
  - Restricted from global system settings.
- 👤 **Staff (`staff`):**
  - Personal workstation dashboard displaying assigned tasks, tasks due today, and priority items.
  - Strictly isolated view: can only see tasks assigned to them or created by them.
  - Can quickly update task status: `To Do` ➔ `In Progress` ➔ `Completed`.
  - Can add notes/comments to task collaboration threads.
  - Can attach files (images, PDFs, documents, spreadsheets) up to 10MB per file.
  - Restricted from creating new tasks or accessing user/settings administration.

---

## 🔑 Demo Accounts (Pre-Seeded)

All demo accounts share the password: `Password123!`

| Role | Name | Email | Default Department |
|---|---|---|---|
| 👑 **Super Admin** | Alexander Vance | `superadmin@apextask.com` | Executive Leadership |
| 🛡️ **Admin** | Sarah Jenkins | `admin@apextask.com` | Product Operations |
| 👤 **Staff (Frontend)** | John Doe | `john.doe@apextask.com` | Frontend Engineering |
| 👤 **Staff (UI/UX)** | Emily Chen | `emily.chen@apextask.com` | UI/UX Design |
| 👤 **Staff (DevOps)** | Marcus Wright | `marcus.wright@apextask.com` | Cloud & DevOps |
| 👤 **Staff (QA)** | Priya Patel | `priya.patel@apextask.com` | Quality Assurance |

> 💡 **Quick Login:** The login screen provides **1-Click Quick Demo Persona buttons** to instantly authenticate as Super Admin, Admin, or Staff without typing credentials!

---

## 💻 Local Development

### 1. Requirements
- PHP 8.2+
- Composer 2.x
- SQLite or MySQL (WAMP/XAMPP)

### 2. Run the Application Locally
```bash
# Start local PHP development server
php artisan serve
```
Open your browser at `http://localhost:8000`.

---

## 🚀 Hostinger Deployment Guide

### Method: Standard Hostinger `public_html` Deployment
1. **Compress & Upload:**
   - Compress the Laravel project files into a ZIP archive (excluding `node_modules` and `_backup_node_react/`).
   - In Hostinger hPanel -> **File Manager**, upload and extract the ZIP directly into `public_html/`.
2. **Configure Database:**
   - In Hostinger hPanel -> **Databases**, create your MySQL database and user.
   - Edit `.env` in `public_html/` with your Hostinger database details:
     ```env
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=u889453186_task_mngmnt
     DB_USERNAME=u889453186_task_mngmnt
     DB_PASSWORD=YourPasswordHere
     ```
3. **Run Migrations & Seeding:**
   - If SSH is available on Hostinger:
     ```bash
     php artisan migrate --seed
     php artisan storage:link
     ```
   - Or export your local database tables to Hostinger via **phpMyAdmin**.
4. **Root Routing (`.htaccess`):**
   - The included root [`.htaccess`](file:///d:/task_management/.htaccess) automatically routes incoming web requests directly to the `public/` directory while securing your `.env` and application files.
