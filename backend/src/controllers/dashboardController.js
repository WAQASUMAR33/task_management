const db = require('../config/db');

async function getStats(req, res) {
  try {
    const today = new Date().toISOString().split('T')[0];
    const isStaff = req.user.role === 'staff';

    let baseFilter = '';
    let params = [];

    if (isStaff) {
      baseFilter = ' WHERE (assigned_to = ? OR created_by = ?)';
      params = [req.user.id, req.user.id];
    }

    // Total tasks
    const totalRow = await db.get(`SELECT COUNT(*) as count FROM tasks ${baseFilter}`, params);
    const totalTasks = totalRow ? totalRow.count : 0;

    // By status
    const statusRows = await db.all(`
      SELECT status, COUNT(*) as count 
      FROM tasks ${baseFilter}
      GROUP BY status
    `, params);

    const statusCounts = { todo: 0, in_progress: 0, completed: 0 };
    for (const r of statusRows) {
      if (statusCounts[r.status] !== undefined) {
        statusCounts[r.status] = r.count;
      }
    }

    // By priority
    const priorityRows = await db.all(`
      SELECT priority, COUNT(*) as count 
      FROM tasks ${baseFilter}
      GROUP BY priority
    `, params);

    const priorityCounts = { low: 0, medium: 0, high: 0 };
    for (const r of priorityRows) {
      if (priorityCounts[r.priority] !== undefined) {
        priorityCounts[r.priority] = r.count;
      }
    }

    // Overdue tasks
    let overdueQuery = "SELECT COUNT(*) as count FROM tasks WHERE due_date < ? AND status != 'completed'";
    let overdueParams = [today];
    if (isStaff) {
      overdueQuery += ' AND (assigned_to = ? OR created_by = ?)';
      overdueParams.push(req.user.id, req.user.id);
    }
    const overdueRow = await db.get(overdueQuery, overdueParams);
    const overdueCount = overdueRow ? overdueRow.count : 0;

    // Tasks due today
    let dueTodayQuery = "SELECT COUNT(*) as count FROM tasks WHERE due_date = ? AND status != 'completed'";
    let dueTodayParams = [today];
    if (isStaff) {
      dueTodayQuery += ' AND (assigned_to = ? OR created_by = ?)';
      dueTodayParams.push(req.user.id, req.user.id);
    }
    const dueTodayRow = await db.get(dueTodayQuery, dueTodayParams);
    const dueTodayCount = dueTodayRow ? dueTodayRow.count : 0;

    // Completion rate
    const completionRate = totalTasks > 0 ? Math.round((statusCounts.completed / totalTasks) * 100) : 0;

    let staffWorkload = [];
    let departmentStats = [];
    let systemUserCounts = { total: 0, admins: 0, staff: 0, active: 0 };

    if (!isStaff) {
      staffWorkload = await db.all(`
        SELECT 
          u.id, u.name, u.email, u.department, u.avatar_color,
          COUNT(t.id) as total_tasks,
          SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
          SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
          SUM(CASE WHEN t.status = 'todo' THEN 1 ELSE 0 END) as todo_tasks,
          SUM(CASE WHEN t.due_date < ? AND t.status != 'completed' THEN 1 ELSE 0 END) as overdue_tasks
        FROM users u
        LEFT JOIN tasks t ON u.id = t.assigned_to
        WHERE u.role = 'staff' AND u.status = 'active'
        GROUP BY u.id
        ORDER BY total_tasks DESC
      `, [today]);

      departmentStats = await db.all(`
        SELECT 
          COALESCE(u.department, 'General') as department,
          COUNT(t.id) as task_count,
          SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_count
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id
        GROUP BY u.department
      `);

      const userCounts = await db.get(`
        SELECT 
          COUNT(*) as total,
          SUM(CASE WHEN role IN ('super_admin', 'admin') THEN 1 ELSE 0 END) as admins,
          SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff,
          SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
        FROM users
      `);
      if (userCounts) {
        systemUserCounts = userCounts;
      }
    }

    // Recent activity
    let activityQuery = `
      SELECT a.id, a.user_id, a.action, a.details, a.created_at,
             u.name as user_name, u.role as user_role, u.avatar_color as user_color
      FROM activity_logs a
      LEFT JOIN users u ON a.user_id = u.id
    `;
    let activityParams = [];
    if (isStaff) {
      activityQuery += ' WHERE a.user_id = ?';
      activityParams.push(req.user.id);
    }
    activityQuery += ' ORDER BY a.created_at DESC LIMIT 10';

    const recentActivity = await db.all(activityQuery, activityParams);

    res.json({
      role: req.user.role,
      totalTasks,
      statusCounts,
      priorityCounts,
      overdueCount,
      dueTodayCount,
      completionRate,
      staffWorkload,
      departmentStats,
      systemUserCounts,
      recentActivity
    });
  } catch (err) {
    console.error('Get stats error:', err);
    res.status(500).json({ error: 'Failed to retrieve dashboard statistics.' });
  }
}

module.exports = {
  getStats
};
