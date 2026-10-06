const db = require('../config/db');
const { comparePassword, hashPassword, generateToken } = require('../utils/auth');
const { logActivity } = require('../utils/logger');

async function login(req, res) {
  try {
    const { email, password } = req.body;

    if (!email || !password) {
      return res.status(400).json({ error: 'Email and password are required.' });
    }

    const user = await db.get('SELECT * FROM users WHERE LOWER(email) = LOWER(?)', [email.trim()]);
    if (!user) {
      return res.status(401).json({ error: 'Invalid email or password.' });
    }

    if (user.status !== 'active') {
      return res.status(403).json({ error: 'Your account is deactivated. Please contact an administrator.' });
    }

    const isMatch = comparePassword(password, user.password_hash);
    if (!isMatch) {
      return res.status(401).json({ error: 'Invalid email or password.' });
    }

    const token = generateToken(user);

    await logActivity(user.id, 'USER_LOGIN', `User logged in: ${user.email}`);

    res.json({
      message: 'Login successful',
      token,
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
        department: user.department,
        status: user.status,
        avatar_color: user.avatar_color
      }
    });
  } catch (err) {
    console.error('Login error:', err);
    res.status(500).json({ error: 'Server error during login.' });
  }
}

async function getMe(req, res) {
  res.json({ user: req.user });
}

async function changePassword(req, res) {
  try {
    const { currentPassword, newPassword } = req.body;
    if (!currentPassword || !newPassword) {
      return res.status(400).json({ error: 'Current password and new password are required.' });
    }

    if (newPassword.length < 6) {
      return res.status(400).json({ error: 'New password must be at least 6 characters long.' });
    }

    const user = await db.get('SELECT * FROM users WHERE id = ?', [req.user.id]);
    const isMatch = comparePassword(currentPassword, user.password_hash);
    if (!isMatch) {
      return res.status(400).json({ error: 'Current password does not match.' });
    }

    const newHash = hashPassword(newPassword);
    await db.run('UPDATE users SET password_hash = ? WHERE id = ?', [newHash, req.user.id]);

    await logActivity(req.user.id, 'PASSWORD_CHANGE', 'User changed their password');

    res.json({ message: 'Password updated successfully.' });
  } catch (err) {
    console.error('Change password error:', err);
    res.status(500).json({ error: 'Failed to change password.' });
  }
}

async function updateProfile(req, res) {
  try {
    const { name, department, avatar_color } = req.body;
    if (!name) {
      return res.status(400).json({ error: 'Name is required.' });
    }

    await db.run(
      'UPDATE users SET name = ?, department = COALESCE(?, department), avatar_color = COALESCE(?, avatar_color) WHERE id = ?',
      [name.trim(), department ? department.trim() : null, avatar_color || null, req.user.id]
    );

    const updatedUser = await db.get(
      'SELECT id, name, email, role, department, status, avatar_color FROM users WHERE id = ?',
      [req.user.id]
    );
    
    await logActivity(req.user.id, 'PROFILE_UPDATE', 'User updated their profile');

    res.json({ message: 'Profile updated successfully', user: updatedUser });
  } catch (err) {
    console.error('Update profile error:', err);
    res.status(500).json({ error: 'Failed to update profile.' });
  }
}

module.exports = {
  login,
  getMe,
  changePassword,
  updateProfile
};
