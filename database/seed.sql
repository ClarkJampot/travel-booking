-- Seed data for booking-system (SQL Server)

-- Roles
INSERT INTO dbo.roles(name) VALUES (N'customer'), (N'agency'), (N'owner'), (N'admin');

-- Users (dummy hashes, not used yet in PHP)
INSERT INTO dbo.users(email, password_hash, full_name, role_id) VALUES
(N'alice@example.com', N'$2b$10$hashalice', N'Alice Customer', 1),
(N'beta.agency@example.com', N'$2b$10$hashagency', N'Beta Agency', 2),
(N'hotel.owner@example.com', N'$2b$10$hashowner', N'Hotel Owner', 3),
(N'admin@example.com', N'$2b$10$hashadmin', N'Admin User', 4);

-- Hotels
INSERT INTO dbo.hotels(name, city, country, price_per_night, rating, created_by) VALUES
(N'Seaside Inn', N'Cebu', N'Philippines', 45.00, 4.2, 3),
(N'Mountain View Lodge', N'Baguio', N'Philippines', 55.00, 4.5, 3),
(N'City Center Hotel', N'Manila', N'Philippines', 75.00, 4.0, 3);

-- Flights
INSERT INTO dbo.flights(airline, origin, destination, depart_date, price, created_by) VALUES
(N'AirAsia', N'MNL', N'CEB', '2025-10-20', 35.00, 2),
(N'Cebu Pacific', N'MNL', N'DVO', '2025-10-21', 40.00, 2),
(N'PAL', N'CEB', N'MNL', '2025-10-22', 50.00, 2);

-- Activities
INSERT INTO dbo.activities(title, city, date, price, created_by) VALUES
(N'Island Hopping', N'Cebu', '2025-10-25', 20.00, 2),
(N'City Walking Tour', N'Manila', '2025-10-26', 15.00, 2),
(N'Strawberry Farm Visit', N'Baguio', '2025-10-27', 12.00, 2);

-- Transfers
INSERT INTO dbo.transfers(service, origin, destination, date, price, created_by) VALUES
(N'Airport Shuttle', N'MNL Airport', N'Makati', '2025-10-20', 10.00, 2),
(N'Van Transfer', N'Cebu Airport', N'Moalboal', '2025-10-21', 25.00, 2),
(N'Car Service', N'Davao Airport', N'City Center', '2025-10-22', 15.00, 2);

-- Ads
INSERT INTO dbo.ads(placement, title, image_url, link_url, active) VALUES
(N'home', N'Cebu Deals', NULL, NULL, 1),
(N'listing', N'Island Hopping Promo', NULL, NULL, 1),
(N'sidebar', N'Hotel Discount', NULL, NULL, 1);