const db = require('../config/db');
const { logActivity } = require('../utils/logger');

async function getSettings(req, res) {
  try {
    const rows = await db.all('SELECT setting_key, setting_value FROM system_settings');
    const settings = {};
    for (const r of rows) {
      settings[r.setting_key] = r.setting_value;
    }

    const defaults = {
      app_name: 'ApexTask Pro',
      company_name: 'Acme Enterprise Solutions',
      allow_file_uploads: 'true',
      max_file_size_mb: '10',
      default_user_role: 'staff',
      task_notifications_enabled: 'true'
    };

    res.json({ ...defaults, ...settings });
  } catch (err) {
    console.error('Get settings error:', err);
    res.status(500).json({ error: 'Failed to fetch settings.' });
  }
}

async function updateSettings(req, res) {
  try {
    const updates = req.body;

    for (const [k, v] of Object.entries(updates)) {
      await db.run(
        `INSERT INTO system_settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)`,
        [k, String(v)]
      );
    }

    await logActivity(req.user.id, 'SETTINGS_UPDATED', 'Super Admin updated system configuration');

    res.json({ message: 'Settings saved successfully.' });
  } catch (err) {
    console.error('Update settings error:', err);
    res.status(500).json({ error: 'Failed to update system settings.' });
  }
}

module.exports = {
  getSettings,
  updateSettings
};
