-- Database schema for travel-booking (MySQL/MariaDB)

-- Drop existing tables (respect FK order)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS ads;
DROP TABLE IF EXISTS transfers;
DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS flights;
DROP TABLE IF EXISTS hotels;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
SET FOREIGN_KEY_CHECKS = 1;

-- Roles
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
);

-- Users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Hotels
CREATE TABLE hotels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  city VARCHAR(100) NOT NULL,
  country VARCHAR(100) NOT NULL,
  price_per_night DECIMAL(10,2) NOT NULL,
  rating DECIMAL(3,2) DEFAULT 0,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Flights
CREATE TABLE flights (
  id INT AUTO_INCREMENT PRIMARY KEY,
  airline VARCHAR(100) NOT NULL,
  origin VARCHAR(100) NOT NULL,
  destination VARCHAR(100) NOT NULL,
  depart_date DATE NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Activities
CREATE TABLE activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  city VARCHAR(100) NOT NULL,
  date DATE NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Transfers (land)
CREATE TABLE transfers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service VARCHAR(255) NOT NULL,
  origin VARCHAR(100) NOT NULL,
  destination VARCHAR(100) NOT NULL,
  date DATE NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Bookings (polymorphic item reference via type+id for simplicity)
CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  item_type ENUM('hotel','flight','activity','transfer') NOT NULL,
  item_id INT NOT NULL,
  booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  total_price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Basic Ads
CREATE TABLE ads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  placement ENUM('home','listing','sidebar') NOT NULL,
  title VARCHAR(255) NOT NULL,
  image_url VARCHAR(500) NULL,
  link_url VARCHAR(500) NULL,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);




