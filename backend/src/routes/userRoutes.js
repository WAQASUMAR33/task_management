const express = require('express');
const router = express.Router();
const userController = require('../controllers/userController');
const { authenticate, requireRoles } = require('../middleware/auth');

// All user management routes require login and Super Admin / Admin role
router.use(authenticate);
router.use(requireRoles('super_admin', 'admin'));

router.get('/', userController.getAllUsers);
router.get('/:id', userController.getUserById);
router.post('/', userController.createUser);
router.put('/:id', userController.updateUser);
router.patch('/:id/toggle-status', userController.toggleUserStatus);
router.post('/:id/reset-password', userController.resetUserPassword);
router.delete('/:id', userController.deleteUser);

module.exports = router;
