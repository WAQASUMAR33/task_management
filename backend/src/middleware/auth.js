const db = require('../config/db');
const { verifyToken } = require('../utils/auth');

async function authenticate(req, res, next) {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return res.status(401).json({ error: 'Access denied. No token provided.' });
    }

    const token = authHeader.split(' ')[1];
    const decoded = verifyToken(token);
    if (!decoded) {
      return res.status(401).json({ error: 'Invalid or expired token.' });
    }

    // Fetch fresh user record from MySQL
    const user = await db.get(
      'SELECT id, name, email, role, department, status, avatar_color FROM users WHERE id = ?',
      [decoded.id]
    );

    if (!user) {
      return res.status(401).json({ error: 'User not found.' });
    }

    if (user.status !== 'active') {
      return res.status(403).json({ error: 'Account has been deactivated. Please contact an administrator.' });
    }

    req.user = user;
    next();
  } catch (err) {
    console.error('Authentication error:', err);
    res.status(500).json({ error: 'Server authentication error.' });
  }
}

function requireRoles(...allowedRoles) {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ error: 'Authentication required.' });
    }

    if (!allowedRoles.includes(req.user.role)) {
      return res.status(403).json({
        error: `Permission denied. Required role: [${allowedRoles.join(', ')}]. Your role: ${req.user.role}`
      });
    }

    next();
  };
}

module.exports = {
  authenticate,
  requireRoles
};
