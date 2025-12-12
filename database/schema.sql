-- SQL Server schema for travel booking system
-- Idempotent drop (drop in reverse order of dependencies)

IF OBJECT_ID('dbo.entity_images', 'U') IS NOT NULL DROP TABLE dbo.entity_images;
IF OBJECT_ID('dbo.transfer_instances', 'U') IS NOT NULL DROP TABLE dbo.transfer_instances;
IF OBJECT_ID('dbo.transfer_schedules', 'U') IS NOT NULL DROP TABLE dbo.transfer_schedules;
IF OBJECT_ID('dbo.transfer_routes', 'U') IS NOT NULL DROP TABLE dbo.transfer_routes;
IF OBJECT_ID('dbo.transfer_types', 'U') IS NOT NULL DROP TABLE dbo.transfer_types;
IF OBJECT_ID('dbo.flight_instances', 'U') IS NOT NULL DROP TABLE dbo.flight_instances;
IF OBJECT_ID('dbo.flight_schedules', 'U') IS NOT NULL DROP TABLE dbo.flight_schedules;
IF OBJECT_ID('dbo.flight_route_pairs', 'U') IS NOT NULL DROP TABLE dbo.flight_route_pairs;
IF OBJECT_ID('dbo.flight_routes', 'U') IS NOT NULL DROP TABLE dbo.flight_routes;
IF OBJECT_ID('dbo.airports', 'U') IS NOT NULL DROP TABLE dbo.airports;
IF OBJECT_ID('dbo.activity_bookings', 'U') IS NOT NULL DROP TABLE dbo.activity_bookings;
IF OBJECT_ID('dbo.transfer_bookings', 'U') IS NOT NULL DROP TABLE dbo.transfer_bookings;
IF OBJECT_ID('dbo.flight_bookings', 'U') IS NOT NULL DROP TABLE dbo.flight_bookings;
IF OBJECT_ID('dbo.hotel_bookings', 'U') IS NOT NULL DROP TABLE dbo.hotel_bookings;
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
  first_name NVARCHAR(50) NOT NULL,
  last_name NVARCHAR(50) NULL,
  role_id INT NOT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  deleted_at DATETIME2 NULL,
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

-- Bookings

CREATE TABLE dbo.hotel_bookings (
  id INT IDENTITY(1,1) PRIMARY KEY,
  user_id INT NOT NULL,
  hotel_id INT NOT NULL,
  check_in DATE NOT NULL,
  check_out DATE NOT NULL,
  guests INT NOT NULL DEFAULT 1,
  total_price DECIMAL(10,2) NOT NULL,
  booked_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  cancelled_at DATETIME2 NULL,
  status NVARCHAR(20) DEFAULT 'confirmed' CHECK (status IN ('confirmed','cancelled','completed')),
  CONSTRAINT FK_hotel_bookings_users FOREIGN KEY (user_id) REFERENCES dbo.users(id),
  CONSTRAINT FK_hotel_bookings_hotels FOREIGN KEY (hotel_id) REFERENCES dbo.hotels(id)
);

CREATE TABLE dbo.flight_bookings (
  id INT IDENTITY(1,1) PRIMARY KEY,
  user_id INT NOT NULL,
  flight_id INT NOT NULL,
  class NVARCHAR(20) NULL,
  passenger_count INT NOT NULL,
  passenger_details NVARCHAR(MAX) NULL,
  total_price DECIMAL(10,2) NOT NULL,
  booked_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  cancelled_at DATETIME2 NULL,
  status NVARCHAR(20) DEFAULT 'confirmed' CHECK (status IN ('confirmed','cancelled','completed')),
  CONSTRAINT FK_flight_bookings_users FOREIGN KEY (user_id) REFERENCES dbo.users(id),
  CONSTRAINT FK_flight_bookings_flights FOREIGN KEY (flight_id) REFERENCES dbo.flights(id)
);

CREATE TABLE dbo.transfer_bookings (
  id INT IDENTITY(1,1) PRIMARY KEY,
  user_id INT NOT NULL,
  transfer_id INT NOT NULL,
  passenger_count INT NOT NULL DEFAULT 1,
  passenger_details NVARCHAR(MAX) NULL,
  total_price DECIMAL(10,2) NOT NULL,
  booked_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  cancelled_at DATETIME2 NULL,
  status NVARCHAR(20) DEFAULT 'confirmed' CHECK (status IN ('confirmed','cancelled','completed')),
  CONSTRAINT FK_transfer_bookings_users FOREIGN KEY (user_id) REFERENCES dbo.users(id),
  CONSTRAINT FK_transfer_bookings_transfers FOREIGN KEY (transfer_id) REFERENCES dbo.transfers(id)
);


CREATE TABLE dbo.activity_bookings (
  id INT IDENTITY(1,1) PRIMARY KEY,
  user_id INT NOT NULL,
  activity_id INT NOT NULL,
  participant_count INT NOT NULL DEFAULT 1,
  total_price DECIMAL(10,2) NOT NULL,
  booked_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  cancelled_at DATETIME2 NULL,
  status NVARCHAR(20) DEFAULT 'confirmed' CHECK (status IN ('confirmed','cancelled','completed')),
  CONSTRAINT FK_activity_bookings_users FOREIGN KEY (user_id) REFERENCES dbo.users(id),
  CONSTRAINT FK_activity_bookings_activities FOREIGN KEY (activity_id) REFERENCES dbo.activities(id)
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
CREATE INDEX IX_hotel_bookings_user_id ON dbo.hotel_bookings(user_id);
CREATE INDEX IX_hotel_bookings_hotel_id ON dbo.hotel_bookings(hotel_id);
CREATE INDEX IX_hotel_bookings_status ON dbo.hotel_bookings(status);
CREATE INDEX IX_flight_bookings_user_id ON dbo.flight_bookings(user_id);
CREATE INDEX IX_flight_bookings_flight_id ON dbo.flight_bookings(flight_id);
CREATE INDEX IX_flight_bookings_status ON dbo.flight_bookings(status);
CREATE INDEX IX_transfer_bookings_user_id ON dbo.transfer_bookings(user_id);
CREATE INDEX IX_transfer_bookings_transfer_id ON dbo.transfer_bookings(transfer_id);
CREATE INDEX IX_transfer_bookings_status ON dbo.transfer_bookings(status);
CREATE INDEX IX_activity_bookings_user_id ON dbo.activity_bookings(user_id);
CREATE INDEX IX_activity_bookings_activity_id ON dbo.activity_bookings(activity_id);
CREATE INDEX IX_activity_bookings_status ON dbo.activity_bookings(status);
CREATE INDEX IX_entity_images_entity ON dbo.entity_images(entity_type, entity_id);
CREATE INDEX IX_entity_images_display_order ON dbo.entity_images(entity_type, entity_id, display_order);

-- ============================================================================
-- NEW FLIGHTS AND TRANSFERS ARCHITECTURE
-- ============================================================================

-- Airports table
CREATE TABLE dbo.airports (
  id INT IDENTITY(1,1) PRIMARY KEY,
  code NVARCHAR(10) NOT NULL UNIQUE,
  name NVARCHAR(255) NOT NULL,
  city_id INT NOT NULL,
  is_international BIT DEFAULT 0,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_airports_cities FOREIGN KEY (city_id) REFERENCES dbo.cities(id)
);

-- Flight Routes table
CREATE TABLE dbo.flight_routes (
  id INT IDENTITY(1,1) PRIMARY KEY,
  origin_airport_id INT NOT NULL,
  destination_airport_id INT NOT NULL,
  airline NVARCHAR(100) NOT NULL,
  base_price_economy DECIMAL(10,2) NOT NULL,
  base_price_business DECIMAL(10,2) NULL,
  base_price_first DECIMAL(10,2) NULL,
  duration_minutes INT NOT NULL,
  aircraft_type NVARCHAR(100) NULL,
  ad BIT DEFAULT 0,
  discount_percent DECIMAL(5,2) DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_flight_routes_origin_airports FOREIGN KEY (origin_airport_id) REFERENCES dbo.airports(id),
  CONSTRAINT FK_flight_routes_destination_airports FOREIGN KEY (destination_airport_id) REFERENCES dbo.airports(id),
  CONSTRAINT FK_flight_routes_users FOREIGN KEY (created_by) REFERENCES dbo.users(id),
  CONSTRAINT UQ_flight_routes_airline_route UNIQUE (origin_airport_id, destination_airport_id, airline)
);

-- Flight Route Pairs table (for round-trip)
CREATE TABLE dbo.flight_route_pairs (
  id INT IDENTITY(1,1) PRIMARY KEY,
  outbound_route_id INT NOT NULL,
  return_route_id INT NOT NULL,
  base_price_economy DECIMAL(10,2) NOT NULL,
  base_price_business DECIMAL(10,2) NULL,
  base_price_first DECIMAL(10,2) NULL,
  discount_percent DECIMAL(5,2) DEFAULT 0,
  ad BIT DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_flight_route_pairs_outbound FOREIGN KEY (outbound_route_id) REFERENCES dbo.flight_routes(id),
  CONSTRAINT FK_flight_route_pairs_return FOREIGN KEY (return_route_id) REFERENCES dbo.flight_routes(id),
  CONSTRAINT FK_flight_route_pairs_users FOREIGN KEY (created_by) REFERENCES dbo.users(id),
  CONSTRAINT CK_flight_route_pairs_different CHECK (outbound_route_id != return_route_id)
);

-- Flight Schedules table
CREATE TABLE dbo.flight_schedules (
  id INT IDENTITY(1,1) PRIMARY KEY,
  route_id INT NULL,
  route_pair_id INT NULL,
  departure_time TIME NOT NULL,
  days_of_week NVARCHAR(20) NOT NULL,
  is_active BIT DEFAULT 1,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_flight_schedules_routes FOREIGN KEY (route_id) REFERENCES dbo.flight_routes(id),
  CONSTRAINT FK_flight_schedules_route_pairs FOREIGN KEY (route_pair_id) REFERENCES dbo.flight_route_pairs(id),
  CONSTRAINT CK_flight_schedules_route_or_pair CHECK ((route_id IS NOT NULL AND route_pair_id IS NULL) OR (route_id IS NULL AND route_pair_id IS NOT NULL))
);

-- Flight Instances table
CREATE TABLE dbo.flight_instances (
  id INT IDENTITY(1,1) PRIMARY KEY,
  schedule_id INT NOT NULL,
  departure_date DATE NOT NULL,
  departure_datetime DATETIME2 NOT NULL,
  price_economy DECIMAL(10,2) NULL,
  price_business DECIMAL(10,2) NULL,
  price_first DECIMAL(10,2) NULL,
  seats_economy_total INT DEFAULT 0,
  seats_economy_available INT DEFAULT 0,
  seats_business_total INT DEFAULT 0,
  seats_business_available INT DEFAULT 0,
  seats_first_total INT DEFAULT 0,
  seats_first_available INT DEFAULT 0,
  status NVARCHAR(20) DEFAULT 'scheduled' CHECK (status IN ('scheduled', 'boarding', 'departed', 'cancelled', 'delayed')),
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_flight_instances_schedules FOREIGN KEY (schedule_id) REFERENCES dbo.flight_schedules(id),
  CONSTRAINT UQ_flight_instances_schedule_date UNIQUE (schedule_id, departure_date)
);

-- Transfer Types table
CREATE TABLE dbo.transfer_types (
  id INT IDENTITY(1,1) PRIMARY KEY,
  name NVARCHAR(100) NOT NULL UNIQUE,
  description NVARCHAR(MAX) NULL,
  icon NVARCHAR(255) NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME()
);

-- Transfer Routes table
CREATE TABLE dbo.transfer_routes (
  id INT IDENTITY(1,1) PRIMARY KEY,
  origin_city_id INT NOT NULL,
  destination_city_id INT NOT NULL,
  origin_specific NVARCHAR(255) NULL,
  destination_specific NVARCHAR(255) NULL,
  transfer_type_id INT NOT NULL,
  base_price DECIMAL(10,2) NOT NULL,
  duration_minutes INT NOT NULL,
  distance_km DECIMAL(8,2) NULL,
  capacity INT NOT NULL,
  description NVARCHAR(MAX) NULL,
  ad BIT DEFAULT 0,
  discount_percent DECIMAL(5,2) DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_transfer_routes_origin_cities FOREIGN KEY (origin_city_id) REFERENCES dbo.cities(id),
  CONSTRAINT FK_transfer_routes_destination_cities FOREIGN KEY (destination_city_id) REFERENCES dbo.cities(id),
  CONSTRAINT FK_transfer_routes_types FOREIGN KEY (transfer_type_id) REFERENCES dbo.transfer_types(id),
  CONSTRAINT FK_transfer_routes_users FOREIGN KEY (created_by) REFERENCES dbo.users(id)
);

-- Transfer Schedules table
CREATE TABLE dbo.transfer_schedules (
  id INT IDENTITY(1,1) PRIMARY KEY,
  route_id INT NOT NULL,
  departure_time TIME NOT NULL,
  days_of_week NVARCHAR(20) NOT NULL,
  is_active BIT DEFAULT 1,
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_transfer_schedules_routes FOREIGN KEY (route_id) REFERENCES dbo.transfer_routes(id)
);

-- Transfer Instances table
CREATE TABLE dbo.transfer_instances (
  id INT IDENTITY(1,1) PRIMARY KEY,
  schedule_id INT NOT NULL,
  departure_date DATE NOT NULL,
  departure_datetime DATETIME2 NOT NULL,
  price DECIMAL(10,2) NULL,
  vehicles_total INT DEFAULT 0,
  vehicles_available INT DEFAULT 0,
  seats_available INT DEFAULT 0,
  status NVARCHAR(20) DEFAULT 'scheduled' CHECK (status IN ('scheduled', 'in_transit', 'completed', 'cancelled')),
  created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
  CONSTRAINT FK_transfer_instances_schedules FOREIGN KEY (schedule_id) REFERENCES dbo.transfer_schedules(id),
  CONSTRAINT UQ_transfer_instances_schedule_date UNIQUE (schedule_id, departure_date)
);

-- Indexes for new flights/transfers architecture
CREATE INDEX IX_airports_code ON dbo.airports(code);
CREATE INDEX IX_airports_city_id ON dbo.airports(city_id);
CREATE INDEX IX_flight_routes_origin ON dbo.flight_routes(origin_airport_id);
CREATE INDEX IX_flight_routes_destination ON dbo.flight_routes(destination_airport_id);
CREATE INDEX IX_flight_routes_airline ON dbo.flight_routes(airline);
CREATE INDEX IX_flight_route_pairs_outbound ON dbo.flight_route_pairs(outbound_route_id);
CREATE INDEX IX_flight_route_pairs_return ON dbo.flight_route_pairs(return_route_id);
CREATE INDEX IX_flight_schedules_route_id ON dbo.flight_schedules(route_id);
CREATE INDEX IX_flight_schedules_route_pair_id ON dbo.flight_schedules(route_pair_id);
CREATE INDEX IX_flight_schedules_active ON dbo.flight_schedules(is_active);
CREATE INDEX IX_flight_instances_schedule_id ON dbo.flight_instances(schedule_id);
CREATE INDEX IX_flight_instances_departure_datetime ON dbo.flight_instances(departure_datetime);
CREATE INDEX IX_flight_instances_status ON dbo.flight_instances(status);
CREATE INDEX IX_transfer_types_name ON dbo.transfer_types(name);
CREATE INDEX IX_transfer_routes_origin ON dbo.transfer_routes(origin_city_id);
CREATE INDEX IX_transfer_routes_destination ON dbo.transfer_routes(destination_city_id);
CREATE INDEX IX_transfer_routes_type ON dbo.transfer_routes(transfer_type_id);
CREATE INDEX IX_transfer_schedules_route_id ON dbo.transfer_schedules(route_id);
CREATE INDEX IX_transfer_schedules_active ON dbo.transfer_schedules(is_active);
CREATE INDEX IX_transfer_instances_schedule_id ON dbo.transfer_instances(schedule_id);
CREATE INDEX IX_transfer_instances_departure_datetime ON dbo.transfer_instances(departure_datetime);
CREATE INDEX IX_transfer_instances_status ON dbo.transfer_instances(status);
