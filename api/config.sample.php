<?php
declare(strict_types=1);

// Database Configuration
const DB_SERVER = 'YOURPC\\SQLEXPRESS';
const DB_DATABASE = 'booking-system';
const DB_UID = 'your_sql_login';
const DB_PWD = 'your_password';

// JWT Configuration
const JWT_SECRET = 'your-secret-key-change-this-in-production';
const JWT_EXPIRY = 86400; // 24 hours in seconds

// Upload Configuration
const UPLOAD_MAX_SIZE = 5242880; // 5MB in bytes
const UPLOAD_ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const UPLOAD_CATEGORIES = ['general', 'hotel', 'flight', 'activity', 'transfer', 'destination'];

// Environment
const ENVIRONMENT = 'development'; // 'development' or 'production'

// CORS Configuration (will be used in Phase 3)
const CORS_ALLOWED_ORIGINS = ['http://localhost', 'http://localhost/travel-booking'];

