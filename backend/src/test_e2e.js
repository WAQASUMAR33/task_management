const http = require('http');
const path = require('path');
const fs = require('fs');

const BASE_URL = 'http://localhost:5000/api';

async function request(endpoint, options = {}) {
  const url = `${BASE_URL}${endpoint}`;
  const headers = options.headers || {};
  let body = options.body;

  if (body && typeof body === 'object' && !(body instanceof Buffer)) {
    headers['Content-Type'] = 'application/json';
    body = JSON.stringify(body);
  }

  const res = await fetch(url, {
    method: options.method || 'GET',
    headers,
    body
  });

  const text = await res.text();
  let data;
  try {
    data = JSON.parse(text);
  } catch (e) {
    data = text;
  }

  return { status: res.status, data };
}

async function runTests() {
  console.log('🧪 Starting Comprehensive RBAC & Core API Test Suite...\n');
  let passed = 0;
  let failed = 0;

  function assert(condition, message) {
    if (condition) {
      console.log(`  ✅ PASS: ${message}`);
      passed++;
    } else {
      console.error(`  ❌ FAIL: ${message}`);
      failed++;
    }
  }

  try {
    // 1. Health check
    console.log('1. Health Check');
    const health = await request('/health');
    assert(health.status === 200 && health.data.status === 'online', 'Health check returns online');

    // 2. Authentication: Super Admin
    console.log('\n2. Authentication (Super Admin)');
    const saLogin = await request('/auth/login', {
      method: 'POST',
      body: { email: 'superadmin@apextask.com', password: 'Password123!' }
    });
    assert(saLogin.status === 200 && saLogin.data.token, 'Super admin login successful with JWT');
    const saToken = saLogin.data.token;
    const saHeaders = { 'Authorization': `Bearer ${saToken}` };

    // 3. Super Admin Dashboard Stats
    console.log('\n3. Dashboard Metrics (Super Admin)');
    const saStats = await request('/dashboard/stats', { headers: saHeaders });
    assert(saStats.status === 200 && saStats.data.totalTasks >= 7, `Dashboard returns ${saStats.data.totalTasks} tasks and metrics`);
    assert(saStats.data.staffWorkload.length > 0, 'Staff workload table populated for Super Admin');

    // 4. Super Admin Task Management
    console.log('\n4. Task Management (Super Admin)');
    const allTasks = await request('/tasks', { headers: saHeaders });
    assert(allTasks.status === 200 && Array.isArray(allTasks.data) && allTasks.data.length >= 7, 'All organization tasks fetched');

    // Task Creation
    const newTask = await request('/tasks', {
      method: 'POST',
      headers: saHeaders,
      body: {
        title: 'Automated E2E Test Task',
        description: 'Verifying end-to-end task flow with attachments and comments.',
        priority: 'high',
        status: 'todo'
      }
    });
    assert(newTask.status === 201 && newTask.data.task.id, `Task created with ID #${newTask.data.task.id}`);
    const createdTaskId = newTask.data.task.id;

    // 5. Staff Authentication & RBAC Boundaries
    console.log('\n5. Staff Authentication & RBAC Isolation');
    const staffLogin = await request('/auth/login', {
      method: 'POST',
      body: { email: 'john.doe@apextask.com', password: 'Password123!' }
    });
    assert(staffLogin.status === 200 && staffLogin.data.user.role === 'staff', 'Staff user logged in');
    const staffToken = staffLogin.data.token;
    const staffHeaders = { 'Authorization': `Bearer ${staffToken}` };

    // Staff Task Listing: Should only see assigned/created tasks
    const staffTasks = await request('/tasks', { headers: staffHeaders });
    assert(staffTasks.status === 200 && staffTasks.data.length < allTasks.data.length, `Staff task view is isolated (sees ${staffTasks.data.length} tasks vs all ${allTasks.data.length})`);

    // Staff Forbidden Action: Staff CANNOT create new tasks directly
    const staffCreate = await request('/tasks', {
      method: 'POST',
      headers: staffHeaders,
      body: { title: 'Illegal Staff Task' }
    });
    assert(staffCreate.status === 403, 'Staff cannot create tasks directly (HTTP 403 Forbidden)');

    // Staff Forbidden Action: Staff CANNOT delete tasks
    const staffDelete = await request(`/tasks/${createdTaskId}`, {
      method: 'DELETE',
      headers: staffHeaders
    });
    assert(staffDelete.status === 403, 'Staff cannot delete tasks (HTTP 403 Forbidden)');

    // Staff Forbidden Action: Staff CANNOT access user management
    const staffUsers = await request('/users', { headers: staffHeaders });
    assert(staffUsers.status === 403, 'Staff cannot access User Management endpoint (HTTP 403 Forbidden)');

    // 6. Assigning Task to Staff & Staff Permitted Workflow
    console.log('\n6. Task Reassignment & Staff Workflow (Status & Comments)');
    const reassign = await request(`/tasks/${createdTaskId}/assignee`, {
      method: 'PATCH',
      headers: saHeaders,
      body: { assigned_to: staffLogin.data.user.id }
    });
    assert(reassign.status === 200 && reassign.data.assigned_to === staffLogin.data.user.id, 'Super Admin assigned task to John Doe');

    // Now Staff can update status of assigned task
    const updateStatus = await request(`/tasks/${createdTaskId}/status`, {
      method: 'PATCH',
      headers: staffHeaders,
      body: { status: 'in_progress' }
    });
    assert(updateStatus.status === 200 && updateStatus.data.status === 'in_progress', 'Staff updated assigned task status to in_progress');

    // Staff adds comment
    const addComment = await request(`/tasks/${createdTaskId}/comments`, {
      method: 'POST',
      headers: staffHeaders,
      body: { comment: 'Started working on deliverables. Setting up staging container.' }
    });
    assert(addComment.status === 201 && addComment.data.comment.includes('Started working'), 'Staff posted comment to assigned task');

    // 7. Admin Role & Access Restrictions
    console.log('\n7. Admin Role & User Management Boundaries');
    const adminLogin = await request('/auth/login', {
      method: 'POST',
      body: { email: 'admin@apextask.com', password: 'Password123!' }
    });
    assert(adminLogin.status === 200 && adminLogin.data.user.role === 'admin', 'Admin logged in');
    const adminToken = adminLogin.data.token;
    const adminHeaders = { 'Authorization': `Bearer ${adminToken}` };

    // Admin CAN create Staff member
    const newStaff = await request('/users', {
      method: 'POST',
      headers: adminHeaders,
      body: {
        name: 'Carlos Rivera',
        email: `carlos.rivera.${Date.now()}@apextask.com`,
        password: 'Password123!',
        role: 'staff',
        department: 'Mobile Development'
      }
    });
    assert(newStaff.status === 201 && newStaff.data.user.id, `Admin successfully created new Staff member: ${newStaff.data?.user?.name}`);

    // Admin CANNOT create Super Admin or another Admin
    const adminCreateSA = await request('/users', {
      method: 'POST',
      headers: adminHeaders,
      body: {
        name: 'Rogue Admin',
        email: 'rogue@apextask.com',
        password: 'Password123!',
        role: 'super_admin'
      }
    });
    assert(adminCreateSA.status === 403, 'Admin CANNOT create Super Admin (HTTP 403 Forbidden)');

    // 8. Super Admin System Settings
    console.log('\n8. System Settings (Super Admin Only)');
    const updateSettings = await request('/settings', {
      method: 'PUT',
      headers: saHeaders,
      body: { app_name: 'ApexTask Pro Enterprise' }
    });
    assert(updateSettings.status === 200, 'Super Admin updated global system settings');

    // Staff/Admin CANNOT update system settings
    const staffSettings = await request('/settings', {
      method: 'PUT',
      headers: adminHeaders,
      body: { app_name: 'Hacked Name' }
    });
    assert(staffSettings.status === 403, 'Admin CANNOT update system settings (HTTP 403 Forbidden)');

    // 9. Cleanup test task
    console.log('\n9. Cleanup & Auditing');
    const deleteTask = await request(`/tasks/${createdTaskId}`, {
      method: 'DELETE',
      headers: saHeaders
    });
    assert(deleteTask.status === 200, 'Super Admin deleted test task');

    console.log(`\n========================================`);
    console.log(`🏁 Test Summary: ${passed} Passed, ${failed} Failed`);
    console.log(`========================================\n`);

  } catch (err) {
    console.error('Test Suite encountered unhandled error:', err);
  }
}

runTests();
