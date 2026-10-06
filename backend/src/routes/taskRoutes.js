const express = require('express');
const router = express.Router();
const taskController = require('../controllers/taskController');
const { authenticate } = require('../middleware/auth');
const upload = require('../middleware/upload');

router.use(authenticate);

// Task CRUD
router.get('/', taskController.getAllTasks);
router.get('/:id', taskController.getTaskById);
router.post('/', upload.array('attachments', 10), taskController.createTask);
router.put('/:id', upload.array('attachments', 10), taskController.updateTask);
router.patch('/:id/status', taskController.updateTaskStatus);
router.patch('/:id/assignee', taskController.updateTaskAssignee);
router.delete('/:id', taskController.deleteTask);

// Comments
router.get('/:id/comments', taskController.getComments);
router.post('/:id/comments', taskController.addComment);

// Attachments
router.post('/:id/attachments', upload.array('attachments', 10), taskController.uploadAttachments);
router.delete('/attachments/:attachmentId', taskController.deleteAttachment);
router.get('/attachments/:attachmentId/download', taskController.downloadAttachment);

module.exports = router;
