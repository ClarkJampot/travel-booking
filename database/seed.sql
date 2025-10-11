-- Seed data for booking-system (SQL Server)

-- Roles
INSERT INTO dbo.roles(name) VALUES (N'customer'), (N'agency'), (N'owner'), (N'admin');

-- Users (dummy hashes, not used yet in PHP)
INSERT INTO dbo.users(email, password_hash, full_name, role_id, user_profile_image) VALUES
(N'alice@example.com', N'$2b$10$hashalice', N'Alice Customer', 1, N'/travel-booking/uploads/profiles/alice.jpg'),
(N'beta.agency@example.com', N'$2b$10$hashagency', N'Beta Agency', 2, N'/travel-booking/uploads/profiles/beta.jpg'),
(N'hotel.owner@example.com', N'$2b$10$hashowner', N'Hotel Owner', 3, N'/travel-booking/uploads/profiles/owner.jpg'),
(N'admin@example.com', N'$2b$10$hashadmin', N'Admin User', 4, N'/travel-booking/uploads/profiles/admin.jpg');

-- Hotels
INSERT INTO dbo.hotels(name, city, country, price_per_night, rating, description, image_url, booking_count, created_by) VALUES
(N'Seaside Inn', N'Cebu', N'Philippines', 45.00, 4.2, N'Beautiful beachfront hotel with stunning ocean views and modern amenities.', N'/travel-booking/uploads/hotels/seaside-inn.jpg', 12, 3),
(N'Mountain View Lodge', N'Baguio', N'Philippines', 55.00, 4.5, N'Cozy mountain retreat with panoramic views and cool climate.', N'/travel-booking/uploads/hotels/mountain-view.jpg', 8, 3),
(N'City Center Hotel', N'Manila', N'Philippines', 75.00, 4.0, N'Luxury hotel in the heart of Manila with business facilities.', N'/travel-booking/uploads/hotels/city-center.jpg', 15, 3),
(N'Boracay Paradise Resort', N'Boracay', N'Philippines', 120.00, 4.8, N'Premium beach resort with white sand beaches and water activities.', N'/travel-booking/uploads/hotels/boracay-paradise.jpg', 25, 3),
(N'Palawan Eco Lodge', N'Palawan', N'Philippines', 65.00, 4.6, N'Eco-friendly accommodation surrounded by pristine nature.', N'/travel-booking/uploads/hotels/palawan-eco.jpg', 18, 3),
(N'Davao Business Hotel', N'Davao', N'Philippines', 50.00, 4.1, N'Modern business hotel with conference facilities.', N'/travel-booking/uploads/hotels/davao-business.jpg', 9, 3),
(N'Iloilo Heritage Inn', N'Iloilo', N'Philippines', 40.00, 4.3, N'Historic hotel showcasing local architecture and culture.', N'/travel-booking/uploads/hotels/iloilo-heritage.jpg', 6, 3),
(N'Bohol Beach Resort', N'Bohol', N'Philippines', 80.00, 4.4, N'Family-friendly resort with beach access and island tours.', N'/travel-booking/uploads/hotels/bohol-beach.jpg', 20, 3),
(N'Cagayan de Oro City Hotel', N'Cagayan de Oro', N'Philippines', 45.00, 4.0, N'Comfortable city hotel with river views.', N'/travel-booking/uploads/hotels/cdo-city.jpg', 7, 3),
(N'Zamboanga Boutique Hotel', N'Zamboanga', N'Philippines', 60.00, 4.2, N'Unique boutique hotel with local charm.', N'/travel-booking/uploads/hotels/zamboanga-boutique.jpg', 11, 3),
(N'Baguio Garden Hotel', N'Baguio', N'Philippines', 35.00, 3.8, N'Budget-friendly hotel with garden views.', N'/travel-booking/uploads/hotels/baguio-garden.jpg', 4, 3),
(N'Cebu Business Center', N'Cebu', N'Philippines', 90.00, 4.3, N'Modern business hotel in Cebu IT Park.', N'/travel-booking/uploads/hotels/cebu-business.jpg', 14, 3);

-- Flights
INSERT INTO dbo.flights(airline, origin, destination, depart_date, price, description, image_url, booking_count, created_by) VALUES
(N'AirAsia', N'MNL', N'CEB', '2025-10-20', 35.00, N'Budget-friendly flight with excellent service and on-time performance.', N'/uploads/flights/airasia-ceb.jpg', 8, 2),
(N'Cebu Pacific', N'MNL', N'DVO', '2025-10-21', 40.00, N'Reliable domestic carrier with comfortable seating.', N'/uploads/flights/cebu-pacific-dvo.jpg', 12, 2),
(N'PAL', N'CEB', N'MNL', '2025-10-22', 50.00, N'Philippine Airlines premium service with complimentary meals.', N'/uploads/flights/pal-ceb-mnl.jpg', 15, 2),
(N'AirAsia', N'MNL', N'BORACAY', '2025-10-23', 45.00, N'Direct flight to paradise with beach views.', N'/uploads/flights/airasia-boracay.jpg', 20, 2),
(N'Cebu Pacific', N'MNL', N'PALAWAN', '2025-10-24', 55.00, N'Scenic flight to the last frontier of the Philippines.', N'/uploads/flights/cebu-palawan.jpg', 18, 2),
(N'PAL', N'MNL', N'BOHOL', '2025-10-25', 48.00, N'Comfortable flight to the Chocolate Hills destination.', N'/uploads/flights/pal-bohol.jpg', 14, 2),
(N'AirAsia', N'CEB', N'DAVAO', '2025-10-26', 30.00, N'Quick domestic connection with great value.', N'/uploads/flights/airasia-ceb-dvo.jpg', 6, 2),
(N'Cebu Pacific', N'MNL', N'ILOILO', '2025-10-27', 42.00, N'Reliable service to the Queen City of the South.', N'/uploads/flights/cebu-iloilo.jpg', 9, 2),
(N'PAL', N'MNL', N'CAGAYAN DE ORO', '2025-10-28', 52.00, N'Premium service to the City of Golden Friendship.', N'/uploads/flights/pal-cdo.jpg', 7, 2),
(N'AirAsia', N'MNL', N'ZAMBOANGA', '2025-10-29', 38.00, N'Affordable flight to Asia''s Latin City.', N'/uploads/flights/airasia-zamboanga.jpg', 5, 2),
(N'Cebu Pacific', N'MNL', N'BAGUIO', '2025-10-30', 35.00, N'Short flight to the Summer Capital.', N'/uploads/flights/cebu-baguio.jpg', 11, 2),
(N'PAL', N'MNL', N'GENERAL SANTOS', '2025-11-01', 46.00, N'Service to the Tuna Capital of the Philippines.', N'/uploads/flights/pal-gensan.jpg', 8, 2);

-- Activities
INSERT INTO dbo.activities(title, city, date, price, description, image_url, booking_count, created_by) VALUES
(N'Island Hopping', N'Cebu', '2025-10-25', 20.00, N'Explore beautiful islands with crystal clear waters and white sand beaches.', N'/uploads/activities/cebu-island-hopping.jpg', 15, 2),
(N'City Walking Tour', N'Manila', '2025-10-26', 15.00, N'Discover Manila''s rich history and culture through guided walking tours.', N'/uploads/activities/manila-walking-tour.jpg', 8, 2),
(N'Strawberry Farm Visit', N'Baguio', '2025-10-27', 12.00, N'Pick fresh strawberries and enjoy the cool mountain climate.', N'/uploads/activities/baguio-strawberry.jpg', 12, 2),
(N'Chocolate Hills Tour', N'Bohol', '2025-10-28', 25.00, N'Visit the famous Chocolate Hills and see the Philippine tarsier.', N'/uploads/activities/bohol-chocolate-hills.jpg', 18, 2),
(N'Underground River Tour', N'Palawan', '2025-10-29', 35.00, N'Explore the UNESCO World Heritage underground river.', N'/uploads/activities/palawan-underground.jpg', 22, 2),
(N'Boracay Beach Activities', N'Boracay', '2025-10-30', 30.00, N'Enjoy water sports and beach activities on White Beach.', N'/uploads/activities/boracay-beach.jpg', 25, 2),
(N'Davao City Tour', N'Davao', '2025-11-01', 20.00, N'Visit Davao''s landmarks including the Philippine Eagle Center.', N'/uploads/activities/davao-city-tour.jpg', 10, 2),
(N'Iloilo Heritage Walk', N'Iloilo', '2025-11-02', 18.00, N'Explore historic churches and heritage houses.', N'/uploads/activities/iloilo-heritage.jpg', 7, 2),
(N'Cagayan de Oro White Water Rafting', N'Cagayan de Oro', '2025-11-03', 40.00, N'Experience thrilling white water rafting adventure.', N'/uploads/activities/cdo-rafting.jpg', 14, 2),
(N'Zamboanga Fort Tour', N'Zamboanga', '2025-11-04', 16.00, N'Visit historic Fort Pilar and experience local culture.', N'/uploads/activities/zamboanga-fort.jpg', 6, 2),
(N'Baguio Mines View', N'Baguio', '2025-11-05', 10.00, N'Enjoy panoramic views and visit local markets.', N'/uploads/activities/baguio-mines-view.jpg', 9, 2),
(N'Cebu Temple Tour', N'Cebu', '2025-11-06', 22.00, N'Visit Taoist Temple and experience spiritual journey.', N'/uploads/activities/cebu-temple.jpg', 11, 2);

-- Transfers
INSERT INTO dbo.transfers(service, origin, destination, date, price, description, image_url, booking_count, created_by) VALUES
(N'Airport Shuttle', N'MNL Airport', N'Makati', '2025-10-20', 10.00, N'Comfortable shuttle service from airport to Makati business district.', N'/uploads/transfers/mnl-makati-shuttle.jpg', 8, 2),
(N'Van Transfer', N'Cebu Airport', N'Moalboal', '2025-10-21', 25.00, N'Private van transfer to Moalboal beach destination.', N'/uploads/transfers/cebu-moalboal-van.jpg', 12, 2),
(N'Car Service', N'Davao Airport', N'City Center', '2025-10-22', 15.00, N'Premium car service with professional driver.', N'/uploads/transfers/davao-city-car.jpg', 6, 2),
(N'Bus Transfer', N'Manila', N'Baguio', '2025-10-23', 8.00, N'Comfortable bus ride to the Summer Capital.', N'/uploads/transfers/manila-baguio-bus.jpg', 15, 2),
(N'Ferry Transfer', N'Cebu', N'Bohol', '2025-10-24', 12.00, N'Scenic ferry ride to Bohol island.', N'/uploads/transfers/cebu-bohol-ferry.jpg', 18, 2),
(N'Private Car', N'Boracay Airport', N'White Beach', '2025-10-25', 20.00, N'Direct transfer to Boracay White Beach hotels.', N'/uploads/transfers/boracay-beach-car.jpg', 22, 2),
(N'Shuttle Service', N'Palawan Airport', N'Puerto Princesa', '2025-10-26', 5.00, N'Airport shuttle to Puerto Princesa city center.', N'/uploads/transfers/palawan-city-shuttle.jpg', 14, 2),
(N'Van Service', N'Iloilo Airport', N'City Hotels', '2025-10-27', 8.00, N'Convenient van service to Iloilo city hotels.', N'/uploads/transfers/iloilo-city-van.jpg', 9, 2),
(N'Car Transfer', N'Cagayan de Oro Airport', N'Downtown', '2025-10-28', 12.00, N'Reliable car transfer to downtown area.', N'/uploads/transfers/cdo-downtown-car.jpg', 7, 2),
(N'Bus Service', N'Zamboanga Airport', N'City Center', '2025-10-29', 6.00, N'Budget-friendly bus service to city center.', N'/uploads/transfers/zamboanga-city-bus.jpg', 5, 2),
(N'Private Transfer', N'Davao Airport', N'Samal Island', '2025-10-30', 30.00, N'Private transfer including boat to Samal Island.', N'/uploads/transfers/davao-samal-private.jpg', 11, 2),
(N'Shuttle Bus', N'General Santos Airport', N'Tuna Capital', '2025-11-01', 7.00, N'Shuttle service to General Santos city.', N'/uploads/transfers/gensan-city-shuttle.jpg', 8, 2);

-- Bookings (to generate realistic popularity data)
INSERT INTO dbo.bookings(user_id, item_type, item_id, total_price) VALUES
(1, N'hotel', 4, 120.00), (1, N'hotel', 8, 80.00), (1, N'hotel', 5, 65.00),
(1, N'flight', 4, 45.00), (1, N'flight', 5, 55.00), (1, N'flight', 6, 48.00),
(1, N'activity', 5, 35.00), (1, N'activity', 6, 30.00), (1, N'activity', 4, 25.00),
(1, N'transfer', 6, 20.00), (1, N'transfer', 5, 12.00), (1, N'transfer', 4, 8.00);

-- Ads
INSERT INTO dbo.ads(placement, title, image_url, link_url, active) VALUES
(N'home', N'Cebu Deals', N'/travel-booking/uploads/ads/cebu-deals.jpg', N'hotels.html?city=Cebu', 1),
(N'listing', N'Island Hopping Promo', N'/travel-booking/uploads/ads/island-promo.jpg', N'activities.html?city=Cebu', 1),
(N'sidebar', N'Hotel Discount', N'/travel-booking/uploads/ads/hotel-discount.jpg', N'hotels.html', 1);