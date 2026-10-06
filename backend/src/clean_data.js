const db = require('./config/db');

async function cleanData() {
  console.log('🧹 Cleaning all dummy tasks, comments, attachments, and logs from WAMP MySQL...');

  await db.query('SET FOREIGN_KEY_CHECKS = 0');
  
  // Wipe all dummy tasks, attachments, comments, and activity logs
  await db.query('TRUNCATE TABLE activity_logs');
  await db.query('TRUNCATE TABLE task_comments');
  await db.query('TRUNCATE TABLE task_attachments');
  await db.query('TRUNCATE TABLE tasks');

  // Remove any fake/dummy inactive user accounts
  await db.query("DELETE FROM users WHERE email LIKE '%robert%' OR name LIKE '%Inactive%'");

  await db.query('SET FOREIGN_KEY_CHECKS = 1');

  // Verify counts
  const tasksCount = await db.get('SELECT COUNT(*) as count FROM tasks');
  const usersCount = await db.get('SELECT COUNT(*) as count FROM users');
  const attCount = await db.get('SELECT COUNT(*) as count FROM task_attachments');
  const commentCount = await db.get('SELECT COUNT(*) as count FROM task_comments');

  console.log('✅ Clean complete:');
  console.log(' • Tasks remaining       :', tasksCount.count);
  console.log(' • Attachments remaining :', attCount.count);
  console.log(' • Comments remaining    :', commentCount.count);
  console.log(' • Active Users          :', usersCount.count);

  process.exit(0);
}

cleanData().catch((err) => {
  console.error('Clean failed:', err);
  process.exit(1);
});
