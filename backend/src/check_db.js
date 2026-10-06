const db = require('./config/db');

async function testConnection() {
  console.log('====================================================');
  console.log('       🔍 DATABASE CONNECTION DIAGNOSTIC REPORT     ');
  console.log('====================================================');

  const start = Date.now();

  try {
    // 1. Basic Connection & Version
    const versionRow = await db.get('SELECT VERSION() as ver');
    const dbNameRow = await db.get('SELECT DATABASE() as dbname');
    const currentUserRow = await db.get('SELECT USER() as cur_user');
    const latency = Date.now() - start;

    console.log(`📡 Status            : ✅ CONNECTED (ONLINE)`);
    console.log(`⏱️ Round-Trip Latency: ${latency} ms`);
    console.log(`🌐 Server Host       : ${process.env.DB_HOST}:${process.env.DB_PORT || 3306}`);
    console.log(`📦 Database Name     : ${dbNameRow.dbname}`);
    console.log(`👤 Database User     : ${currentUserRow.cur_user}`);
    console.log(`🐬 Engine Version    : ${versionRow.ver}`);
    console.log('----------------------------------------------------');

    // 2. Tables & Row Counts
    const tables = await db.all('SHOW TABLES');
    console.log(`📋 Total Tables Found: ${tables.length}`);

    for (const t of tables) {
      const tableName = Object.values(t)[0];
      const countRow = await db.get(`SELECT COUNT(*) as count FROM \`${tableName}\``);
      console.log(`  • ${tableName.padEnd(20)} : ${countRow.count} records`);
    }

    console.log('----------------------------------------------------');
    // 3. Sample Users Verification
    const users = await db.all('SELECT id, name, email, role, status FROM users ORDER BY id ASC LIMIT 6');
    console.log(`👥 Active Users in Database (${users.length} loaded):`);
    users.forEach(u => {
      console.log(`  [ID: ${u.id}] ${u.name.padEnd(18)} | ${u.email.padEnd(26)} | Role: ${u.role.padEnd(12)} | Status: ${u.status}`);
    });

    console.log('----------------------------------------------------');
    // 4. Sample Tasks Verification
    const tasks = await db.all('SELECT id, title, priority, status FROM tasks ORDER BY id DESC LIMIT 5');
    console.log(`📌 Tasks in Database (${tasks.length} found):`);
    if (tasks.length === 0) {
      console.log('  (No tasks currently in database)');
    } else {
      tasks.forEach(task => {
        console.log(`  [ID: ${task.id}] ${task.title.padEnd(38)} | Priority: ${task.priority.padEnd(8)} | Status: ${task.status}`);
      });
    }

    console.log('====================================================');
    console.log('✅ RESULT: Remote MySQL database connection is 100% HEALTHY!');
    console.log('====================================================');
    process.exit(0);
  } catch (error) {
    console.error('❌ Connection Check Failed:');
    console.error(' • Error Message:', error.message);
    console.error(' • Error Code   :', error.code || 'N/A');
    console.error(' • Host Target  :', `${process.env.DB_HOST}:${process.env.DB_PORT}`);
    console.log('====================================================');
    process.exit(1);
  }
}

testConnection();
