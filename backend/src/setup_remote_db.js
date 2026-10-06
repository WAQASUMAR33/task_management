const db = require('./config/db');
const { hashPassword } = require('./utils/auth');

async function setup() {
  console.log('🚀 Initializing tables on remote database: ' + process.env.DB_NAME + '...');
  
  await db.initDatabase();

  // Check if users table already has data
  const existingUsers = await db.all('SELECT id, email FROM users');
  if (existingUsers.length === 0) {
    console.log('👤 Seeding default RBAC user accounts...');
    const passwordHash = hashPassword('Password123!');

    const userSql = `
      INSERT INTO users (name, email, password_hash, role, department, status, avatar_color)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    `;

    await db.run(userSql, ['Alexander Vance', 'superadmin@apextask.com', passwordHash, 'super_admin', 'Executive Leadership', 'active', '#7c3aed']);
    await db.run(userSql, ['Sarah Jenkins', 'admin@apextask.com', passwordHash, 'admin', 'Product Operations', 'active', '#2563eb']);
    await db.run(userSql, ['John Doe', 'john.doe@apextask.com', passwordHash, 'staff', 'Frontend Engineering', 'active', '#0891b2']);
    await db.run(userSql, ['Emily Chen', 'emily.chen@apextask.com', passwordHash, 'staff', 'UI/UX Design', 'active', '#db2777']);
    await db.run(userSql, ['Marcus Wright', 'marcus.wright@apextask.com', passwordHash, 'staff', 'Cloud & DevOps', 'active', '#059669']);
    await db.run(userSql, ['Priya Patel', 'priya.patel@apextask.com', passwordHash, 'staff', 'Quality Assurance', 'active', '#d97706']);

    console.log('✅ 6 core RBAC accounts created.');
  } else {
    console.log(`ℹ️ Users already exist (${existingUsers.length} found). Skipping user creation.`);
  }

  // Ensure default system settings exist
  const existingSettings = await db.all('SELECT setting_key FROM system_settings');
  if (existingSettings.length === 0) {
    console.log('⚙️ Initializing system settings...');
    const settingSql = 'INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, ?)';
    await db.run(settingSql, ['app_name', 'ApexTask Pro']);
    await db.run(settingSql, ['company_name', 'Apex Global Enterprise']);
    await db.run(settingSql, ['allow_file_uploads', 'true']);
    await db.run(settingSql, ['max_file_size_mb', '10']);
    await db.run(settingSql, ['default_user_role', 'staff']);
    await db.run(settingSql, ['task_notifications_enabled', 'true']);
    console.log('✅ System settings initialized.');
  }

  // Summary
  const tables = await db.all('SHOW TABLES');
  console.log('\n📊 Remote Database Table Summary:');
  for (const t of tables) {
    const tableName = Object.values(t)[0];
    const countRow = await db.get(`SELECT COUNT(*) as count FROM ${tableName}`);
    console.log(` • ${tableName.padEnd(22)} : ${countRow.count} rows`);
  }

  process.exit(0);
}

setup().catch(err => {
  console.error('❌ Setup error:', err);
  process.exit(1);
});
