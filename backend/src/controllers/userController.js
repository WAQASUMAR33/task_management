const db = require('../config/db');
const { hashPassword } = require('../utils/auth');
const { logActivity } = require('../utils/logger');

const avatarColors = [
  '#4f46e5', '#2563eb', '#0891b2', '#0d9488',
  '#059669', '#d97706', '#dc2626', '#7c3aed', '#db2777'
];

function getRandomColor() {
  return avatarColors[Math.floor(Math.random() * avatarColors.length)];
}

async function getAllUsers(req, res) {
  try {
    const { role, status, search, department } = req.query;

    let queryStr = 'SELECT id, name, email, role, department, status, avatar_color, created_at, updated_at FROM users WHERE 1=1';
    const params = [];

    if (role) {
      queryStr += ' AND role = ?';
      params.push(role);
    }

    if (status) {
      queryStr += ' AND status = ?';
      params.push(status);
    }

    if (department) {
      queryStr += ' AND department = ?';
      params.push(department);
    }

    if (search) {
      queryStr += ' AND (name LIKE ? OR email LIKE ? OR department LIKE ?)';
      params.push(`%${search}%`, `%${search}%`, `%${search}%`);
    }

    queryStr += ' ORDER BY created_at DESC';

    const users = await db.all(queryStr, params);

    // Get task stats per user
    const allTaskStats = await db.all(`
      SELECT 
        assigned_to,
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks
      FROM tasks
      WHERE assigned_to IS NOT NULL
      GROUP BY assigned_to
    `);

    const statsMap = {};
    for (const stat of allTaskStats) {
      statsMap[stat.assigned_to] = stat;
    }

    const enhancedUsers = users.map(u => ({
      ...u,
      stats: statsMap[u.id] || { total_tasks: 0, completed_tasks: 0, in_progress_tasks: 0 }
    }));

    res.json(enhancedUsers);
  } catch (err) {
    console.error('Get all users error:', err);
    res.status(500).json({ error: 'Failed to fetch users.' });
  }
}

async function getUserById(req, res) {
  try {
    const user = await db.get(
      'SELECT id, name, email, role, department, status, avatar_color, created_at, updated_at FROM users WHERE id = ?',
      [req.params.id]
    );
    if (!user) {
      return res.status(404).json({ error: 'User not found.' });
    }
    res.json(user);
  } catch (err) {
    console.error('Get user by id error:', err);
    res.status(500).json({ error: 'Failed to retrieve user.' });
  }
}

async function createUser(req, res) {
  try {
    const { name, email, password, role, department } = req.body;

    if (!name || !email || !password || !role) {
      return res.status(400).json({ error: 'Name, email, password, and role are required.' });
    }

    const validRoles = ['super_admin', 'admin', 'staff'];
    if (!validRoles.includes(role)) {
      return res.status(400).json({ error: 'Invalid role specified.' });
    }

    if (role === 'super_admin' && req.user.role !== 'super_admin') {
      return res.status(403).json({ error: 'Only Super Admins can create new Super Admin accounts.' });
    }
    if (role === 'admin' && req.user.role !== 'super_admin') {
      return res.status(403).json({ error: 'Only Super Admins can create Admin accounts.' });
    }

    const existing = await db.get('SELECT id FROM users WHERE LOWER(email) = LOWER(?)', [email.trim()]);
    if (existing) {
      return res.status(400).json({ error: 'A user with this email address already exists.' });
    }

    const passwordHash = hashPassword(password);
    const color = getRandomColor();

    const result = await db.run(
      `INSERT INTO users (name, email, password_hash, role, department, status, avatar_color)
       VALUES (?, ?, ?, ?, ?, 'active', ?)`,
      [name.trim(), email.trim().toLowerCase(), passwordHash, role, department ? department.trim() : 'General', color]
    );

    const newUser = await db.get(
      'SELECT id, name, email, role, department, status, avatar_color, created_at FROM users WHERE id = ?',
      [result.insertId]
    );

    await logActivity(req.user.id, 'USER_CREATED', `Created user: ${newUser.name} (${newUser.role})`);

    res.status(201).json({
      message: 'User created successfully.',
      user: newUser
    });
  } catch (err) {
    console.error('Create user error:', err);
    res.status(500).json({ error: 'Failed to create user.' });
  }
}

async function updateUser(req, res) {
  try {
    const userId = parseInt(req.params.id, 10);
    const { name, email, role, department, status } = req.body;

    const targetUser = await db.get('SELECT * FROM users WHERE id = ?', [userId]);
    if (!targetUser) {
      return res.status(404).json({ error: 'User not found.' });
    }

    if (req.user.role === 'admin') {
      if (targetUser.role === 'super_admin' || (targetUser.role === 'admin' && targetUser.id !== req.user.id)) {
        return res.status(403).json({ error: 'Admins cannot modify Super Admins or other Admins.' });
      }
      if (role && (role === 'super_admin' || role === 'admin') && role !== targetUser.role) {
        return res.status(403).json({ error: 'Admins cannot promote users to Admin or Super Admin.' });
      }
    }

    if (email && email.toLowerCase() !== targetUser.email.toLowerCase()) {
      const emailConflict = await db.get('SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?', [email.trim(), userId]);
      if (emailConflict) {
        return res.status(400).json({ error: 'Email address is already in use by another account.' });
      }
    }

    if (targetUser.role === 'super_admin') {
      const superAdminCountRow = await db.get("SELECT COUNT(*) as count FROM users WHERE role = 'super_admin' AND status = 'active'");
      const superAdminCount = superAdminCountRow ? superAdminCountRow.count : 0;
      if (superAdminCount <= 1 && (status === 'inactive' || (role && role !== 'super_admin'))) {
        return res.status(400).json({ error: 'Cannot deactivate or demote the system primary Super Admin.' });
      }
    }

    const updatedName = name !== undefined ? name.trim() : targetUser.name;
    const updatedEmail = email !== undefined ? email.trim().toLowerCase() : targetUser.email;
    const updatedRole = role !== undefined ? role : targetUser.role;
    const updatedDept = department !== undefined ? department.trim() : targetUser.department;
    const updatedStatus = status !== undefined ? status : targetUser.status;

    await db.run(
      `UPDATE users 
       SET name = ?, email = ?, role = ?, department = ?, status = ?
       WHERE id = ?`,
      [updatedName, updatedEmail, updatedRole, updatedDept, updatedStatus, userId]
    );

    const updated = await db.get(
      'SELECT id, name, email, role, department, status, avatar_color, created_at, updated_at FROM users WHERE id = ?',
      [userId]
    );

    await logActivity(req.user.id, 'USER_UPDATED', `Updated user: ${updated.name} (Role: ${updated.role}, Status: ${updated.status})`);

    res.json({ message: 'User updated successfully.', user: updated });
  } catch (err) {
    console.error('Update user error:', err);
    res.status(500).json({ error: 'Failed to update user.' });
  }
}

async function toggleUserStatus(req, res) {
  try {
    const userId = parseInt(req.params.id, 10);
    const targetUser = await db.get('SELECT * FROM users WHERE id = ?', [userId]);

    if (!targetUser) {
      return res.status(404).json({ error: 'User not found.' });
    }

    if (targetUser.id === req.user.id) {
      return res.status(400).json({ error: 'You cannot deactivate your own account.' });
    }

    if (req.user.role === 'admin' && (targetUser.role === 'super_admin' || targetUser.role === 'admin')) {
      return res.status(403).json({ error: 'Admins cannot deactivate Admins or Super Admins.' });
    }

    const newStatus = targetUser.status === 'active' ? 'inactive' : 'active';
    await db.run('UPDATE users SET status = ? WHERE id = ?', [newStatus, userId]);

    await logActivity(req.user.id, 'USER_STATUS_TOGGLED', `Changed ${targetUser.name}'s status to ${newStatus}`);

    res.json({ message: `User account is now ${newStatus}.`, status: newStatus });
  } catch (err) {
    console.error('Toggle status error:', err);
    res.status(500).json({ error: 'Failed to update user status.' });
  }
}

async function resetUserPassword(req, res) {
  try {
    const userId = parseInt(req.params.id, 10);
    const { newPassword } = req.body;

    if (!newPassword || newPassword.length < 6) {
      return res.status(400).json({ error: 'New password must be at least 6 characters long.' });
    }

    const targetUser = await db.get('SELECT * FROM users WHERE id = ?', [userId]);
    if (!targetUser) {
      return res.status(404).json({ error: 'User not found.' });
    }

    if (req.user.role === 'admin' && (targetUser.role === 'super_admin' || targetUser.role === 'admin')) {
      return res.status(403).json({ error: 'Admins cannot reset passwords for Admins or Super Admins.' });
    }

    const newHash = hashPassword(newPassword);
    await db.run('UPDATE users SET password_hash = ? WHERE id = ?', [newHash, userId]);

    await logActivity(req.user.id, 'PASSWORD_RESET', `Admin reset password for user: ${targetUser.name}`);

    res.json({ message: `Password for ${targetUser.name} has been reset successfully.` });
  } catch (err) {
    console.error('Reset user password error:', err);
    res.status(500).json({ error: 'Failed to reset password.' });
  }
}

async function deleteUser(req, res) {
  try {
    const userId = parseInt(req.params.id, 10);
    const targetUser = await db.get('SELECT * FROM users WHERE id = ?', [userId]);

    if (!targetUser) {
      return res.status(404).json({ error: 'User not found.' });
    }

    if (targetUser.id === req.user.id) {
      return res.status(400).json({ error: 'You cannot delete your own account.' });
    }

    if (req.user.role === 'admin' && (targetUser.role === 'super_admin' || targetUser.role === 'admin')) {
      return res.status(403).json({ error: 'Admins cannot delete Admins or Super Admins.' });
    }

    await db.run('DELETE FROM users WHERE id = ?', [userId]);

    await logActivity(req.user.id, 'USER_DELETED', `Deleted user: ${targetUser.name} (${targetUser.email})`);

    res.json({ message: 'User deleted successfully.' });
  } catch (err) {
    console.error('Delete user error:', err);
    res.status(500).json({ error: 'Failed to delete user.' });
  }
}

module.exports = {
  getAllUsers,
  getUserById,
  createUser,
  updateUser,
  toggleUserStatus,
  resetUserPassword,
  deleteUser
};
