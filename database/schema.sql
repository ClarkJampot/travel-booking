-- SQL Server schema for travel booking system
-- Idempotent drop (drop in reverse order of dependencies)

IF OBJECT_ID('dbo.entity_images', 'U') IS NOT NULL DROP TABLE dbo.entity_images;
IF OBJECT_ID('dbo.bookings', 'U') IS NOT NULL DROP TABLE dbo.bookings;
IF OBJECT_ID('dbo.jwt_tokens', 'U') IS NOT NULL DROP TABLE dbo.jwt_tokens;
IF OBJECT_ID('dbo.transfers', 'U') IS NOT NULL DROP TABLE dbo.transfers;
IF OBJECT_ID('dbo.activities', 'U') IS NOT NULL DROP TABLE dbo.activities;
IF OBJECT_ID('dbo.flights', 'U') IS NOT NULL DROP TABLE dbo.flights;
IF OBJECT_ID('dbo.hotels', 'U') IS NOT NULL DROP TABLE dbo.hotels;
IF OBJECT_ID('dbo.destinations', 'U') IS NOT NULL DROP TABLE dbo.destinations;
IF OBJECT_ID('dbo.cities', 'U') IS NOT NULL DROP TABLE dbo.cities;
IF OBJECT_ID('dbo.users', 'U') IS NOT NULL DROP TABLE dbo.users;
IF OBJECT_ID('dbo.provinces', 'U') IS NOT NULL DROP TABLE dbo.provinces;
IF OBJECT_ID('dbo.roles', 'U') IS NOT NULL DROP TABLE dbo.roles;

-- Roles table
CREATE TABLE dbo.roles (
  id INT IDENTITY(1,1) PRIMARY KEY,
  name NVARCHAR(50) NOT NULL UNIQUE
);

-- Users table
CREATE TABLE dbo.users (
  id INT IDENTITY(1,1) PRIMARY KEY,
  email NVARCHAR(255) NOT NULL UNIQUE,
  password_hash NVARCHAR(255) NOT NULL,
  full_name NVARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  phone NVARCHAR(50) NULL,
  address NVARCHAR(500) NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_users_roles FOREIGN KEY (role_id) REFERENCES dbo.roles(id)
);

-- JWT Tokens table for token-based authentication
CREATE TABLE dbo.jwt_tokens (
  id INT IDENTITY(1,1) PRIMARY KEY,
  user_id INT NOT NULL,
  token NVARCHAR(500) NOT NULL,
  expires_at DATETIME2 NOT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_jwt_tokens_users FOREIGN KEY (user_id) REFERENCES dbo.users(id) ON DELETE CASCADE
);

-- Provinces table (Philippine provinces)
CREATE TABLE dbo.provinces (
  id INT IDENTITY(1,1) PRIMARY KEY,
  name NVARCHAR(100) NOT NULL UNIQUE,
  code NVARCHAR(10) NULL,
  region NVARCHAR(100) NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME()
);

-- Cities table (linked to provinces)
CREATE TABLE dbo.cities (
  id INT IDENTITY(1,1) PRIMARY KEY,
  name NVARCHAR(100) NOT NULL,
  province_id INT NOT NULL,
  is_capital BIT DEFAULT 0,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_cities_provinces FOREIGN KEY (province_id) REFERENCES dbo.provinces(id),
  CONSTRAINT UQ_cities_name_province UNIQUE (name, province_id)
);

-- Destinations table (for destination pages)
CREATE TABLE dbo.destinations (
  id INT IDENTITY(1,1) PRIMARY KEY,
  name NVARCHAR(255) NOT NULL,
  description NVARCHAR(MAX) NULL,
  featured BIT DEFAULT 0,
  province_id INT NULL,
  city_id INT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_destinations_provinces FOREIGN KEY (province_id) REFERENCES dbo.provinces(id),
  CONSTRAINT FK_destinations_cities FOREIGN KEY (city_id) REFERENCES dbo.cities(id)
);

-- Hotels table (linked to destinations and cities)
CREATE TABLE dbo.hotels (
  id INT IDENTITY(1,1) PRIMARY KEY,
  name NVARCHAR(255) NOT NULL,
  destination_id INT NULL,
  city_id INT NULL,
  province_id INT NULL,
  price_per_night DECIMAL(10,2) NOT NULL,
  description NVARCHAR(MAX) NULL,
  booking_count INT DEFAULT 0,
  ad BIT DEFAULT 0,
  discount_percent DECIMAL(5,2) DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_hotels_destinations FOREIGN KEY (destination_id) REFERENCES dbo.destinations(id),
  CONSTRAINT FK_hotels_cities FOREIGN KEY (city_id) REFERENCES dbo.cities(id),
  CONSTRAINT FK_hotels_provinces FOREIGN KEY (province_id) REFERENCES dbo.provinces(id),
  CONSTRAINT FK_hotels_users FOREIGN KEY (created_by) REFERENCES dbo.users(id)
);

-- Flights table
CREATE TABLE dbo.flights (
  id INT IDENTITY(1,1) PRIMARY KEY,
  airline NVARCHAR(100) NOT NULL,
  origin NVARCHAR(100) NOT NULL,
  destination NVARCHAR(100) NOT NULL,
  origin_city_id INT NULL,
  destination_city_id INT NULL,
  depart_date DATE NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  description NVARCHAR(MAX) NULL,
  booking_count INT DEFAULT 0,
  ad BIT DEFAULT 0,
  discount_percent DECIMAL(5,2) DEFAULT 0,
  trip_type NVARCHAR(20) NULL CHECK (trip_type IN ('one-way', 'round-trip')),
  created_by INT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_flights_users FOREIGN KEY (created_by) REFERENCES dbo.users(id),
  CONSTRAINT FK_flights_origin_cities FOREIGN KEY (origin_city_id) REFERENCES dbo.cities(id),
  CONSTRAINT FK_flights_destination_cities FOREIGN KEY (destination_city_id) REFERENCES dbo.cities(id)
);

-- Activities table (linked to destinations and cities)
CREATE TABLE dbo.activities (
  id INT IDENTITY(1,1) PRIMARY KEY,
  title NVARCHAR(255) NOT NULL,
  destination_id INT NULL,
  city_id INT NULL,
  date DATE NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  description NVARCHAR(MAX) NULL,
  booking_count INT DEFAULT 0,
  ad BIT DEFAULT 0,
  discount_percent DECIMAL(5,2) DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_activities_destinations FOREIGN KEY (destination_id) REFERENCES dbo.destinations(id),
  CONSTRAINT FK_activities_cities FOREIGN KEY (city_id) REFERENCES dbo.cities(id),
  CONSTRAINT FK_activities_users FOREIGN KEY (created_by) REFERENCES dbo.users(id)
);

-- Transfers table
CREATE TABLE dbo.transfers (
  id INT IDENTITY(1,1) PRIMARY KEY,
  service NVARCHAR(255) NOT NULL,
  origin NVARCHAR(100) NOT NULL,
  destination NVARCHAR(100) NOT NULL,
  origin_city_id INT NULL,
  destination_city_id INT NULL,
  date DATE NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  description NVARCHAR(MAX) NULL,
  booking_count INT DEFAULT 0,
  ad BIT DEFAULT 0,
  discount_percent DECIMAL(5,2) DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_transfers_users FOREIGN KEY (created_by) REFERENCES dbo.users(id),
  CONSTRAINT FK_transfers_origin_cities FOREIGN KEY (origin_city_id) REFERENCES dbo.cities(id),
  CONSTRAINT FK_transfers_destination_cities FOREIGN KEY (destination_city_id) REFERENCES dbo.cities(id)
);

-- Bookings table
CREATE TABLE dbo.bookings (
  id INT IDENTITY(1,1) PRIMARY KEY,
  user_id INT NOT NULL,
  item_type NVARCHAR(16) NOT NULL CHECK (item_type IN ('hotel','flight','activity','transfer')),
  item_id INT NOT NULL,
  booked_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  total_price DECIMAL(10,2) NOT NULL,
  status NVARCHAR(20) DEFAULT 'confirmed' CHECK (status IN ('confirmed','cancelled','completed')),
  CONSTRAINT FK_bookings_users FOREIGN KEY (user_id) REFERENCES dbo.users(id)
);

-- Entity Images table (for multiple images per entity)
CREATE TABLE dbo.entity_images (
  id INT IDENTITY(1,1) PRIMARY KEY,
  entity_type NVARCHAR(20) NOT NULL CHECK (entity_type IN ('hotel','flight','activity','transfer','destination')),
  entity_id INT NOT NULL,
  image_url NVARCHAR(500) NOT NULL,
  display_order INT DEFAULT 0,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME()
);

-- Indexes for performance
CREATE INDEX IX_users_email ON dbo.users(email);
CREATE INDEX IX_jwt_tokens_user_id ON dbo.jwt_tokens(user_id);
CREATE INDEX IX_jwt_tokens_token ON dbo.jwt_tokens(token);
CREATE INDEX IX_jwt_tokens_expires_at ON dbo.jwt_tokens(expires_at);
CREATE INDEX IX_destinations_featured ON dbo.destinations(featured);
CREATE INDEX IX_provinces_name ON dbo.provinces(name);
CREATE INDEX IX_provinces_code ON dbo.provinces(code);
CREATE INDEX IX_cities_name ON dbo.cities(name);
CREATE INDEX IX_cities_province_id ON dbo.cities(province_id);
CREATE INDEX IX_hotels_destination_id ON dbo.hotels(destination_id);
CREATE INDEX IX_hotels_city_id ON dbo.hotels(city_id);
CREATE INDEX IX_hotels_province_id ON dbo.hotels(province_id);
CREATE INDEX IX_hotels_price ON dbo.hotels(price_per_night);
CREATE INDEX IX_activities_destination_id ON dbo.activities(destination_id);
CREATE INDEX IX_activities_city_id ON dbo.activities(city_id);
CREATE INDEX IX_activities_date ON dbo.activities(date);
CREATE INDEX IX_flights_depart ON dbo.flights(depart_date);
CREATE INDEX IX_flights_origin ON dbo.flights(origin);
CREATE INDEX IX_flights_destination ON dbo.flights(destination);
CREATE INDEX IX_flights_origin_city_id ON dbo.flights(origin_city_id);
CREATE INDEX IX_flights_destination_city_id ON dbo.flights(destination_city_id);
CREATE INDEX IX_transfers_origin_city_id ON dbo.transfers(origin_city_id);
CREATE INDEX IX_transfers_destination_city_id ON dbo.transfers(destination_city_id);
CREATE INDEX IX_destinations_province_id ON dbo.destinations(province_id);
CREATE INDEX IX_destinations_city_id ON dbo.destinations(city_id);
CREATE INDEX IX_transfers_date ON dbo.transfers(date);
CREATE INDEX IX_bookings_user_id ON dbo.bookings(user_id);
CREATE INDEX IX_bookings_item_type ON dbo.bookings(item_type);
CREATE INDEX IX_entity_images_entity ON dbo.entity_images(entity_type, entity_id);
CREATE INDEX IX_entity_images_display_order ON dbo.entity_images(entity_type, entity_id, display_order);
