const express = require('express');
const cors = require('cors');
const path = require('path');
const fs = require('fs');

// Load environment variables reliably from backend/.env or root .env
const envPath = fs.existsSync(path.join(__dirname, '..', '.env')) 
  ? path.join(__dirname, '..', '.env') 
  : path.join(__dirname, '..', '..', '.env');
require('dotenv').config({ path: envPath });

// Initialize DB schema
require('./config/db');

const authRoutes = require('./routes/authRoutes');
const userRoutes = require('./routes/userRoutes');
const taskRoutes = require('./routes/taskRoutes');
const dashboardRoutes = require('./routes/dashboardRoutes');
const settingsRoutes = require('./routes/settingsRoutes');

const app = express();
const PORT = process.env.PORT || 5000;

// Middleware
app.use(cors({
  origin: '*',
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
  allowedHeaders: ['Content-Type', 'Authorization']
}));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static uploads
const uploadsDir = path.join(__dirname, '..', 'uploads');
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true });
}
app.use('/uploads', express.static(uploadsDir));

// API Routes
app.use('/api/auth', authRoutes);
app.use('/api/users', userRoutes);
app.use('/api/tasks', taskRoutes);
app.use('/api/dashboard', dashboardRoutes);
app.use('/api/settings', settingsRoutes);

// Health check endpoint
app.get('/api/health', (req, res) => {
  res.json({
    status: 'online',
    timestamp: new Date().toISOString(),
    service: 'ApexTask Unified Full-Stack Application'
  });
});

// Serve Frontend Static Files (Unified Single Codebase Architecture)
// Checks backend/public first, then falls back to ../frontend/dist
const publicDir = path.join(__dirname, '..', 'public');
const frontendDistDir = path.join(__dirname, '..', '..', 'frontend', 'dist');

const staticDir = fs.existsSync(publicDir) ? publicDir : (fs.existsSync(frontendDistDir) ? frontendDistDir : null);

if (staticDir) {
  console.log(`📦 Serving React frontend static assets from: ${staticDir}`);
  app.use(express.static(staticDir));

  // SPA fallback middleware: handles any client-side routes (Express 4 & 5 compatible)
  app.use((req, res, next) => {
    if ((req.method === 'GET' || req.method === 'HEAD') && !req.path.startsWith('/api') && !req.path.startsWith('/uploads')) {
      return res.sendFile(path.join(staticDir, 'index.html'));
    }
    next();
  });
}

// Centralized error handler
app.use((err, req, res, next) => {
  console.error('API Error:', err);

  if (err.name === 'MulterError') {
    if (err.code === 'LIMIT_FILE_SIZE') {
      return res.status(400).json({ error: 'File size exceeds maximum limit of 10MB.' });
    }
    return res.status(400).json({ error: `File upload error: ${err.message}` });
  }

  res.status(err.status || 500).json({
    error: err.message || 'Internal Server Error'
  });
});

app.listen(PORT, () => {
  console.log(`🚀 ApexTask Unified Server running on http://localhost:${PORT}`);
});
