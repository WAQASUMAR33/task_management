const db = require('../config/db');

async function logActivity(userId, action, details) {
  try {
    await db.run(
      'INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)',
      [userId || null, action, typeof details === 'object' ? JSON.stringify(details) : details]
    );
  } catch (err) {
    console.error('Failed to log activity:', err);
  }
}

module.exports = {
  logActivity
};
