const mysql = require('mysql2/promise');
const path = require('path');
const fs = require('fs');

const envPath = fs.existsSync(path.join(__dirname, '..', '.env')) 
  ? path.join(__dirname, '..', '.env') 
  : path.join(__dirname, '..', '..', '.env');
require('dotenv').config({ path: envPath });

const pool = mysql.createPool({
  host: process.env.DB_HOST || '127.0.0.1',
  port: parseInt(process.env.DB_PORT || '3306', 10),
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'task_management',
  waitForConnections: true,
  connectionLimit: 15,
  queueLimit: 0,
  connectTimeout: 15000,
  timezone: '+00:00',
  dateStrings: true
});

async function query(sql, params = []) {
  const [results] = await pool.query(sql, params);
  return results;
}

async function get(sql, params = []) {
  const [results] = await pool.query(sql, params);
  return results && results.length > 0 ? results[0] : null;
}

async function all(sql, params = []) {
  const [results] = await pool.query(sql, params);
  return results;
}

async function run(sql, params = []) {
  const [result] = await pool.query(sql, params);
  return {
    insertId: result.insertId,
    affectedRows: result.affectedRows
  };
}

async function initDatabase() {
  try {
    const isLocal = process.env.DB_HOST === '127.0.0.1' || process.env.DB_HOST === 'localhost';

    // On local machines, optionally attempt to create the database if permissions allow
    if (isLocal) {
      try {
        const rootConn = await mysql.createConnection({
          host: process.env.DB_HOST,
          port: parseInt(process.env.DB_PORT || '3306', 10),
          user: process.env.DB_USER || 'root',
          password: process.env.DB_PASSWORD || ''
        });
        await rootConn.query(`CREATE DATABASE IF NOT EXISTS \`${process.env.DB_NAME || 'task_management'}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`);
        await rootConn.end();
      } catch (ignored) {
        // User may already be restricted to existing DB
      }
    }

    // Verify pool connection
    const testConn = await pool.getConnection();
    testConn.release();

    // Create Tables if not exist
    await pool.query(`
      CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('super_admin', 'admin', 'staff') NOT NULL DEFAULT 'staff',
        department VARCHAR(255) DEFAULT 'General',
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        avatar_color VARCHAR(32) DEFAULT '#4f46e5',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    await pool.query(`
      CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        assigned_to INT NULL,
        due_date DATE NULL,
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        status ENUM('todo', 'in_progress', 'completed') DEFAULT 'todo',
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_assigned (assigned_to),
        INDEX idx_status (status),
        INDEX idx_priority (priority),
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    await pool.query(`
      CREATE TABLE IF NOT EXISTS task_attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        uploaded_by INT NULL,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        mime_type VARCHAR(128) NOT NULL,
        size INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_attachment_task (task_id),
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    await pool.query(`
      CREATE TABLE IF NOT EXISTS task_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        user_id INT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_comment_task (task_id),
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    await pool.query(`
      CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(64) NOT NULL,
        details TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_log_user (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    await pool.query(`
      CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(128) PRIMARY KEY,
        setting_value TEXT NOT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    console.log(`✅ Connected to MySQL Server at ${process.env.DB_HOST}:${process.env.DB_PORT} (Database: ${process.env.DB_NAME})`);
  } catch (err) {
    console.error(`❌ Failed to connect to MySQL at ${process.env.DB_HOST}:${process.env.DB_PORT}:`, err.message);
  }
}

// Initialize tables on startup
initDatabase();

module.exports = {
  pool,
  query,
  get,
  all,
  run,
  initDatabase
};
