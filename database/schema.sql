-- SQL Server schema for booking-system
-- Idempotent drop
IF OBJECT_ID('dbo.bookings', 'U') IS NOT NULL DROP TABLE dbo.bookings;
IF OBJECT_ID('dbo.ads', 'U') IS NOT NULL DROP TABLE dbo.ads;
IF OBJECT_ID('dbo.transfers', 'U') IS NOT NULL DROP TABLE dbo.transfers;
IF OBJECT_ID('dbo.activities', 'U') IS NOT NULL DROP TABLE dbo.activities;
IF OBJECT_ID('dbo.flights', 'U') IS NOT NULL DROP TABLE dbo.flights;
IF OBJECT_ID('dbo.hotels', 'U') IS NOT NULL DROP TABLE dbo.hotels;
IF OBJECT_ID('dbo.users', 'U') IS NOT NULL DROP TABLE dbo.users;
IF OBJECT_ID('dbo.roles', 'U') IS NOT NULL DROP TABLE dbo.roles;

-- Roles
CREATE TABLE dbo.roles (
  id INT IDENTITY(1,1) PRIMARY KEY,
  name NVARCHAR(50) NOT NULL UNIQUE
);

-- Users
CREATE TABLE dbo.users (
  id INT IDENTITY(1,1) PRIMARY KEY,
  email NVARCHAR(255) NOT NULL UNIQUE,
  password_hash NVARCHAR(255) NOT NULL,
  full_name NVARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  user_profile_image NVARCHAR(500) NULL,
  phone NVARCHAR(50) NULL,
  address NVARCHAR(500) NULL,
  bio NVARCHAR(1000) NULL,
  onboarding_completed BIT DEFAULT 0,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_users_roles FOREIGN KEY (role_id) REFERENCES dbo.roles(id)
);

-- Hotels
CREATE TABLE dbo.hotels (
  id INT IDENTITY(1,1) PRIMARY KEY,
  name NVARCHAR(255) NOT NULL,
  city NVARCHAR(100) NOT NULL,
  country NVARCHAR(100) NOT NULL,
  price_per_night DECIMAL(10,2) NOT NULL,
  rating DECIMAL(3,2) DEFAULT 0,
  description NVARCHAR(MAX) NULL,
  image_url NVARCHAR(500) NULL,
  booking_count INT DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_hotels_users FOREIGN KEY (created_by) REFERENCES dbo.users(id)
);

-- Flights
CREATE TABLE dbo.flights (
  id INT IDENTITY(1,1) PRIMARY KEY,
  airline NVARCHAR(100) NOT NULL,
  origin NVARCHAR(100) NOT NULL,
  destination NVARCHAR(100) NOT NULL,
  depart_date DATE NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  description NVARCHAR(MAX) NULL,
  image_url NVARCHAR(500) NULL,
  booking_count INT DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_flights_users FOREIGN KEY (created_by) REFERENCES dbo.users(id)
);

-- Activities
CREATE TABLE dbo.activities (
  id INT IDENTITY(1,1) PRIMARY KEY,
  title NVARCHAR(255) NOT NULL,
  city NVARCHAR(100) NOT NULL,
  date DATE NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  description NVARCHAR(MAX) NULL,
  image_url NVARCHAR(500) NULL,
  booking_count INT DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_activities_users FOREIGN KEY (created_by) REFERENCES dbo.users(id)
);

-- Transfers
CREATE TABLE dbo.transfers (
  id INT IDENTITY(1,1) PRIMARY KEY,
  service NVARCHAR(255) NOT NULL,
  origin NVARCHAR(100) NOT NULL,
  destination NVARCHAR(100) NOT NULL,
  date DATE NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  description NVARCHAR(MAX) NULL,
  image_url NVARCHAR(500) NULL,
  booking_count INT DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_transfers_users FOREIGN KEY (created_by) REFERENCES dbo.users(id)
);

-- Bookings
CREATE TABLE dbo.bookings (
  id INT IDENTITY(1,1) PRIMARY KEY,
  user_id INT NOT NULL,
  item_type NVARCHAR(16) NOT NULL CHECK (item_type IN ('hotel','flight','activity','transfer')),
  item_id INT NOT NULL,
  booked_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  total_price DECIMAL(10,2) NOT NULL,
  CONSTRAINT FK_bookings_users FOREIGN KEY (user_id) REFERENCES dbo.users(id)
);

-- Ads
CREATE TABLE dbo.ads (
  id INT IDENTITY(1,1) PRIMARY KEY,
  placement NVARCHAR(16) NOT NULL CHECK (placement IN ('home','listing','sidebar')),
  title NVARCHAR(255) NOT NULL,
  image_url NVARCHAR(500) NULL,
  link_url NVARCHAR(500) NULL,
  active BIT DEFAULT 1,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME()
);

-- Indexes
CREATE INDEX IX_hotels_city ON dbo.hotels(city);
CREATE INDEX IX_hotels_country ON dbo.hotels(country);
CREATE INDEX IX_hotels_price ON dbo.hotels(price_per_night);
CREATE INDEX IX_hotels_rating ON dbo.hotels(rating);
CREATE INDEX IX_flights_depart ON dbo.flights(depart_date);
CREATE INDEX IX_activities_date ON dbo.activities(date);
CREATE INDEX IX_transfers_date ON dbo.transfers(date);