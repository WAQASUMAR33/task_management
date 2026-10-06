const db = require('./config/db');

async function check() {
  try {
    const version = await db.get('SELECT VERSION() as ver');
    const dbName = await db.get('SELECT DATABASE() as db');
    const tables = await db.all('SHOW TABLES');

    console.log('✅ Connection Status : CONNECTED');
    console.log('📦 Database Name     : ' + dbName.db);
    console.log('🐬 MySQL Version     : ' + version.ver);
    console.log('📋 Tables Found      : ' + tables.length);

    console.log('\n--- Table Details in WAMP MySQL ---');
    for (const t of tables) {
      const tableName = Object.values(t)[0];
      const countRow = await db.get(`SELECT COUNT(*) as count FROM ${tableName}`);
      console.log(` • ${tableName.padEnd(20)} : ${countRow.count} records`);
    }
    process.exit(0);
  } catch (e) {
    console.error('❌ Connection Failed:', e.message);
    process.exit(1);
  }
}

check();
