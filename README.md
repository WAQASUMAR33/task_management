# ⚡ ApexTask Pro - Simple Task Management System with RBAC & File Attachments

A modern, robust, and responsive full-stack **Task Management System** featuring **3-Tier Role-Based Access Control (RBAC)**, multi-file attachments (max 10MB per file with preview and download), real-time filtering & search, and role-aware dashboards.

---

## 🌟 Key Features

### 1. 3-Tier Role-Based Access Control (RBAC)
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
  - Personal dashboard displaying **"My Tasks"**, tasks due today, and priority items.
  - Strictly isolated view: can only see tasks assigned to them or created by them.
  - Can quickly update task status: `To Do` ➔ `In Progress` ➔ `Completed`.
  - Can add notes/comments to task collaboration threads.
  - Can attach files (images, PDFs, documents, spreadsheets) up to 10MB per file.
  - Restricted from creating new tasks or deleting tasks/users.

---

### 2. Multi-File Attachments & Collaboration
- **Supported Formats:** Images (PNG, JPG, SVG, WebP), PDFs, Word Documents, Excel/CSV Spreadsheets, Plain Text, and ZIP Archives.
- **Validation:** Server-side and client-side 10MB per file validation and MIME-type verification.
- **File Previews & Downloads:** Embedded image thumbnails and dedicated file-type icons with direct download streams.
- **Task Comments:** Real-time conversation thread per task with role badges and timestamps.

---

### 3. Role-Aware Dashboard & Real-Time Filtering
- **Executive View (Super Admin / Admin):**
  - Metric KPI cards: Total Tasks, In Progress, Completed, Overdue, Tasks Due Today.
  - Overall completion rate percentage with progress bar.
  - Priority distribution metrics (High, Medium, Low).
  - Staff workload allocation matrix with task counts, completion velocity, and overdue alerts.
  - Live activity and audit trail log.
- **Staff View:**
  - Focused dashboard with assigned workloads, due dates, and quick status actions.
- **Filtering & Search:**
  - Real-time text search across title and description.
  - Filter by Status (`To Do`, `In Progress`, `Completed`).
  - Filter by Priority (`Low`, `Medium`, `High`).
  - Filter by Assignee (Staff dropdown).
  - Overdue toggle & Date range checks.
  - Card Grid View and Row Table View toggles.

---

## 🏗️ Technology Stack

| Layer | Technology |
|---|---|
| **Frontend** | React 19, Vite, Tailwind CSS v4, Lucide React Icons |
| **Backend** | Node.js, Express 5, CORS, Multer (File Handling) |
| **Database** | MySQL 5.7+ / 8.0+ on **WAMP Server** (`mysql2/promise` pool) |
| **Security** | JSON Web Tokens (JWT), Bcrypt password hashing (Cost 10) |

---

## 🗄️ WAMP MySQL Configuration

The backend is configured to connect directly to your local **WAMP Server**:
- **Host:** `127.0.0.1` (or `localhost`)
- **Port:** `3306`
- **Username:** `root`
- **Password:** *(empty)*
- **Database:** `task_management`

You can open **phpMyAdmin** at `http://localhost/phpmyadmin` to browse and manage all tables:
- `users`
- `tasks`
- `task_attachments`
- `task_comments`
- `activity_logs`
- `system_settings`

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

## 🚀 Setup & Execution Instructions

### Prerequisites
- Node.js (v18+ or v22+)
- npm (v9+)

---

### 1. Backend Setup
```bash
# Navigate to the backend directory
cd backend

# Install dependencies (Express, better-sqlite3, multer, bcryptjs, jsonwebtoken, etc.)
npm install

# Seed the SQLite database with default roles, users, tasks, and sample attachments
npm run seed

# Start the backend server (Runs on port 5000)
npm start
```
The backend API will run at `http://localhost:5000`.

---

### 2. Frontend Setup
In a new terminal window:
```bash
# Navigate to the frontend directory
cd frontend

# Install dependencies
npm install

# Start the Vite development server (Runs on port 3000 or 3001)
npm run dev
```
Open your browser at `http://localhost:3000` (or `http://localhost:3001` if port 3000 is occupied).

---

### 3. Running Automated Test Suite
To verify the RBAC rules and API integrity:
```bash
cd backend
node src/test_e2e.js
```
Expected output: **20 Passed, 0 Failed**.

---

## 📡 API Endpoints Reference

### Authentication (`/api/auth`)
- `POST /api/auth/login` - Authenticate user & issue JWT
- `GET /api/auth/me` - Retrieve current session user profile
- `PUT /api/auth/profile` - Update display name & avatar color
- `POST /api/auth/change-password` - Update password

### User Management (`/api/users`) - *Super Admin & Admin Only*
- `GET /api/users` - List users with assigned task statistics
- `POST /api/users` - Create user (Admin can only create Staff; Super Admin can create any)
- `PUT /api/users/:id` - Update user details
- `PATCH /api/users/:id/toggle-status` - Deactivate / Activate account
- `POST /api/users/:id/reset-password` - Reset user password
- `DELETE /api/users/:id` - Delete user account

### Task Management (`/api/tasks`)
- `GET /api/tasks` - List tasks with status/priority/assignee/search filters (role-isolated for staff)
- `GET /api/tasks/:id` - Task detail with attachments & comments
- `POST /api/tasks` - Create task with optional file uploads (*Admin/Super Admin only*)
- `PUT /api/tasks/:id` - Update task details (*Admin/Super Admin only*)
- `PATCH /api/tasks/:id/status` - Quick status update (`todo`, `in_progress`, `completed`)
- `PATCH /api/tasks/:id/assignee` - Reassign task (*Admin/Super Admin only*)
- `DELETE /api/tasks/:id` - Delete task (*Admin/Super Admin only*)

### File Attachments & Comments
- `POST /api/tasks/:id/attachments` - Upload files (up to 10 files, 10MB each)
- `DELETE /api/tasks/attachments/:attachmentId` - Delete attachment file
- `GET /api/tasks/attachments/:attachmentId/download` - Stream / download file
- `GET /api/tasks/:id/comments` - Fetch task comment timeline
- `POST /api/tasks/:id/comments` - Post comment / note

### Dashboard & Analytics (`/api/dashboard`)
- `GET /api/dashboard/stats` - Role-aware metrics, overdue counts, completion velocity, workload tables, and activity log.
