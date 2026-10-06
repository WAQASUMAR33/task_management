const path = require('path');
const fs = require('fs');
const db = require('./config/db');
const { hashPassword } = require('./utils/auth');

async function seedDatabase() {
  console.log('🌱 Seeding WAMP MySQL database: task_management...');

  const uploadDir = path.join(__dirname, '..', 'uploads');
  if (!fs.existsSync(uploadDir)) {
    fs.mkdirSync(uploadDir, { recursive: true });
  }

  // Ensure tables exist
  await db.initDatabase();

  // Clear existing records
  await db.query('SET FOREIGN_KEY_CHECKS = 0');
  await db.query('TRUNCATE TABLE activity_logs');
  await db.query('TRUNCATE TABLE task_comments');
  await db.query('TRUNCATE TABLE task_attachments');
  await db.query('TRUNCATE TABLE tasks');
  await db.query('TRUNCATE TABLE users');
  await db.query('TRUNCATE TABLE system_settings');
  await db.query('SET FOREIGN_KEY_CHECKS = 1');

  const passwordHash = hashPassword('Password123!');

  // Insert Users
  const userSql = `
    INSERT INTO users (name, email, password_hash, role, department, status, avatar_color)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  `;

  const superAdmin = await db.run(userSql, [
    'Alexander Vance',
    'superadmin@apextask.com',
    passwordHash,
    'super_admin',
    'Executive Leadership',
    'active',
    '#7c3aed'
  ]);

  const admin = await db.run(userSql, [
    'Sarah Jenkins',
    'admin@apextask.com',
    passwordHash,
    'admin',
    'Product Operations',
    'active',
    '#2563eb'
  ]);

  const staff1 = await db.run(userSql, [
    'John Doe',
    'john.doe@apextask.com',
    passwordHash,
    'staff',
    'Frontend Engineering',
    'active',
    '#0891b2'
  ]);

  const staff2 = await db.run(userSql, [
    'Emily Chen',
    'emily.chen@apextask.com',
    passwordHash,
    'staff',
    'UI/UX Design',
    'active',
    '#db2777'
  ]);

  const staff3 = await db.run(userSql, [
    'Marcus Wright',
    'marcus.wright@apextask.com',
    passwordHash,
    'staff',
    'Cloud & DevOps',
    'active',
    '#059669'
  ]);

  const staff4 = await db.run(userSql, [
    'Priya Patel',
    'priya.patel@apextask.com',
    passwordHash,
    'staff',
    'Quality Assurance',
    'active',
    '#d97706'
  ]);

  await db.run(userSql, [
    'Robert Miller (Inactive)',
    'robert.miller@apextask.com',
    passwordHash,
    'staff',
    'Data Analytics',
    'inactive',
    '#64748b'
  ]);

  const superAdminId = superAdmin.insertId;
  const adminId = admin.insertId;
  const staff1Id = staff1.insertId;
  const staff2Id = staff2.insertId;
  const staff3Id = staff3.insertId;
  const staff4Id = staff4.insertId;

  // Insert System Settings
  const settingSql = 'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)';
  await db.run(settingSql, ['app_name', 'ApexTask Pro']);
  await db.run(settingSql, ['company_name', 'Apex Global Enterprise']);
  await db.run(settingSql, ['allow_file_uploads', 'true']);
  await db.run(settingSql, ['max_file_size_mb', '10']);
  await db.run(settingSql, ['default_user_role', 'staff']);
  await db.run(settingSql, ['task_notifications_enabled', 'true']);

  // Dates helper
  const now = new Date();
  const formatDate = (daysOffset) => {
    const d = new Date(now);
    d.setDate(d.getDate() + daysOffset);
    return d.toISOString().split('T')[0];
  };

  const taskSql = `
    INSERT INTO tasks (title, description, assigned_to, created_by, due_date, priority, status)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  `;

  const task1 = await db.run(taskSql, [
    'Implement RBAC Permission Guard in React Client',
    'Set up route protection and conditional component rendering for Super Admin, Admin, and Staff user personas.',
    staff1Id,
    adminId,
    formatDate(2),
    'high',
    'in_progress'
  ]);

  const task2 = await db.run(taskSql, [
    'Design High-Fidelity Task Detail Drawer Mockup',
    'Produce Figma components with dark and light themes, file attachment preview gallery, and comment timeline.',
    staff2Id,
    adminId,
    formatDate(1),
    'high',
    'completed'
  ]);

  const task3 = await db.run(taskSql, [
    'Configure Production Multi-Stage Docker & CI/CD Pipeline',
    'Optimize Docker image size with alpine node runtime, enable automated healthchecks, and configure caching.',
    staff3Id,
    superAdminId,
    formatDate(-2), // Overdue
    'high',
    'in_progress'
  ]);

  const task4 = await db.run(taskSql, [
    'Run End-to-End Test Suite for File Upload Validation',
    'Verify that file type checks reject malicious executables and respect the 10MB per file boundary condition.',
    staff4Id,
    adminId,
    formatDate(0), // Due today
    'medium',
    'in_progress'
  ]);

  const task5 = await db.run(taskSql, [
    'Refactor MySQL Indexes for Dashboard Analytics',
    'Add composite indexes on tasks(assigned_to, status, due_date) to accelerate aggregated metric calculations in WAMP MySQL.',
    staff3Id,
    adminId,
    formatDate(5),
    'medium',
    'todo'
  ]);

  const task6 = await db.run(taskSql, [
    'Audit Security Headers & Helmet Configuration',
    'Implement strict Content-Security-Policy headers, rate limiting on authentication routes, and CORS verification.',
    staff1Id,
    superAdminId,
    formatDate(4),
    'low',
    'todo'
  ]);

  const task7 = await db.run(taskSql, [
    'Create Mobile Responsive Hamburger Drawer Navigation',
    'Ensure all navigation links and quick-action modals fit seamlessly on viewports smaller than 768px.',
    staff2Id,
    adminId,
    formatDate(3),
    'medium',
    'completed'
  ]);

  // Create sample attachments on disk and DB
  const samplePdfPath = path.join(uploadDir, 'demo_spec_v2.txt');
  fs.writeFileSync(samplePdfPath, 'ApexTask System Architecture Specification & RBAC Security Guidelines.\nVersion: 2.4.0\nValidated: Yes');

  const sampleArchSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200" viewBox="0 0 400 200"><rect width="400" height="200" fill="#1e293b"/><text x="200" y="100" fill="#38bdf8" font-family="sans-serif" font-size="20" font-weight="bold" text-anchor="middle">ApexTask Architecture Diagram</text><text x="200" y="130" fill="#94a3b8" font-family="sans-serif" font-size="14" text-anchor="middle">Role-Based Access &amp; File Pipeline</text></svg>`;
  const sampleSvgPath = path.join(uploadDir, 'demo_diagram.svg');
  fs.writeFileSync(sampleSvgPath, sampleArchSvg);

  const attSql = `
    INSERT INTO task_attachments (task_id, uploaded_by, filename, original_name, mime_type, size)
    VALUES (?, ?, ?, ?, ?, ?)
  `;

  await db.run(attSql, [task1.insertId, adminId, 'demo_spec_v2.txt', 'Architecture_Spec_v2.txt', 'text/plain', 128]);
  await db.run(attSql, [task1.insertId, staff1Id, 'demo_diagram.svg', 'Frontend_Workflow_Diagram.svg', 'image/svg+xml', sampleArchSvg.length]);
  await db.run(attSql, [task2.insertId, staff2Id, 'demo_diagram.svg', 'UI_Design_Tokens.svg', 'image/svg+xml', sampleArchSvg.length]);

  // Comments
  const commentSql = `
    INSERT INTO task_comments (task_id, user_id, comment)
    VALUES (?, ?, ?)
  `;

  await db.run(commentSql, [task1.insertId, adminId, 'John, make sure the role check also covers unauthenticated token expiry gracefully.']);
  await db.run(commentSql, [task1.insertId, staff1Id, 'Working on it! Added JWT interceptor with automatic redirection to login and toast alert.']);
  await db.run(commentSql, [task2.insertId, staff2Id, 'Exported all SVG assets and updated contrast ratios to conform with WCAG AA accessibility standards.']);
  await db.run(commentSql, [task3.insertId, staff3Id, 'Investigating Docker layer cache miss on alpine base image. Expecting fix before EOD.']);

  // Activity logs
  const logSql = 'INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)';
  await db.run(logSql, [superAdminId, 'SYSTEM_INIT', 'System initialized and seeded with enterprise defaults in WAMP MySQL']);
  await db.run(logSql, [adminId, 'TASK_CREATED', 'Created task #1: "Implement RBAC Permission Guard"']);
  await db.run(logSql, [staff1Id, 'TASK_STATUS_CHANGED', 'Updated task #1 to In Progress']);
  await db.run(logSql, [staff2Id, 'TASK_STATUS_CHANGED', 'Marked task #2 as Completed']);

  console.log('✅ WAMP MySQL database seeded successfully:');
  console.log('   - Super Admin : superadmin@apextask.com  / Password123!');
  console.log('   - Admin       : admin@apextask.com       / Password123!');
  console.log('   - Staff 1     : john.doe@apextask.com    / Password123!');
  console.log('   - Staff 2     : emily.chen@apextask.com  / Password123!');

  process.exit(0);
}

seedDatabase().catch((err) => {
  console.error('Seeding failed:', err);
  process.exit(1);
});
