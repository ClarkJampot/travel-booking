-- Seed data for travel-booking

-- Roles
INSERT INTO roles (name) VALUES ('customer'), ('agency'), ('owner'), ('admin');

-- Users (passwords are dummy hashes for demo)
INSERT INTO users (email, password_hash, full_name, role_id) VALUES
('alice@example.com', '$2b$10$hashalice', 'Alice Customer', 1),
('beta.agency@example.com', '$2b$10$hashagency', 'Beta Agency', 2),
('hotel.owner@example.com', '$2b$10$hashowner', 'Hotel Owner', 3),
('admin@example.com', '$2b$10$hashadmin', 'Admin User', 4);

-- Hotels
INSERT INTO hotels (name, city, country, price_per_night, rating, created_by) VALUES
('Seaside Inn', 'Cebu', 'Philippines', 45.00, 4.2, 3),
('Mountain View Lodge', 'Baguio', 'Philippines', 55.00, 4.5, 3),
('City Center Hotel', 'Manila', 'Philippines', 75.00, 4.0, 3);

-- Flights
INSERT INTO flights (airline, origin, destination, depart_date, price, created_by) VALUES
('AirAsia', 'MNL', 'CEB', '2025-10-20', 35.00, 2),
('Cebu Pacific', 'MNL', 'DVO', '2025-10-21', 40.00, 2),
('PAL', 'CEB', 'MNL', '2025-10-22', 50.00, 2);

-- Activities
INSERT INTO activities (title, city, date, price, created_by) VALUES
('Island Hopping', 'Cebu', '2025-10-25', 20.00, 2),
('City Walking Tour', 'Manila', '2025-10-26', 15.00, 2),
('Strawberry Farm Visit', 'Baguio', '2025-10-27', 12.00, 2);

-- Transfers
INSERT INTO transfers (service, origin, destination, date, price, created_by) VALUES
('Airport Shuttle', 'MNL Airport', 'Makati', '2025-10-20', 10.00, 2),
('Van Transfer', 'Cebu Airport', 'Moalboal', '2025-10-21', 25.00, 2),
('Car Service', 'Davao Airport', 'City Center', '2025-10-22', 15.00, 2);

-- Ads
INSERT INTO ads (placement, title, image_url, link_url, active) VALUES
('home', 'Cebu Deals', NULL, NULL, 1),
('listing', 'Island Hopping Promo', NULL, NULL, 1),
('sidebar', 'Hotel Discount', NULL, NULL, 1);




