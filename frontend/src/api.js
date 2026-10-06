const API_BASE_URL = '/api';

export async function apiRequest(endpoint, options = {}) {
  const token = localStorage.getItem('apextask_token');
  
  const headers = {
    ...(options.headers || {})
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  // If body is NOT FormData, default to application/json
  if (options.body && !(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(options.body);
  }

  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    ...options,
    headers
  });

  if (response.status === 401) {
    // Unauthorized / Expired token
    localStorage.removeItem('apextask_token');
    localStorage.removeItem('apextask_user');
    window.dispatchEvent(new Event('auth:unauthorized'));
  }

  const data = await response.json().catch(() => null);

  if (!response.ok) {
    const errorMsg = (data && data.error) || response.statusText || 'An error occurred';
    throw new Error(errorMsg);
  }

  return data;
}

export const authApi = {
  login: (credentials) => apiRequest('/auth/login', { method: 'POST', body: credentials }),
  getMe: () => apiRequest('/auth/me'),
  updateProfile: (profile) => apiRequest('/auth/profile', { method: 'PUT', body: profile }),
  changePassword: (data) => apiRequest('/auth/change-password', { method: 'POST', body: data })
};

export const userApi = {
  getAll: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return apiRequest(`/users${query ? `?${query}` : ''}`);
  },
  getById: (id) => apiRequest(`/users/${id}`),
  create: (userData) => apiRequest('/users', { method: 'POST', body: userData }),
  update: (id, userData) => apiRequest(`/users/${id}`, { method: 'PUT', body: userData }),
  toggleStatus: (id) => apiRequest(`/users/${id}/toggle-status`, { method: 'PATCH' }),
  resetPassword: (id, newPassword) => apiRequest(`/users/${id}/reset-password`, { method: 'POST', body: { newPassword } }),
  delete: (id) => apiRequest(`/users/${id}`, { method: 'DELETE' })
};

export const taskApi = {
  getAll: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return apiRequest(`/tasks${query ? `?${query}` : ''}`);
  },
  getById: (id) => apiRequest(`/tasks/${id}`),
  create: (formData) => apiRequest('/tasks', { method: 'POST', body: formData }),
  update: (id, formData) => apiRequest(`/tasks/${id}`, { method: 'PUT', body: formData }),
  updateStatus: (id, status) => apiRequest(`/tasks/${id}/status`, { method: 'PATCH', body: { status } }),
  updateAssignee: (id, assigned_to) => apiRequest(`/tasks/${id}/assignee`, { method: 'PATCH', body: { assigned_to } }),
  delete: (id) => apiRequest(`/tasks/${id}`, { method: 'DELETE' }),
  getComments: (id) => apiRequest(`/tasks/${id}/comments`),
  addComment: (id, comment) => apiRequest(`/tasks/${id}/comments`, { method: 'POST', body: { comment } }),
  uploadAttachments: (id, formData) => apiRequest(`/tasks/${id}/attachments`, { method: 'POST', body: formData }),
  deleteAttachment: (attachmentId) => apiRequest(`/tasks/attachments/${attachmentId}`, { method: 'DELETE' }),
  getDownloadUrl: (attachmentId) => `/api/tasks/attachments/${attachmentId}/download`
};

export const dashboardApi = {
  getStats: () => apiRequest('/dashboard/stats')
};

export const settingsApi = {
  get: () => apiRequest('/settings'),
  update: (data) => apiRequest('/settings', { method: 'PUT', body: data })
};
