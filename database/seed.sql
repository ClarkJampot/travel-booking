-- Seed data for travel booking system (SQL Server)

-- Roles
INSERT INTO dbo.roles(name) VALUES (N'customer'), (N'agency'), (N'owner'), (N'admin');

-- Users (password: password123 for all - hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)
INSERT INTO dbo.users(email, password_hash, full_name, role_id, phone, address) VALUES
(N'customer@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Alice Customer', 1, N'+63 912 345 6789', N'Manila, Philippines'),
(N'agency@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Beta Travel Agency', 2, N'+63 912 345 6790', N'Cebu, Philippines'),
(N'owner@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Hotel Owner', 3, N'+63 912 345 6791', N'Boracay, Philippines'),
(N'admin@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Admin User', 4, N'+63 912 345 6792', N'Manila, Philippines');

-- Destinations
INSERT INTO dbo.destinations(name, country, description, image_url, featured) VALUES
(N'Boracay', N'Philippines', N'Famous white sand beach destination with crystal clear waters and vibrant nightlife.', N'/uploads/destinations/boracay.jpg', 1),
(N'Palawan', N'Philippines', N'Known as the last frontier, featuring stunning limestone cliffs and pristine beaches.', N'/uploads/destinations/palawan.jpg', 1),
(N'Cebu', N'Philippines', N'Historic city with beautiful beaches, diving spots, and rich cultural heritage.', N'/uploads/destinations/cebu.jpg', 1),
(N'Bohol', N'Philippines', N'Home to the famous Chocolate Hills and the adorable Philippine tarsier.', N'/uploads/destinations/bohol.jpg', 0),
(N'Baguio', N'Philippines', N'Cool mountain city known as the Summer Capital with scenic views and strawberry farms.', N'/uploads/destinations/baguio.jpg', 0),
(N'Manila', N'Philippines', N'Capital city with rich history, modern shopping centers, and cultural attractions.', N'/uploads/destinations/manila.jpg', 0),
(N'Davao', N'Philippines', N'Largest city in Mindanao with Mount Apo, Philippine Eagle Center, and durian fruits.', N'/uploads/destinations/davao.jpg', 0),
(N'Iloilo', N'Philippines', N'Heritage city with beautiful colonial architecture and delicious local cuisine.', N'/uploads/destinations/iloilo.jpg', 0);

-- Hotels (linked to destinations)
INSERT INTO dbo.hotels(name, destination_id, city, country, price_per_night, rating, description, image_url, booking_count, created_by) VALUES
(N'Boracay Paradise Resort', 1, N'Boracay', N'Philippines', 120.00, 4.8, N'Premium beach resort with white sand beaches and water activities.', N'/uploads/hotels/boracay-paradise.jpg', 25, 3),
(N'White Beach Hotel', 1, N'Boracay', N'Philippines', 80.00, 4.5, N'Beachfront hotel with direct access to White Beach.', N'/uploads/hotels/white-beach.jpg', 18, 3),
(N'Palawan Eco Lodge', 2, N'Palawan', N'Philippines', 65.00, 4.6, N'Eco-friendly accommodation surrounded by pristine nature.', N'/uploads/hotels/palawan-eco.jpg', 18, 3),
(N'El Nido Beach Resort', 2, N'El Nido', N'Philippines', 95.00, 4.7, N'Luxury resort with stunning views of limestone cliffs.', N'/uploads/hotels/el-nido.jpg', 22, 3),
(N'Seaside Inn', 3, N'Cebu', N'Philippines', 45.00, 4.2, N'Beautiful beachfront hotel with stunning ocean views and modern amenities.', N'/uploads/hotels/seaside-inn.jpg', 12, 3),
(N'Cebu Business Center', 3, N'Cebu', N'Philippines', 90.00, 4.3, N'Modern business hotel in Cebu IT Park.', N'/uploads/hotels/cebu-business.jpg', 14, 3),
(N'Bohol Beach Resort', 4, N'Bohol', N'Philippines', 80.00, 4.4, N'Family-friendly resort with beach access and island tours.', N'/uploads/hotels/bohol-beach.jpg', 20, 3),
(N'Mountain View Lodge', 5, N'Baguio', N'Philippines', 55.00, 4.5, N'Cozy mountain retreat with panoramic views and cool climate.', N'/uploads/hotels/mountain-view.jpg', 8, 3),
(N'City Center Hotel', 6, N'Manila', N'Philippines', 75.00, 4.0, N'Luxury hotel in the heart of Manila with business facilities.', N'/uploads/hotels/city-center.jpg', 15, 3),
(N'Davao Business Hotel', 7, N'Davao', N'Philippines', 50.00, 4.1, N'Modern business hotel with conference facilities.', N'/uploads/hotels/davao-business.jpg', 9, 3),
(N'Iloilo Heritage Inn', 8, N'Iloilo', N'Philippines', 40.00, 4.3, N'Historic hotel showcasing local architecture and culture.', N'/uploads/hotels/iloilo-heritage.jpg', 6, 3);

-- Flights
INSERT INTO dbo.flights(airline, origin, destination, depart_date, price, description, image_url, booking_count, created_by) VALUES
(N'AirAsia', N'MNL', N'Boracay', '2025-10-20', 45.00, N'Direct flight to paradise with beach views.', N'/uploads/flights/airasia-boracay.jpg', 20, 2),
(N'Cebu Pacific', N'MNL', N'Palawan', '2025-10-21', 55.00, N'Scenic flight to the last frontier of the Philippines.', N'/uploads/flights/cebu-palawan.jpg', 18, 2),
(N'PAL', N'MNL', N'CEB', '2025-10-22', 50.00, N'Philippine Airlines premium service with complimentary meals.', N'/uploads/flights/pal-ceb.jpg', 15, 2),
(N'AirAsia', N'MNL', N'BOHOL', '2025-10-23', 48.00, N'Comfortable flight to the Chocolate Hills destination.', N'/uploads/flights/airasia-bohol.jpg', 14, 2),
(N'Cebu Pacific', N'MNL', N'BAGUIO', '2025-10-24', 35.00, N'Short flight to the Summer Capital.', N'/uploads/flights/cebu-baguio.jpg', 11, 2),
(N'PAL', N'MNL', N'DVO', '2025-10-25', 52.00, N'Premium service to Davao City.', N'/uploads/flights/pal-dvo.jpg', 12, 2),
(N'AirAsia', N'CEB', N'Boracay', '2025-10-26', 40.00, N'Quick connection to Boracay from Cebu.', N'/uploads/flights/airasia-ceb-boracay.jpg', 8, 2),
(N'Cebu Pacific', N'MNL', N'ILOILO', '2025-10-27', 42.00, N'Reliable service to the Queen City of the South.', N'/uploads/flights/cebu-iloilo.jpg', 9, 2);

-- Activities (linked to destinations)
INSERT INTO dbo.activities(title, destination_id, city, date, price, description, image_url, booking_count, created_by) VALUES
(N'Boracay Beach Activities', 1, N'Boracay', '2025-10-25', 30.00, N'Enjoy water sports and beach activities on White Beach.', N'/uploads/activities/boracay-beach.jpg', 25, 2),
(N'Sunset Sailing', 1, N'Boracay', '2025-10-26', 25.00, N'Romantic sunset sailing experience around Boracay.', N'/uploads/activities/boracay-sunset.jpg', 18, 2),
(N'Underground River Tour', 2, N'Palawan', '2025-10-27', 35.00, N'Explore the UNESCO World Heritage underground river.', N'/uploads/activities/palawan-underground.jpg', 22, 2),
(N'Island Hopping', 2, N'El Nido', '2025-10-28', 40.00, N'Discover beautiful lagoons and hidden beaches.', N'/uploads/activities/el-nido-hopping.jpg', 20, 2),
(N'Island Hopping', 3, N'Cebu', '2025-10-29', 20.00, N'Explore beautiful islands with crystal clear waters and white sand beaches.', N'/uploads/activities/cebu-island-hopping.jpg', 15, 2),
(N'Chocolate Hills Tour', 4, N'Bohol', '2025-10-30', 25.00, N'Visit the famous Chocolate Hills and see the Philippine tarsier.', N'/uploads/activities/bohol-chocolate-hills.jpg', 18, 2),
(N'Strawberry Farm Visit', 5, N'Baguio', '2025-10-31', 12.00, N'Pick fresh strawberries and enjoy the cool mountain climate.', N'/uploads/activities/baguio-strawberry.jpg', 12, 2),
(N'City Walking Tour', 6, N'Manila', '2025-11-01', 15.00, N'Discover Manila''s rich history and culture through guided walking tours.', N'/uploads/activities/manila-walking-tour.jpg', 8, 2),
(N'Davao City Tour', 7, N'Davao', '2025-11-02', 20.00, N'Visit Davao''s landmarks including the Philippine Eagle Center.', N'/uploads/activities/davao-city-tour.jpg', 10, 2),
(N'Iloilo Heritage Walk', 8, N'Iloilo', '2025-11-03', 18.00, N'Explore historic churches and heritage houses.', N'/uploads/activities/iloilo-heritage.jpg', 7, 2);

-- Transfers
INSERT INTO dbo.transfers(service, origin, destination, date, price, description, image_url, booking_count, created_by) VALUES
(N'Airport Shuttle', N'Boracay Airport', N'White Beach', '2025-10-20', 20.00, N'Direct transfer to Boracay White Beach hotels.', N'/uploads/transfers/boracay-shuttle.jpg', 22, 2),
(N'Van Transfer', N'Palawan Airport', N'Puerto Princesa', '2025-10-21', 15.00, N'Airport shuttle to Puerto Princesa city center.', N'/uploads/transfers/palawan-shuttle.jpg', 18, 2),
(N'Car Service', N'Cebu Airport', N'City Center', '2025-10-22', 12.00, N'Premium car service with professional driver.', N'/uploads/transfers/cebu-car.jpg', 14, 2),
(N'Bus Transfer', N'Manila', N'Baguio', '2025-10-23', 8.00, N'Comfortable bus ride to the Summer Capital.', N'/uploads/transfers/manila-baguio-bus.jpg', 15, 2),
(N'Ferry Transfer', N'Cebu', N'Bohol', '2025-10-24', 12.00, N'Scenic ferry ride to Bohol island.', N'/uploads/transfers/cebu-bohol-ferry.jpg', 18, 2),
(N'Private Car', N'Davao Airport', N'City Center', '2025-10-25', 15.00, N'Reliable car transfer to downtown area.', N'/uploads/transfers/davao-car.jpg', 9, 2);

-- Bookings
INSERT INTO dbo.bookings(user_id, item_type, item_id, total_price, status) VALUES
(1, N'hotel', 1, 120.00, N'confirmed'),
(1, N'hotel', 3, 65.00, N'confirmed'),
(1, N'flight', 1, 45.00, N'confirmed'),
(1, N'activity', 1, 30.00, N'confirmed'),
(1, N'transfer', 1, 20.00, N'confirmed');

-- Ads
INSERT INTO dbo.ads(placement, title, image_url, link_url, active) VALUES
(N'home', N'Boracay Deals', N'/uploads/ads/boracay-deals.jpg', N'destinations.html?id=1', 1),
(N'home', N'Palawan Adventure', N'/uploads/ads/palawan-adventure.jpg', N'destinations.html?id=2', 1),
(N'listing', N'Island Hopping Promo', N'/uploads/ads/island-promo.jpg', N'activities.html', 1),
(N'sidebar', N'Hotel Discount', N'/uploads/ads/hotel-discount.jpg', N'hotels.html', 1);
