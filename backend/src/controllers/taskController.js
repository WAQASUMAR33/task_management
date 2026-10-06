const path = require('path');
const fs = require('fs');
const db = require('../config/db');
const { logActivity } = require('../utils/logger');

async function getAllTasks(req, res) {
  try {
    const { status, priority, assigned_to, search, due_date_from, due_date_to, overdue } = req.query;

    let queryStr = `
      SELECT 
        t.id, t.title, t.description, t.assigned_to, t.created_by, t.due_date, t.priority, t.status,
        t.created_at, t.updated_at,
        u_assigned.name as assigned_name, u_assigned.email as assigned_email, u_assigned.avatar_color as assigned_color, u_assigned.department as assigned_department,
        u_creator.name as creator_name,
        (SELECT COUNT(*) FROM task_attachments a WHERE a.task_id = t.id) as attachment_count,
        (SELECT COUNT(*) FROM task_comments c WHERE c.task_id = t.id) as comment_count
      FROM tasks t
      LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
      LEFT JOIN users u_creator ON t.created_by = u_creator.id
      WHERE 1=1
    `;
    const params = [];

    // Role-based visibility: Staff can only see tasks assigned to them or created by them
    if (req.user.role === 'staff') {
      queryStr += ' AND (t.assigned_to = ? OR t.created_by = ?)';
      params.push(req.user.id, req.user.id);
    }

    if (status) {
      queryStr += ' AND t.status = ?';
      params.push(status);
    }

    if (priority) {
      queryStr += ' AND t.priority = ?';
      params.push(priority);
    }

    if (assigned_to) {
      queryStr += ' AND t.assigned_to = ?';
      params.push(parseInt(assigned_to, 10));
    }

    if (search) {
      queryStr += ' AND (t.title LIKE ? OR t.description LIKE ?)';
      params.push(`%${search}%`, `%${search}%`);
    }

    if (due_date_from) {
      queryStr += ' AND t.due_date >= ?';
      params.push(due_date_from);
    }

    if (due_date_to) {
      queryStr += ' AND t.due_date <= ?';
      params.push(due_date_to);
    }

    if (overdue === 'true') {
      const today = new Date().toISOString().split('T')[0];
      queryStr += " AND t.due_date < ? AND t.status != 'completed'";
      params.push(today);
    }

    queryStr += " ORDER BY CASE t.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END ASC, t.created_at DESC";

    const tasks = await db.all(queryStr, params);
    res.json(tasks);
  } catch (err) {
    console.error('Get all tasks error:', err);
    res.status(500).json({ error: 'Failed to fetch tasks.' });
  }
}

async function getTaskById(req, res) {
  try {
    const taskId = parseInt(req.params.id, 10);

    const taskQuery = `
      SELECT 
        t.id, t.title, t.description, t.assigned_to, t.created_by, t.due_date, t.priority, t.status,
        t.created_at, t.updated_at,
        u_assigned.name as assigned_name, u_assigned.email as assigned_email, u_assigned.avatar_color as assigned_color, u_assigned.department as assigned_department,
        u_creator.name as creator_name
      FROM tasks t
      LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
      LEFT JOIN users u_creator ON t.created_by = u_creator.id
      WHERE t.id = ?
    `;

    const task = await db.get(taskQuery, [taskId]);
    if (!task) {
      return res.status(404).json({ error: 'Task not found.' });
    }

    if (req.user.role === 'staff' && task.assigned_to !== req.user.id && task.created_by !== req.user.id) {
      return res.status(403).json({ error: 'You do not have permission to view this task.' });
    }

    const attachments = await db.all(`
      SELECT a.id, a.task_id, a.uploaded_by, a.filename, a.original_name, a.mime_type, a.size, a.created_at,
             u.name as uploader_name
      FROM task_attachments a
      LEFT JOIN users u ON a.uploaded_by = u.id
      WHERE a.task_id = ?
      ORDER BY a.created_at DESC
    `, [taskId]);

    const comments = await db.all(`
      SELECT c.id, c.task_id, c.user_id, c.comment, c.created_at,
             u.name as author_name, u.role as author_role, u.avatar_color as author_color
      FROM task_comments c
      LEFT JOIN users u ON c.user_id = u.id
      WHERE c.task_id = ?
      ORDER BY c.created_at ASC
    `, [taskId]);

    res.json({
      ...task,
      attachments,
      comments
    });
  } catch (err) {
    console.error('Get task by id error:', err);
    res.status(500).json({ error: 'Failed to retrieve task details.' });
  }
}

async function createTask(req, res) {
  try {
    const { title, description, assigned_to, due_date, priority, status } = req.body;

    if (!title) {
      return res.status(400).json({ error: 'Task title is required.' });
    }

    if (req.user.role === 'staff') {
      return res.status(403).json({ error: 'Staff members cannot create new tasks. Contact your Admin.' });
    }

    let assignedId = assigned_to ? parseInt(assigned_to, 10) : null;
    if (assignedId) {
      const assignedUser = await db.get('SELECT id, name FROM users WHERE id = ?', [assignedId]);
      if (!assignedUser) {
        return res.status(400).json({ error: 'Assigned user does not exist.' });
      }
    }

    const taskPriority = priority || 'medium';
    const taskStatus = status || 'todo';

    const result = await db.run(
      `INSERT INTO tasks (title, description, assigned_to, created_by, due_date, priority, status)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [title.trim(), description ? description.trim() : null, assignedId, req.user.id, due_date || null, taskPriority, taskStatus]
    );

    const taskId = result.insertId;

    if (req.files && req.files.length > 0) {
      for (const file of req.files) {
        await db.run(
          `INSERT INTO task_attachments (task_id, uploaded_by, filename, original_name, mime_type, size)
           VALUES (?, ?, ?, ?, ?, ?)`,
          [taskId, req.user.id, file.filename, file.originalname, file.mimetype, file.size]
        );
      }
    }

    await logActivity(req.user.id, 'TASK_CREATED', `Created task #${taskId}: "${title.trim()}"`);

    const newTask = await db.get(
      `SELECT t.*, u.name as assigned_name 
       FROM tasks t 
       LEFT JOIN users u ON t.assigned_to = u.id 
       WHERE t.id = ?`,
      [taskId]
    );

    res.status(201).json({
      message: 'Task created successfully.',
      task: newTask
    });
  } catch (err) {
    console.error('Create task error:', err);
    res.status(500).json({ error: 'Failed to create task.' });
  }
}

async function updateTask(req, res) {
  try {
    const taskId = parseInt(req.params.id, 10);
    const existing = await db.get('SELECT * FROM tasks WHERE id = ?', [taskId]);

    if (!existing) {
      return res.status(404).json({ error: 'Task not found.' });
    }

    if (req.user.role === 'staff') {
      return res.status(403).json({ error: 'Staff members can only update task status or add comments.' });
    }

    const { title, description, assigned_to, due_date, priority, status } = req.body;

    let assignedId = assigned_to !== undefined ? (assigned_to ? parseInt(assigned_to, 10) : null) : existing.assigned_to;
    if (assignedId && assignedId !== existing.assigned_to) {
      const userExists = await db.get('SELECT id FROM users WHERE id = ?', [assignedId]);
      if (!userExists) {
        return res.status(400).json({ error: 'Assigned user does not exist.' });
      }
    }

    const updatedTitle = title !== undefined ? title.trim() : existing.title;
    const updatedDesc = description !== undefined ? description : existing.description;
    const updatedDueDate = due_date !== undefined ? due_date : existing.due_date;
    const updatedPriority = priority !== undefined ? priority : existing.priority;
    const updatedStatus = status !== undefined ? status : existing.status;

    await db.run(
      `UPDATE tasks
       SET title = ?, description = ?, assigned_to = ?, due_date = ?, priority = ?, status = ?
       WHERE id = ?`,
      [updatedTitle, updatedDesc, assignedId, updatedDueDate, updatedPriority, updatedStatus, taskId]
    );

    if (req.files && req.files.length > 0) {
      for (const file of req.files) {
        await db.run(
          `INSERT INTO task_attachments (task_id, uploaded_by, filename, original_name, mime_type, size)
           VALUES (?, ?, ?, ?, ?, ?)`,
          [taskId, req.user.id, file.filename, file.originalname, file.mimetype, file.size]
        );
      }
    }

    await logActivity(req.user.id, 'TASK_UPDATED', `Updated task #${taskId}: "${updatedTitle}"`);

    const updated = await db.get(
      `SELECT t.*, u.name as assigned_name 
       FROM tasks t 
       LEFT JOIN users u ON t.assigned_to = u.id 
       WHERE t.id = ?`,
      [taskId]
    );

    res.json({ message: 'Task updated successfully.', task: updated });
  } catch (err) {
    console.error('Update task error:', err);
    res.status(500).json({ error: 'Failed to update task.' });
  }
}

async function updateTaskStatus(req, res) {
  try {
    const taskId = parseInt(req.params.id, 10);
    const { status } = req.body;

    const validStatuses = ['todo', 'in_progress', 'completed'];
    if (!validStatuses.includes(status)) {
      return res.status(400).json({ error: 'Invalid status. Must be todo, in_progress, or completed.' });
    }

    const task = await db.get('SELECT * FROM tasks WHERE id = ?', [taskId]);
    if (!task) {
      return res.status(404).json({ error: 'Task not found.' });
    }

    if (req.user.role === 'staff' && task.assigned_to !== req.user.id) {
      return res.status(403).json({ error: 'You can only update status for tasks assigned to you.' });
    }

    await db.run('UPDATE tasks SET status = ? WHERE id = ?', [status, taskId]);

    await logActivity(req.user.id, 'TASK_STATUS_CHANGED', `Changed task #${taskId} status from ${task.status} to ${status}`);

    res.json({ message: `Task status changed to ${status}.`, status });
  } catch (err) {
    console.error('Update status error:', err);
    res.status(500).json({ error: 'Failed to update task status.' });
  }
}

async function updateTaskAssignee(req, res) {
  try {
    const taskId = parseInt(req.params.id, 10);
    const { assigned_to } = req.body;

    if (req.user.role === 'staff') {
      return res.status(403).json({ error: 'Only Admins and Super Admins can re-assign tasks.' });
    }

    const task = await db.get('SELECT * FROM tasks WHERE id = ?', [taskId]);
    if (!task) {
      return res.status(404).json({ error: 'Task not found.' });
    }

    let targetUserId = assigned_to ? parseInt(assigned_to, 10) : null;
    let targetUserName = 'Unassigned';

    if (targetUserId) {
      const user = await db.get('SELECT id, name FROM users WHERE id = ?', [targetUserId]);
      if (!user) {
        return res.status(400).json({ error: 'User does not exist.' });
      }
      targetUserName = user.name;
    }

    await db.run('UPDATE tasks SET assigned_to = ? WHERE id = ?', [targetUserId, taskId]);

    await logActivity(req.user.id, 'TASK_REASSIGNED', `Reassigned task #${taskId} to ${targetUserName}`);

    res.json({ message: `Task reassigned to ${targetUserName}.`, assigned_to: targetUserId, assigned_name: targetUserName });
  } catch (err) {
    console.error('Update assignee error:', err);
    res.status(500).json({ error: 'Failed to reassign task.' });
  }
}

async function deleteTask(req, res) {
  try {
    const taskId = parseInt(req.params.id, 10);

    if (req.user.role === 'staff') {
      return res.status(403).json({ error: 'Staff members cannot delete tasks.' });
    }

    const task = await db.get('SELECT * FROM tasks WHERE id = ?', [taskId]);
    if (!task) {
      return res.status(404).json({ error: 'Task not found.' });
    }

    const attachments = await db.all('SELECT filename FROM task_attachments WHERE task_id = ?', [taskId]);
    for (const att of attachments) {
      const filePath = path.join(__dirname, '..', '..', 'uploads', att.filename);
      if (fs.existsSync(filePath)) {
        try { fs.unlinkSync(filePath); } catch (e) {}
      }
    }

    await db.run('DELETE FROM tasks WHERE id = ?', [taskId]);

    await logActivity(req.user.id, 'TASK_DELETED', `Deleted task #${taskId}: "${task.title}"`);

    res.json({ message: 'Task deleted successfully.' });
  } catch (err) {
    console.error('Delete task error:', err);
    res.status(500).json({ error: 'Failed to delete task.' });
  }
}

async function addComment(req, res) {
  try {
    const taskId = parseInt(req.params.id, 10);
    const { comment } = req.body;

    if (!comment || !comment.trim()) {
      return res.status(400).json({ error: 'Comment text is required.' });
    }

    const task = await db.get('SELECT * FROM tasks WHERE id = ?', [taskId]);
    if (!task) {
      return res.status(404).json({ error: 'Task not found.' });
    }

    if (req.user.role === 'staff' && task.assigned_to !== req.user.id && task.created_by !== req.user.id) {
      return res.status(403).json({ error: 'You can only comment on tasks assigned to you.' });
    }

    const result = await db.run(
      'INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)',
      [taskId, req.user.id, comment.trim()]
    );

    const newComment = await db.get(
      `SELECT c.id, c.task_id, c.user_id, c.comment, c.created_at,
              u.name as author_name, u.role as author_role, u.avatar_color as author_color
       FROM task_comments c
       LEFT JOIN users u ON c.user_id = u.id
       WHERE c.id = ?`,
      [result.insertId]
    );

    await logActivity(req.user.id, 'TASK_COMMENT_ADDED', `Added comment to task #${taskId}`);

    res.status(201).json(newComment);
  } catch (err) {
    console.error('Add comment error:', err);
    res.status(500).json({ error: 'Failed to add comment.' });
  }
}

async function getComments(req, res) {
  try {
    const taskId = parseInt(req.params.id, 10);
    const comments = await db.all(
      `SELECT c.id, c.task_id, c.user_id, c.comment, c.created_at,
              u.name as author_name, u.role as author_role, u.avatar_color as author_color
       FROM task_comments c
       LEFT JOIN users u ON c.user_id = u.id
       WHERE c.task_id = ?
       ORDER BY c.created_at ASC`,
      [taskId]
    );

    res.json(comments);
  } catch (err) {
    console.error('Get comments error:', err);
    res.status(500).json({ error: 'Failed to fetch comments.' });
  }
}

async function uploadAttachments(req, res) {
  try {
    const taskId = parseInt(req.params.id, 10);
    const task = await db.get('SELECT * FROM tasks WHERE id = ?', [taskId]);
    if (!task) {
      return res.status(404).json({ error: 'Task not found.' });
    }

    if (req.user.role === 'staff' && task.assigned_to !== req.user.id) {
      return res.status(403).json({ error: 'You can only upload files to tasks assigned to you.' });
    }

    if (!req.files || req.files.length === 0) {
      return res.status(400).json({ error: 'No files provided for upload.' });
    }

    const insertedFiles = [];
    for (const file of req.files) {
      const result = await db.run(
        `INSERT INTO task_attachments (task_id, uploaded_by, filename, original_name, mime_type, size)
         VALUES (?, ?, ?, ?, ?, ?)`,
        [taskId, req.user.id, file.filename, file.originalname, file.mimetype, file.size]
      );
      const inserted = await db.get(
        `SELECT a.*, u.name as uploader_name 
         FROM task_attachments a 
         LEFT JOIN users u ON a.uploaded_by = u.id 
         WHERE a.id = ?`,
        [result.insertId]
      );
      insertedFiles.push(inserted);
    }

    await logActivity(req.user.id, 'TASK_FILE_UPLOADED', `Uploaded ${req.files.length} file(s) to task #${taskId}`);

    res.status(201).json({
      message: `${req.files.length} file(s) uploaded successfully.`,
      attachments: insertedFiles
    });
  } catch (err) {
    console.error('Upload attachments error:', err);
    res.status(500).json({ error: 'Failed to upload attachments.' });
  }
}

async function deleteAttachment(req, res) {
  try {
    const attachmentId = parseInt(req.params.attachmentId, 10);
    const attachment = await db.get('SELECT * FROM task_attachments WHERE id = ?', [attachmentId]);

    if (!attachment) {
      return res.status(404).json({ error: 'Attachment not found.' });
    }

    if (req.user.role === 'staff' && attachment.uploaded_by !== req.user.id) {
      return res.status(403).json({ error: 'You can only delete attachments uploaded by yourself.' });
    }

    const filePath = path.join(__dirname, '..', '..', 'uploads', attachment.filename);
    if (fs.existsSync(filePath)) {
      try { fs.unlinkSync(filePath); } catch (e) {}
    }

    await db.run('DELETE FROM task_attachments WHERE id = ?', [attachmentId]);

    await logActivity(req.user.id, 'TASK_FILE_DELETED', `Deleted attachment "${attachment.original_name}" from task #${attachment.task_id}`);

    res.json({ message: 'Attachment deleted successfully.' });
  } catch (err) {
    console.error('Delete attachment error:', err);
    res.status(500).json({ error: 'Failed to delete attachment.' });
  }
}

async function downloadAttachment(req, res) {
  try {
    const attachmentId = parseInt(req.params.attachmentId, 10);
    const attachment = await db.get(
      `SELECT a.*, t.assigned_to, t.created_by 
       FROM task_attachments a
       JOIN tasks t ON a.task_id = t.id
       WHERE a.id = ?`,
      [attachmentId]
    );

    if (!attachment) {
      return res.status(404).json({ error: 'Attachment not found.' });
    }

    if (req.user.role === 'staff' && attachment.assigned_to !== req.user.id && attachment.created_by !== req.user.id) {
      return res.status(403).json({ error: 'Permission denied to view this attachment.' });
    }

    const filePath = path.join(__dirname, '..', '..', 'uploads', attachment.filename);
    if (!fs.existsSync(filePath)) {
      return res.status(404).json({ error: 'Physical file not found on server.' });
    }

    res.setHeader('Content-Disposition', `inline; filename="${encodeURIComponent(attachment.original_name)}"`);
    res.setHeader('Content-Type', attachment.mime_type);
    fs.createReadStream(filePath).pipe(res);
  } catch (err) {
    console.error('Download attachment error:', err);
    res.status(500).json({ error: 'Failed to stream attachment.' });
  }
}

module.exports = {
  getAllTasks,
  getTaskById,
  createTask,
  updateTask,
  updateTaskStatus,
  updateTaskAssignee,
  deleteTask,
  addComment,
  getComments,
  uploadAttachments,
  deleteAttachment,
  downloadAttachment
};
