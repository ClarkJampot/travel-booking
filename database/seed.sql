-- Complete Seed Data for Travel Booking System (SQL Server)
-- Phase 4: Complete rewrite with hashed passwords, proper ownership, and city_id references
-- All passwords are hashed using PHP password_hash() with PASSWORD_DEFAULT
-- Unhashed passwords are documented in docs/SEED_PASSWORDS.md

-- ============================================================================
-- ROLES
-- ============================================================================
INSERT INTO dbo.roles(name) VALUES (N'customer'), (N'agency'), (N'owner'), (N'admin');

-- ============================================================================
-- PROVINCES (All 81 Philippine Provinces organized by region)
-- ============================================================================

-- National Capital Region (NCR)
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Metro Manila', N'NCR', N'National Capital Region');

-- Region I - Ilocos Region
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Ilocos Norte', N'IN', N'Ilocos Region'),
(N'Ilocos Sur', N'IS', N'Ilocos Region'),
(N'La Union', N'LU', N'Ilocos Region'),
(N'Pangasinan', N'PAN', N'Ilocos Region');

-- Region II - Cagayan Valley
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Batanes', N'BTN', N'Cagayan Valley'),
(N'Cagayan', N'CAG', N'Cagayan Valley'),
(N'Isabela', N'ISA', N'Cagayan Valley'),
(N'Nueva Vizcaya', N'NUV', N'Cagayan Valley'),
(N'Quirino', N'QUI', N'Cagayan Valley');

-- Region III - Central Luzon
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Aurora', N'AUR', N'Central Luzon'),
(N'Bataan', N'BAN', N'Central Luzon'),
(N'Bulacan', N'BUL', N'Central Luzon'),
(N'Nueva Ecija', N'NUE', N'Central Luzon'),
(N'Pampanga', N'PAM', N'Central Luzon'),
(N'Tarlac', N'TAR', N'Central Luzon'),
(N'Zambales', N'ZMB', N'Central Luzon');

-- Region IV-A - CALABARZON
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Batangas', N'BTG', N'CALABARZON'),
(N'Cavite', N'CAV', N'CALABARZON'),
(N'Laguna', N'LAG', N'CALABARZON'),
(N'Quezon', N'QUE', N'CALABARZON'),
(N'Rizal', N'RIZ', N'CALABARZON');

-- Region IV-B - MIMAROPA
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Marinduque', N'MAD', N'MIMAROPA'),
(N'Occidental Mindoro', N'MDC', N'MIMAROPA'),
(N'Oriental Mindoro', N'MDR', N'MIMAROPA'),
(N'Palawan', N'PLW', N'MIMAROPA'),
(N'Romblon', N'ROM', N'MIMAROPA');

-- Region V - Bicol Region
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Albay', N'ALB', N'Bicol Region'),
(N'Camarines Norte', N'CAN', N'Bicol Region'),
(N'Camarines Sur', N'CAS', N'Bicol Region'),
(N'Catanduanes', N'CAT', N'Bicol Region'),
(N'Masbate', N'MAS', N'Bicol Region'),
(N'Sorsogon', N'SOR', N'Bicol Region');

-- Region VI - Western Visayas
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Aklan', N'AKL', N'Western Visayas'),
(N'Antique', N'ANT', N'Western Visayas'),
(N'Capiz', N'CAP', N'Western Visayas'),
(N'Guimaras', N'GUI', N'Western Visayas'),
(N'Iloilo', N'ILI', N'Western Visayas'),
(N'Negros Occidental', N'NEC', N'Western Visayas');

-- Region VII - Central Visayas
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Bohol', N'BOH', N'Central Visayas'),
(N'Cebu', N'CEB', N'Central Visayas'),
(N'Negros Oriental', N'NER', N'Central Visayas'),
(N'Siquijor', N'SIG', N'Central Visayas');

-- Region VIII - Eastern Visayas
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Biliran', N'BIL', N'Eastern Visayas'),
(N'Eastern Samar', N'EAS', N'Eastern Visayas'),
(N'Leyte', N'LEY', N'Eastern Visayas'),
(N'Northern Samar', N'NSA', N'Eastern Visayas'),
(N'Samar', N'WSA', N'Eastern Visayas'),
(N'Southern Leyte', N'SLE', N'Eastern Visayas');

-- Region IX - Zamboanga Peninsula
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Zamboanga del Norte', N'ZAN', N'Zamboanga Peninsula'),
(N'Zamboanga del Sur', N'ZAS', N'Zamboanga Peninsula'),
(N'Zamboanga Sibugay', N'ZSI', N'Zamboanga Peninsula');

-- Region X - Northern Mindanao
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Bukidnon', N'BUK', N'Northern Mindanao'),
(N'Camiguin', N'CAM', N'Northern Mindanao'),
(N'Lanao del Norte', N'LAN', N'Northern Mindanao'),
(N'Misamis Occidental', N'MSC', N'Northern Mindanao'),
(N'Misamis Oriental', N'MSR', N'Northern Mindanao');

-- Region XI - Davao Region
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Davao de Oro', N'DVO', N'Davao Region'),
(N'Davao del Norte', N'DAV', N'Davao Region'),
(N'Davao del Sur', N'DAS', N'Davao Region'),
(N'Davao Occidental', N'DAC', N'Davao Region'),
(N'Davao Oriental', N'DAO', N'Davao Region');

-- Region XII - SOCCSKSARGEN
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Cotabato', N'NCO', N'SOCCSKSARGEN'),
(N'Sarangani', N'SAR', N'SOCCSKSARGEN'),
(N'South Cotabato', N'SCO', N'SOCCSKSARGEN'),
(N'Sultan Kudarat', N'SUK', N'SOCCSKSARGEN');

-- Region XIII - Caraga
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Agusan del Norte', N'AGN', N'Caraga'),
(N'Agusan del Sur', N'AGS', N'Caraga'),
(N'Dinagat Islands', N'DIN', N'Caraga'),
(N'Surigao del Norte', N'SUN', N'Caraga'),
(N'Surigao del Sur', N'SUR', N'Caraga');

-- Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Basilan', N'BAS', N'BARMM'),
(N'Lanao del Sur', N'LAS', N'BARMM'),
(N'Maguindanao', N'MAG', N'BARMM'),
(N'Sulu', N'SLU', N'BARMM'),
(N'Tawi-Tawi', N'TAW', N'BARMM');

-- Cordillera Administrative Region (CAR)
INSERT INTO dbo.provinces(name, code, region) VALUES
(N'Abra', N'ABR', N'Cordillera Administrative Region'),
(N'Apayao', N'APA', N'Cordillera Administrative Region'),
(N'Benguet', N'BEN', N'Cordillera Administrative Region'),
(N'Ifugao', N'IFU', N'Cordillera Administrative Region'),
(N'Kalinga', N'KAL', N'Cordillera Administrative Region'),
(N'Mountain Province', N'MOU', N'Cordillera Administrative Region');

-- ============================================================================
-- CITIES (Tourist-Relevant Cities - All Component Cities + Independent Cities + Major Tourist Municipalities)
-- ============================================================================

-- Metro Manila (NCR) - Province ID: 1
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Manila', 1, 1),
(N'Makati', 1, 0),
(N'Quezon City', 1, 0),
(N'Taguig', 1, 0),
(N'Pasig', 1, 0),
(N'Mandaluyong', 1, 0),
(N'Pasay', 1, 0),
(N'Las Piñas', 1, 0),
(N'Parañaque', 1, 0),
(N'Valenzuela', 1, 0),
(N'Caloocan', 1, 0),
(N'Malabon', 1, 0),
(N'Navotas', 1, 0),
(N'Muntinlupa', 1, 0),
(N'Marikina', 1, 0),
(N'San Juan', 1, 0);

-- Ilocos Norte - Province ID: 2
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Laoag', 2, 1),
(N'Batac', 2, 0),
(N'Pagudpud', 2, 0);

-- Ilocos Sur - Province ID: 3
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Vigan', 3, 1),
(N'Candon', 3, 0);

-- La Union - Province ID: 4
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'San Fernando', 4, 1),
(N'San Juan', 4, 0);

-- Pangasinan - Province ID: 5
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Lingayen', 5, 1),
(N'Dagupan', 5, 0),
(N'Alaminos', 5, 0),
(N'San Carlos', 5, 0),
(N'Urdaneta', 5, 0);

-- Batanes - Province ID: 6
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Basco', 6, 1),
(N'Mahatao', 6, 0),
(N'Ivana', 6, 0),
(N'Uyugan', 6, 0),
(N'Sabtang', 6, 0),
(N'Itbayat', 6, 0);

-- Cagayan - Province ID: 7
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Tuguegarao', 7, 1);

-- Isabela - Province ID: 8
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Ilagan', 8, 1),
(N'Santiago', 8, 0),
(N'Cauayan', 8, 0);

-- Nueva Vizcaya - Province ID: 9
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Bayombong', 9, 1);

-- Quirino - Province ID: 10
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Cabarroguis', 10, 1);

-- Aurora - Province ID: 11
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Baler', 11, 1);

-- Bataan - Province ID: 12
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Balanga', 12, 1),
(N'Mariveles', 12, 0);

-- Bulacan - Province ID: 13
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Malolos', 13, 1),
(N'San Jose del Monte', 13, 0),
(N'Meycauayan', 13, 0);

-- Nueva Ecija - Province ID: 14
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Palayan', 14, 1),
(N'Cabanatuan', 14, 0),
(N'Gapan', 14, 0),
(N'San Jose', 14, 0),
(N'Muñoz', 14, 0);

-- Pampanga - Province ID: 15
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'San Fernando', 15, 1),
(N'Angeles', 15, 0),
(N'Mabalacat', 15, 0);

-- Tarlac - Province ID: 16
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Tarlac City', 16, 1);

-- Zambales - Province ID: 17
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Iba', 17, 1),
(N'Olongapo', 17, 0),
(N'Subic', 17, 0);

-- Batangas - Province ID: 18
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Batangas City', 18, 1),
(N'Lipa', 18, 0),
(N'Nasugbu', 18, 0),
(N'Anilao', 18, 0),
(N'Tagaytay', 18, 0);

-- Cavite - Province ID: 19
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Trece Martires', 19, 1),
(N'Cavite City', 19, 0),
(N'Tagaytay', 19, 0),
(N'Bacoor', 19, 0),
(N'Imus', 19, 0),
(N'Dasmariñas', 19, 0);

-- Laguna - Province ID: 20
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Santa Cruz', 20, 1),
(N'Calamba', 20, 0),
(N'San Pablo', 20, 0),
(N'Los Baños', 20, 0),
(N'Biñan', 20, 0);

-- Quezon - Province ID: 21
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Lucena', 21, 1),
(N'Tayabas', 21, 0);

-- Rizal - Province ID: 22
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Antipolo', 22, 1),
(N'Taytay', 22, 0);

-- Marinduque - Province ID: 23
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Boac', 23, 1);

-- Occidental Mindoro - Province ID: 24
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Mamburao', 24, 1),
(N'San Jose', 24, 0);

-- Oriental Mindoro - Province ID: 25
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Calapan', 25, 1),
(N'Puerto Galera', 25, 0);

-- Palawan - Province ID: 26
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Puerto Princesa', 26, 1),
(N'El Nido', 26, 0),
(N'Coron', 26, 0),
(N'San Vicente', 26, 0),
(N'Roxas', 26, 0);

-- Romblon - Province ID: 27
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Romblon', 27, 1);

-- Albay - Province ID: 28
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Legazpi', 28, 1),
(N'Tabaco', 28, 0),
(N'Ligao', 28, 0);

-- Camarines Norte - Province ID: 29
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Daet', 29, 1);

-- Camarines Sur - Province ID: 30
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Pili', 30, 1),
(N'Naga', 30, 0),
(N'Iriga', 30, 0);

-- Catanduanes - Province ID: 31
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Virac', 31, 1);

-- Masbate - Province ID: 32
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Masbate City', 32, 1);

-- Sorsogon - Province ID: 33
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Sorsogon City', 33, 1);

-- Aklan - Province ID: 34
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Kalibo', 34, 1),
(N'Boracay', 34, 0);

-- Antique - Province ID: 35
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'San Jose', 35, 1);

-- Capiz - Province ID: 36
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Roxas', 36, 1);

-- Guimaras - Province ID: 37
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Jordan', 37, 1);

-- Iloilo - Province ID: 38
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Iloilo City', 38, 1),
(N'Passi', 38, 0);

-- Negros Occidental - Province ID: 39
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Bacolod', 39, 1),
(N'Bago', 39, 0),
(N'Cadiz', 39, 0),
(N'Escalante', 39, 0),
(N'Himamaylan', 39, 0),
(N'Kabankalan', 39, 0),
(N'La Carlota', 39, 0),
(N'Sagay', 39, 0),
(N'San Carlos', 39, 0),
(N'Silay', 39, 0),
(N'Sipalay', 39, 0),
(N'Talisay', 39, 0),
(N'Victorias', 39, 0);

-- Bohol - Province ID: 40
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Tagbilaran', 40, 1),
(N'Panglao', 40, 0),
(N'Loboc', 40, 0),
(N'Carmen', 40, 0);

-- Cebu - Province ID: 41
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Cebu City', 41, 1),
(N'Lapu-Lapu', 41, 0),
(N'Mandaue', 41, 0),
(N'Talisay', 41, 0),
(N'Toledo', 41, 0),
(N'Danao', 41, 0);

-- Negros Oriental - Province ID: 42
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Dumaguete', 42, 1),
(N'Bais', 42, 0),
(N'Bayawan', 42, 0),
(N'Canlaon', 42, 0),
(N'Guihulngan', 42, 0),
(N'Tanjay', 42, 0);

-- Siquijor - Province ID: 43
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Siquijor', 43, 1);

-- Biliran - Province ID: 44
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Naval', 44, 1);

-- Eastern Samar - Province ID: 45
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Borongan', 45, 1);

-- Leyte - Province ID: 46
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Tacloban', 46, 1),
(N'Ormoc', 46, 0),
(N'Baybay', 46, 0);

-- Northern Samar - Province ID: 47
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Catarman', 47, 1);

-- Samar - Province ID: 48
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Catbalogan', 48, 1),
(N'Calbayog', 48, 0);

-- Southern Leyte - Province ID: 49
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Maasin', 49, 1);

-- Zamboanga del Norte - Province ID: 50
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Dipolog', 50, 1),
(N'Dapitan', 50, 0);

-- Zamboanga del Sur - Province ID: 51
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Pagadian', 51, 1),
(N'Zamboanga City', 51, 0);

-- Zamboanga Sibugay - Province ID: 52
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Ipil', 52, 1);

-- Bukidnon - Province ID: 53
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Malaybalay', 53, 1),
(N'Valencia', 53, 0);

-- Camiguin - Province ID: 54
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Mambajao', 54, 1);

-- Lanao del Norte - Province ID: 55
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Tubod', 55, 1),
(N'Iligan', 55, 0);

-- Misamis Occidental - Province ID: 56
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Oroquieta', 56, 1),
(N'Ozamiz', 56, 0),
(N'Tangub', 56, 0);

-- Misamis Oriental - Province ID: 57
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Cagayan de Oro', 57, 1),
(N'El Salvador', 57, 0),
(N'Gingoog', 57, 0);

-- Davao de Oro - Province ID: 58
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Nabunturan', 58, 1);

-- Davao del Norte - Province ID: 59
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Tagum', 59, 1),
(N'Panabo', 59, 0),
(N'Island Garden City of Samal', 59, 0);

-- Davao del Sur - Province ID: 60
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Digos', 60, 1),
(N'Davao City', 60, 0);

-- Davao Occidental - Province ID: 61
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Mati', 61, 1);

-- Davao Oriental - Province ID: 62
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Mat', 62, 1);

-- Cotabato - Province ID: 63
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Kidapawan', 63, 1);

-- Sarangani - Province ID: 64
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Alabel', 64, 1),
(N'General Santos', 64, 0);

-- South Cotabato - Province ID: 65
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Koronadal', 65, 1),
(N'General Santos', 65, 0);

-- Sultan Kudarat - Province ID: 66
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Isulan', 66, 1),
(N'Tacurong', 66, 0);

-- Agusan del Norte - Province ID: 67
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Cabadbaran', 67, 1),
(N'Butuan', 67, 0);

-- Agusan del Sur - Province ID: 68
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Prosperidad', 68, 1),
(N'Bayugan', 68, 0);

-- Dinagat Islands - Province ID: 69
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'San Jose', 69, 1);

-- Surigao del Norte - Province ID: 70
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Surigao City', 70, 1),
(N'Siargao', 70, 0),
(N'General Luna', 70, 0),
(N'Dapa', 70, 0);

-- Surigao del Sur - Province ID: 71
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Tandag', 71, 1),
(N'Bislig', 71, 0);

-- Basilan - Province ID: 72
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Isabela', 72, 1),
(N'Lamitan', 72, 0);

-- Lanao del Sur - Province ID: 73
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Marawi', 73, 1);

-- Maguindanao - Province ID: 74
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Buluan', 74, 1);

-- Sulu - Province ID: 75
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Jolo', 75, 1);

-- Tawi-Tawi - Province ID: 76
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Bongao', 76, 1);

-- Abra - Province ID: 77
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Bangued', 77, 1);

-- Apayao - Province ID: 78
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Kabugao', 78, 1);

-- Benguet - Province ID: 79
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'La Trinidad', 79, 1),
(N'Baguio', 79, 0);

-- Ifugao - Province ID: 80
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Lagawe', 80, 1),
(N'Banaue', 80, 0);

-- Kalinga - Province ID: 81
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Tabuk', 81, 1);

-- Mountain Province - Province ID: 82
INSERT INTO dbo.cities(name, province_id, is_capital) VALUES
(N'Bontoc', 82, 1),
(N'Sagada', 82, 0);

-- ============================================================================
-- USERS (2-3 per role with hashed passwords)
-- ============================================================================
-- Password hash algorithm: PHP password_hash() with PASSWORD_DEFAULT
-- All passwords are: password123 (for testing)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- Customers (3 users)
INSERT INTO dbo.users(email, password_hash, full_name, phone, role_id) VALUES
(N'customer1@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Juan Dela Cruz', N'+639171234567', 1),
(N'customer2@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Maria Santos', N'+639171234568', 1),
(N'customer3@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Pedro Garcia', N'+639171234569', 1);

-- Agencies (3 users)
INSERT INTO dbo.users(email, password_hash, full_name, phone, role_id) VALUES
(N'agency1@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Travel Express', N'+639171234570', 2),
(N'agency2@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Adventure Tours', N'+639171234571', 2),
(N'agency3@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Paradise Travel', N'+639171234572', 2);

-- Owners (3 users)
INSERT INTO dbo.users(email, password_hash, full_name, phone, role_id) VALUES
(N'owner1@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Hotel Magnate', N'+639171234573', 3),
(N'owner2@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Resort Group', N'+639171234574', 3),
(N'owner3@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Luxury Hotels', N'+639171234575', 3);

-- Admins (2 users)
INSERT INTO dbo.users(email, password_hash, full_name, phone, role_id) VALUES
(N'admin@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'System Administrator', N'+639171234576', 4),
(N'admin2@example.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'Site Manager', N'+639171234577', 4);

-- ============================================================================
-- DESTINATIONS
-- ============================================================================
INSERT INTO dbo.destinations(name, description, featured) VALUES
(N'Boracay Island', N'Famous white sand beach destination in Aklan, perfect for water activities and nightlife.', 1),
(N'Puerto Princesa', N'Capital city of Palawan, gateway to stunning natural attractions including the Underground River.', 1),
(N'Bohol', N'Beautiful island province known for its unique Chocolate Hills, tarsiers, and pristine beaches.', 1),
(N'Baguio City', N'Cool mountain city known as the Summer Capital of the Philippines.', 1),
(N'Siargao Island', N'Surfing capital of the Philippines with pristine beaches and natural pools.', 1),
(N'El Nido', N'Stunning limestone cliffs, crystal-clear lagoons, and pristine beaches.', 0),
(N'Coron', N'World-class diving destination with shipwrecks and stunning coral reefs.', 0),
(N'Vigan', N'Historic Spanish colonial city, a UNESCO World Heritage Site.', 0),
(N'Sagada', N'Mountain destination known for hanging coffins and beautiful caves.', 0),
(N'Batanes', N'Northernmost province with rolling hills, traditional stone houses, and dramatic landscapes.', 0);

-- ============================================================================
-- HOTELS (Owned by owners, using city_id and province_id via subqueries)
-- ============================================================================
-- Owner 1 (user_id 7): Hotels 1-5
-- Owner 2 (user_id 8): Hotels 6-10
-- Owner 3 (user_id 9): Hotels 11-12

INSERT INTO dbo.hotels(name, destination_id, city_id, province_id, price_per_night, description, created_by, ad, discount_percent) VALUES
-- Owner 1 hotels
(N'Boracay Paradise Resort', 1, (SELECT id FROM dbo.cities WHERE name = N'Boracay' AND province_id = (SELECT id FROM dbo.provinces WHERE name = N'Aklan')), (SELECT id FROM dbo.provinces WHERE name = N'Aklan'), 3500.00, N'Beachfront resort with stunning sunset views and direct beach access.', 7, 1, 20.00),
(N'Manila Grand Hotel', NULL, (SELECT id FROM dbo.cities WHERE name = N'Manila'), (SELECT id FROM dbo.provinces WHERE name = N'Metro Manila'), 2500.00, N'Modern hotel in the heart of Manila with excellent city views.', 7, 0, 0.00),
(N'Prestige Vacation Apartments', 4, (SELECT id FROM dbo.cities WHERE name = N'Baguio'), (SELECT id FROM dbo.provinces WHERE name = N'Benguet'), 1800.00, N'Cozy mountain retreat with cool climate and scenic views.', 7, 0, 15.00),
(N'One Central Hotel', NULL, (SELECT id FROM dbo.cities WHERE name = N'Cebu City'), (SELECT id FROM dbo.provinces WHERE name = N'Cebu'), 2200.00, N'Conveniently located hotel in downtown Cebu City.', 7, 1, 35.00),
(N'Summit Ridge Resort', NULL, (SELECT id FROM dbo.cities WHERE name = N'Tagaytay' AND province_id = (SELECT id FROM dbo.provinces WHERE name = N'Batangas')), (SELECT id FROM dbo.provinces WHERE name = N'Batangas'), 3200.00, N'Scenic resort overlooking Taal Volcano with cool mountain breeze.', 7, 0, 0.00),

-- Owner 2 hotels
(N'El Nido Beach Hotel', 6, (SELECT id FROM dbo.cities WHERE name = N'El Nido'), (SELECT id FROM dbo.provinces WHERE name = N'Palawan'), 4500.00, N'Luxurious beachfront resort with access to stunning lagoons.', 8, 1, 30.00),
(N'Bacau Bay Resort Coron', 7, (SELECT id FROM dbo.cities WHERE name = N'Coron'), (SELECT id FROM dbo.provinces WHERE name = N'Palawan'), 4200.00, N'Diving resort with easy access to world-class dive sites.', 8, 0, 0.00),
(N'Harana Surf Resort', 5, (SELECT id FROM dbo.cities WHERE name = N'General Luna'), (SELECT id FROM dbo.provinces WHERE name = N'Surigao del Norte'), 3800.00, N'Surfing-focused resort near Cloud 9 surf break.', 8, 1, 22.00),
(N'Henann Resort Alona Beach', 3, (SELECT id FROM dbo.cities WHERE name = N'Panglao'), (SELECT id FROM dbo.provinces WHERE name = N'Bohol'), 3600.00, N'Beachfront resort near Alona Beach with stunning views.', 8, 0, 10.00),
(N'Seaview Beach Resort', NULL, (SELECT id FROM dbo.cities WHERE name = N'Dumaguete'), (SELECT id FROM dbo.provinces WHERE name = N'Negros Oriental'), 2800.00, N'Comfortable hotel with ocean views in the City of Gentle People.', 8, 0, 0.00),

-- Owner 3 hotels
(N'Vigan Heritage Mansion', 8, (SELECT id FROM dbo.cities WHERE name = N'Vigan'), (SELECT id FROM dbo.provinces WHERE name = N'Ilocos Sur'), 2000.00, N'Historic inn in the heart of Vigan''s Spanish colonial district.', 9, 0, 0.00),
(N'Kanip Aw Pines View Lodge', 9, (SELECT id FROM dbo.cities WHERE name = N'Sagada'), (SELECT id FROM dbo.provinces WHERE name = N'Mountain Province'), 1500.00, N'Rustic lodge perfect for adventure seekers and nature lovers.', 9, 1, 18.00);

-- ============================================================================
-- FLIGHTS (Owned by agencies)
-- ============================================================================
-- Agency 1 (user_id 4): Flights 1-5
-- Agency 2 (user_id 5): Flights 6-10
-- Agency 3 (user_id 6): Flights 11-15

INSERT INTO dbo.flights(airline, origin, destination, depart_date, price, description, created_by, ad, discount_percent, trip_type) VALUES
-- Agency 1 flights
(N'Philippine Airlines', N'MNL', N'MPH', '2024-06-01', 8500.00, N'Direct flight from Manila to Boracay (Caticlan).', 4, 1, 15.00, N'one-way'),
(N'Cebu Pacific', N'MNL', N'CEB', '2024-06-05', 4500.00, N'Affordable flight from Manila to Cebu.', 4, 0, 0.00, N'round-trip'),
(N'AirAsia', N'MNL', N'PPS', '2024-06-10', 6200.00, N'Flight to Puerto Princesa, gateway to Palawan.', 4, 0, 12.00, N'one-way'),
(N'Philippine Airlines', N'MNL', N'TAG', '2024-06-15', 7800.00, N'Flight to Tagbilaran, Bohol.', 4, 1, 20.00, N'round-trip'),
(N'Cebu Pacific', N'MNL', N'BAG', '2024-06-20', 3500.00, N'Flight to Baguio for cool mountain escape.', 4, 0, 0.00, N'one-way'),

-- Agency 2 flights
(N'Philippine Airlines', N'MNL', N'USU', '2024-07-01', 9200.00, N'Flight to El Nido, Palawan.', 5, 1, 25.00, N'one-way'),
(N'Cebu Pacific', N'MNL', N'DVO', '2024-07-05', 5500.00, N'Flight to Davao City.', 5, 0, 0.00, N'round-trip'),
(N'AirAsia', N'MNL', N'DGT', '2024-07-10', 5800.00, N'Flight to Dumaguete, Negros Oriental.', 5, 0, 8.00, N'one-way'),
(N'Philippine Airlines', N'CEB', N'MPH', '2024-07-15', 6800.00, N'Flight from Cebu to Boracay.', 5, 1, 18.00, N'one-way'),
(N'Cebu Pacific', N'MNL', N'ILO', '2024-07-20', 4800.00, N'Flight to Iloilo City.', 5, 0, 0.00, N'one-way'),

-- Agency 3 flights
(N'Philippine Airlines', N'MNL', N'VGN', '2024-08-01', 7200.00, N'Flight to Vigan, Ilocos Sur.', 6, 0, 0.00, N'one-way'),
(N'Cebu Pacific', N'MNL', N'LGP', '2024-08-05', 4200.00, N'Flight to Legazpi, Albay.', 6, 1, 22.00, N'round-trip'),
(N'AirAsia', N'MNL', N'BCD', '2024-08-10', 5200.00, N'Flight to Bacolod, Negros Occidental.', 6, 0, 0.00, N'one-way'),
(N'Philippine Airlines', N'MNL', N'ZAM', '2024-08-15', 8800.00, N'Flight to Zamboanga City.', 6, 0, 10.00, N'one-way'),
(N'Cebu Pacific', N'MNL', N'CDO', '2024-08-20', 5600.00, N'Flight to Cagayan de Oro.', 6, 0, 0.00, N'one-way');

-- ============================================================================
-- ACTIVITIES (Owned by agencies, using city_id via subqueries)
-- ============================================================================
-- Agency 1 (user_id 4): Activities 1-5
-- Agency 2 (user_id 5): Activities 6-10
-- Agency 3 (user_id 6): Activities 11-15

INSERT INTO dbo.activities(title, destination_id, city_id, date, price, description, created_by, ad, discount_percent) VALUES
-- Agency 1 activities
(N'Boracay Island Hopping Tour', 1, (SELECT id FROM dbo.cities WHERE name = N'Boracay'), '2024-06-15', 2500.00, N'Full-day island hopping tour to nearby islands with snorkeling and lunch.', 4, 1, 20.00),
(N'Manila City Tour', NULL, (SELECT id FROM dbo.cities WHERE name = N'Manila'), '2024-06-20', 1800.00, N'Guided tour of historic Intramuros and modern Manila attractions.', 4, 0, 0.00),
(N'Baguio Strawberry Farm Experience', 4, (SELECT id FROM dbo.cities WHERE name = N'Baguio'), '2024-06-25', 1200.00, N'Visit strawberry farms and enjoy fresh strawberry picking.', 4, 0, 10.00),
(N'Cebu Heritage Walk', NULL, (SELECT id FROM dbo.cities WHERE name = N'Cebu City'), '2024-07-01', 1500.00, N'Explore Cebu''s historic sites including Magellan''s Cross and Basilica.', 4, 0, 0.00),
(N'Tagaytay Ridge Hiking', NULL, (SELECT id FROM dbo.cities WHERE name = N'Tagaytay' AND province_id = (SELECT id FROM dbo.provinces WHERE name = N'Batangas')), '2024-07-05', 2000.00, N'Scenic hiking trail with views of Taal Volcano and Lake.', 4, 1, 15.00),

-- Agency 2 activities
(N'El Nido Island Hopping Tour A', 6, (SELECT id FROM dbo.cities WHERE name = N'El Nido'), '2024-07-10', 3200.00, N'Visit Secret Lagoon, Big Lagoon, and stunning beaches.', 5, 1, 25.00),
(N'Coron Wreck Diving', 7, (SELECT id FROM dbo.cities WHERE name = N'Coron'), '2024-07-15', 4500.00, N'Dive to World War II shipwrecks in crystal-clear waters.', 5, 0, 0.00),
(N'Siargao Surfing Lesson', 5, (SELECT id FROM dbo.cities WHERE name = N'General Luna'), '2024-07-20', 3500.00, N'Learn to surf at Cloud 9 with professional instructors.', 5, 1, 18.00),
(N'Bohol Chocolate Hills Tour', 3, (SELECT id FROM dbo.cities WHERE name = N'Tagbilaran'), '2024-07-25', 2500.00, N'Visit the unique geological formation with over 1,200 cone-shaped hills. Includes viewing deck and optional ATV adventure.', 5, 1, 15.00),
(N'Dumaguete City Tour', NULL, (SELECT id FROM dbo.cities WHERE name = N'Dumaguete'), '2024-08-01', 1600.00, N'Explore Dumaguete''s heritage sites and local markets.', 5, 0, 8.00),

-- Agency 3 activities
(N'Vigan Heritage Walk', 8, (SELECT id FROM dbo.cities WHERE name = N'Vigan'), '2024-08-05', 1400.00, N'Stroll through cobblestone streets and visit ancestral houses.', 6, 0, 0.00),
(N'Sagada Cave Connection', 9, (SELECT id FROM dbo.cities WHERE name = N'Sagada'), '2024-08-10', 2200.00, N'Adventure through connected caves with stunning rock formations.', 6, 1, 22.00),
(N'Batanes North Tour', 10, (SELECT id FROM dbo.cities WHERE name = N'Basco'), '2024-08-15', 3500.00, N'Explore Basco, Mahatao, and Ivana with rolling hills and lighthouses.', 6, 0, 0.00),
(N'Davao City Nature Tour', NULL, (SELECT id FROM dbo.cities WHERE name = N'Davao City'), '2024-08-20', 2800.00, N'Visit Mount Apo, Philippine Eagle Center, and Davao Crocodile Park.', 6, 0, 12.00),
(N'Iloilo Culinary Tour', NULL, (SELECT id FROM dbo.cities WHERE name = N'Iloilo City'), '2024-08-25', 2100.00, N'Food tour featuring famous Ilonggo dishes and local delicacies.', 6, 0, 0.00),
(N'Palawan Underground River Tour', 2, (SELECT id FROM dbo.cities WHERE name = N'Puerto Princesa'), '2024-09-01', 3800.00, N'UNESCO World Heritage Site featuring an underground river system. Boat tour through the spectacular cave system.', 4, 1, 20.00);

-- ============================================================================
-- TRANSFERS (Owned by agencies)
-- ============================================================================
-- Agency 1 (user_id 4): Transfers 1-3
-- Agency 2 (user_id 5): Transfers 4-6
-- Agency 3 (user_id 6): Transfers 7-9

INSERT INTO dbo.transfers(service, origin, destination, date, price, description, created_by, ad, discount_percent) VALUES
-- Agency 1 transfers
(N'Manila to Boracay Transfer', N'Manila Airport (MNL)', N'Boracay Port', '2024-06-01', 3500.00, N'Comfortable van transfer from Manila Airport to Boracay Port.', 4, 1, 15.00),
(N'Cebu to Bohol Transfer', N'Cebu Airport (CEB)', N'Bohol Port', '2024-06-05', 2800.00, N'Van transfer from Cebu Airport to Bohol Port.', 4, 0, 0.00),
(N'Manila to Baguio Transfer', N'Manila Airport (MNL)', N'Baguio City', '2024-06-10', 3200.00, N'Bus transfer from Manila Airport to Baguio City.', 4, 0, 10.00),

-- Agency 2 transfers
(N'Puerto Princesa to El Nido Transfer', N'Puerto Princesa Airport (PPS)', N'El Nido', '2024-07-01', 4200.00, N'Van transfer from Puerto Princesa Airport to El Nido.', 5, 1, 20.00),
(N'Manila to Tagaytay Transfer', N'Manila Airport (MNL)', N'Tagaytay City', '2024-07-05', 2500.00, N'Van transfer from Manila Airport to Tagaytay City.', 5, 0, 0.00),
(N'Davao to General Santos Transfer', N'Davao Airport (DVO)', N'General Santos City', '2024-07-10', 1800.00, N'Van transfer from Davao Airport to General Santos City.', 5, 0, 0.00),

-- Agency 3 transfers
(N'Manila to Vigan Transfer', N'Manila Airport (MNL)', N'Vigan City', '2024-08-01', 4500.00, N'Bus transfer from Manila Airport to Vigan City.', 6, 0, 0.00),
(N'Manila to Sagada Transfer', N'Manila Airport (MNL)', N'Sagada', '2024-08-05', 5200.00, N'Van transfer from Manila Airport to Sagada.', 6, 1, 18.00),
(N'Manila to Batanes Transfer', N'Manila Airport (MNL)', N'Batanes (via Basco)', '2024-08-10', 8500.00, N'Van transfer from Manila Airport to Batanes via Basco.', 6, 0, 0.00);

-- ============================================================================
-- BOOKINGS (Linked to customers)
-- ============================================================================
-- Customer 1 (user_id 1): Bookings 1-3
-- Customer 2 (user_id 2): Bookings 4-6
-- Customer 3 (user_id 3): Bookings 7-9
-- Note: Customer user IDs are 1, 2, 3 (correct)

INSERT INTO dbo.bookings(user_id, item_type, item_id, total_price, status) VALUES
-- Customer 1 bookings
(1, N'hotel', 1, 14000.00, N'confirmed'),
(1, N'flight', 1, 17000.00, N'confirmed'),
(1, N'activity', 1, 5000.00, N'confirmed'),

-- Customer 2 bookings
(2, N'hotel', 6, 18000.00, N'confirmed'),
(2, N'flight', 6, 18400.00, N'confirmed'),
(2, N'transfer', 4, 8400.00, N'confirmed'),

-- Customer 3 bookings
(3, N'hotel', 11, 8000.00, N'confirmed'),
(3, N'activity', 11, 2800.00, N'confirmed'),
(3, N'transfer', 7, 9000.00, N'confirmed');

-- ============================================================================
-- ENTITY IMAGES (All images per entity for carousel display)
-- ============================================================================
-- All images are stored here, including primary images (display_order = 1)

-- Hotel Images (3-5 images per hotel)
INSERT INTO dbo.entity_images(entity_type, entity_id, image_url, display_order) VALUES
-- Hotel 1: Boracay Paradise Resort (Primary image + additional)
(N'hotel', 1, N'/uploads/hotels/1/boracay-paradise.jpg', 1),
(N'hotel', 1, N'/uploads/hotels/1/boracay-paradise-1.jpg', 2),
(N'hotel', 1, N'/uploads/hotels/1/boracay-paradise-2.jpg', 3),
(N'hotel', 1, N'/uploads/hotels/1/boracay-paradise-3.jpg', 4),
-- Hotel 2: Manila Grand Hotel (Primary image + additional)
(N'hotel', 2, N'/uploads/hotels/2/manila-grand.jpg', 1),
(N'hotel', 2, N'/uploads/hotels/2/manila-grand-1.jpg', 2),
(N'hotel', 2, N'/uploads/hotels/2/manila-grand-2.jpg', 3),
-- Hotel 3: Prestige Vacation Apartments (Primary image + additional)
(N'hotel', 3, N'/uploads/hotels/3/prestige-apts.jpg', 1),
(N'hotel', 3, N'/uploads/hotels/3/prestige-apts-1.jpg', 2),
(N'hotel', 3, N'/uploads/hotels/3/prestige-apts-2.jpg', 3),
(N'hotel', 3, N'/uploads/hotels/3/prestige-apts-3.jpg', 4),
-- Hotel 4: One Central Hotel (Primary image + additional)
(N'hotel', 4, N'/uploads/hotels/4/cebu-central.jpg', 1),
(N'hotel', 4, N'/uploads/hotels/4/cebu-central-1.jpg', 2),
(N'hotel', 4, N'/uploads/hotels/4/cebu-central-2.jpg', 3),
-- Hotel 5: Summit Ridge Resort (Primary image + additional)
(N'hotel', 5, N'/uploads/hotels/5/tagaytay-ridge.jpg', 1),
(N'hotel', 5, N'/uploads/hotels/5/tagaytay-ridge-1.jpg', 2),
(N'hotel', 5, N'/uploads/hotels/5/tagaytay-ridge-2.jpg', 3),
(N'hotel', 5, N'/uploads/hotels/5/tagaytay-ridge-3.jpg', 4),
(N'hotel', 5, N'/uploads/hotels/5/tagaytay-ridge-4.jpg', 5),
-- Hotel 6: El Nido Beach Hotel (Primary image + additional)
(N'hotel', 6, N'/uploads/hotels/6/el-nido-beach.jpg', 1),
(N'hotel', 6, N'/uploads/hotels/6/el-nido-beach-1.jpg', 2),
(N'hotel', 6, N'/uploads/hotels/6/el-nido-beach-2.jpg', 3),
(N'hotel', 6, N'/uploads/hotels/6/el-nido-beach-3.jpg', 4),
-- Hotel 7: Bacau Bay Resort Coron (Primary image + additional)
(N'hotel', 7, N'/uploads/hotels/7/bacau-bay-resort-coron.jpg', 1),
(N'hotel', 7, N'/uploads/hotels/7/bacau-bay-resort-coron-1.jpg', 2),
(N'hotel', 7, N'/uploads/hotels/7/bacau-bay-resort-coron-2.jpg', 3),
-- Hotel 8: Harana Surf Resort (Primary image + additional)
(N'hotel', 8, N'/uploads/hotels/8/harana-surf.jpg', 1),
(N'hotel', 8, N'/uploads/hotels/8/harana-surf-1.jpg', 2),
(N'hotel', 8, N'/uploads/hotels/8/harana-surf-2.jpg', 3),
(N'hotel', 8, N'/uploads/hotels/8/harana-surf-3.jpg', 4),
-- Hotel 9: Henann Resort Alona Beach (Primary image + additional)
(N'hotel', 9, N'/uploads/hotels/9/henann-resort-alona.jpg', 1),
(N'hotel', 9, N'/uploads/hotels/9/henann-resort-alona-1.jpg', 2),
(N'hotel', 9, N'/uploads/hotels/9/henann-resort-alona-2.jpg', 3),
-- Hotel 10: Seaview Beach Resort (Primary image + additional)
(N'hotel', 10, N'/uploads/hotels/10/dumaguete-seaview.jpg', 1),
(N'hotel', 10, N'/uploads/hotels/10/dumaguete-seaview-1.jpg', 2),
(N'hotel', 10, N'/uploads/hotels/10/dumaguete-seaview-2.jpg', 3),
-- Hotel 11: Vigan Heritage Mansion (Primary image + additional)
(N'hotel', 11, N'/uploads/hotels/11/vigan-heritage.jpg', 1),
(N'hotel', 11, N'/uploads/hotels/11/vigan-heritage-1.jpg', 2),
-- Hotel 12: Kanip Aw Pines View Lodge (Primary image + additional)
(N'hotel', 12, N'/uploads/hotels/12/sagada-lodge.jpg', 1),
(N'hotel', 12, N'/uploads/hotels/12/sagada-lodge-1.jpg', 2),
(N'hotel', 12, N'/uploads/hotels/12/sagada-lodge-2.jpg', 3);

-- Activity Images (2-4 images per activity)
INSERT INTO dbo.entity_images(entity_type, entity_id, image_url, display_order) VALUES
-- Activity 1: Boracay Island Hopping Tour (Primary image + additional)
(N'activity', 1, N'/uploads/activities/1/boracay-hopping.jpg', 1),
(N'activity', 1, N'/uploads/activities/1/boracay-hopping-1.jpg', 2),
(N'activity', 1, N'/uploads/activities/1/boracay-hopping-2.jpg', 3),
-- Activity 2: Manila City Tour (Primary image + additional)
(N'activity', 2, N'/uploads/activities/2/manila-tour.jpg', 1),
(N'activity', 2, N'/uploads/activities/2/manila-tour-1.jpg', 2),
-- Activity 3: Baguio Strawberry Farm Experience (Primary image + additional)
(N'activity', 3, N'/uploads/activities/3/baguio-strawberry.jpg', 1),
(N'activity', 3, N'/uploads/activities/3/baguio-strawberry-1.jpg', 2),
(N'activity', 3, N'/uploads/activities/3/baguio-strawberry-2.jpg', 3),
-- Activity 4: Cebu Heritage Walk (Primary image + additional)
(N'activity', 4, N'/uploads/activities/4/cebu-heritage.jpg', 1),
(N'activity', 4, N'/uploads/activities/4/cebu-heritage-1.jpg', 2),
-- Activity 5: Tagaytay Ridge Hiking (Primary image + additional)
(N'activity', 5, N'/uploads/activities/5/tagaytay-hiking.jpg', 1),
(N'activity', 5, N'/uploads/activities/5/tagaytay-hiking-1.jpg', 2),
(N'activity', 5, N'/uploads/activities/5/tagaytay-hiking-2.jpg', 3),
(N'activity', 5, N'/uploads/activities/5/tagaytay-hiking-3.jpg', 4),
-- Activity 6: El Nido Island Hopping Tour A (Primary image + additional)
(N'activity', 6, N'/uploads/activities/6/el-nido-tour-a.jpg', 1),
(N'activity', 6, N'/uploads/activities/6/el-nido-tour-a-1.jpg', 2),
(N'activity', 6, N'/uploads/activities/6/el-nido-tour-a-2.jpg', 3),
(N'activity', 6, N'/uploads/activities/6/el-nido-tour-a-3.jpg', 4),
-- Activity 7: Coron Wreck Diving (Primary image + additional)
(N'activity', 7, N'/uploads/activities/7/coron-diving.jpg', 1),
(N'activity', 7, N'/uploads/activities/7/coron-diving-1.jpg', 2),
(N'activity', 7, N'/uploads/activities/7/coron-diving-2.jpg', 3),
-- Activity 8: Siargao Surfing Lesson (Primary image + additional)
(N'activity', 8, N'/uploads/activities/8/siargao-surfing.jpg', 1),
(N'activity', 8, N'/uploads/activities/8/siargao-surfing-1.jpg', 2),
(N'activity', 8, N'/uploads/activities/8/siargao-surfing-2.jpg', 3),
-- Activity 9: Bohol Chocolate Hills Tour (Primary image + additional)
(N'activity', 9, N'/uploads/activities/9/chocolate-hills.jpg', 1),
(N'activity', 9, N'/uploads/activities/9/chocolate-hills-1.jpg', 2),
(N'activity', 9, N'/uploads/activities/9/chocolate-hills-2.jpg', 3),
(N'activity', 9, N'/uploads/activities/9/chocolate-hills-3.jpg', 4),
-- Activity 10: Dumaguete City Tour (Primary image + additional)
(N'activity', 10, N'/uploads/activities/10/dumaguete-tour.jpg', 1),
(N'activity', 10, N'/uploads/activities/10/dumaguete-tour-1.jpg', 2),
-- Activity 11: Vigan Heritage Walk (Primary image + additional)
(N'activity', 11, N'/uploads/activities/11/vigan-heritage.jpg', 1),
(N'activity', 11, N'/uploads/activities/11/vigan-heritage-1.jpg', 2),
(N'activity', 11, N'/uploads/activities/11/vigan-heritage-2.jpg', 3),
-- Activity 12: Sagada Cave Connection (Primary image + additional)
(N'activity', 12, N'/uploads/activities/12/sagada-caves.jpg', 1),
(N'activity', 12, N'/uploads/activities/12/sagada-caves-1.jpg', 2),
(N'activity', 12, N'/uploads/activities/12/sagada-caves-2.jpg', 3),
(N'activity', 12, N'/uploads/activities/12/sagada-caves-3.jpg', 4),
-- Activity 13: Batanes North Tour (Primary image + additional)
(N'activity', 13, N'/uploads/activities/13/batanes-north.jpg', 1),
(N'activity', 13, N'/uploads/activities/13/batanes-north-1.jpg', 2),
(N'activity', 13, N'/uploads/activities/13/batanes-north-2.jpg', 3),
-- Activity 14: Davao City Nature Tour (Primary image + additional)
(N'activity', 14, N'/uploads/activities/14/davao-nature.jpg', 1),
(N'activity', 14, N'/uploads/activities/14/davao-nature-1.jpg', 2),
(N'activity', 14, N'/uploads/activities/14/davao-nature-2.jpg', 3),
-- Activity 15: Iloilo Culinary Tour (Primary image + additional)
(N'activity', 15, N'/uploads/activities/15/iloilo-culinary.jpg', 1),
(N'activity', 15, N'/uploads/activities/15/iloilo-culinary-1.jpg', 2),
(N'activity', 15, N'/uploads/activities/15/iloilo-culinary-2.jpg', 3),
-- Activity 16: Palawan Underground River Tour (Primary image + additional)
(N'activity', 16, N'/uploads/activities/16/underground-river.jpg', 1),
(N'activity', 16, N'/uploads/activities/16/underground-river-1.jpg', 2),
(N'activity', 16, N'/uploads/activities/16/underground-river-2.jpg', 3);

-- Destination Images (2-4 images per destination)
INSERT INTO dbo.entity_images(entity_type, entity_id, image_url, display_order) VALUES
-- Destination 1: Boracay Island (Primary image + additional)
(N'destination', 1, N'/uploads/destinations/1/boracay.jpg', 1),
(N'destination', 1, N'/uploads/destinations/1/boracay-1.jpg', 2),
(N'destination', 1, N'/uploads/destinations/1/boracay-2.jpg', 3),
(N'destination', 1, N'/uploads/destinations/1/boracay-3.jpg', 4),
-- Destination 2: Puerto Princesa (Primary image + additional)
(N'destination', 2, N'/uploads/destinations/2/puerto-princesa.jpg', 1),
(N'destination', 2, N'/uploads/destinations/2/puerto-princesa-1.jpg', 2),
(N'destination', 2, N'/uploads/destinations/2/puerto-princesa-2.jpg', 3),
-- Destination 3: Bohol (Primary image + additional)
(N'destination', 3, N'/uploads/destinations/3/bohol.jpg', 1),
(N'destination', 3, N'/uploads/destinations/3/bohol-1.jpg', 2),
(N'destination', 3, N'/uploads/destinations/3/bohol-2.jpg', 3),
-- Destination 4: Baguio City (Primary image + additional)
(N'destination', 4, N'/uploads/destinations/4/baguio.jpg', 1),
(N'destination', 4, N'/uploads/destinations/4/baguio-1.jpg', 2),
(N'destination', 4, N'/uploads/destinations/4/baguio-2.jpg', 3),
-- Destination 5: Siargao Island (Primary image + additional)
(N'destination', 5, N'/uploads/destinations/5/siargao.jpg', 1),
(N'destination', 5, N'/uploads/destinations/5/siargao-1.jpg', 2),
(N'destination', 5, N'/uploads/destinations/5/siargao-2.jpg', 3),
(N'destination', 5, N'/uploads/destinations/5/siargao-3.jpg', 4),
-- Destination 6: El Nido (Primary image + additional)
(N'destination', 6, N'/uploads/destinations/6/el-nido.jpg', 1),
(N'destination', 6, N'/uploads/destinations/6/el-nido-1.jpg', 2),
(N'destination', 6, N'/uploads/destinations/6/el-nido-2.jpg', 3),
(N'destination', 6, N'/uploads/destinations/6/el-nido-3.jpg', 4),
-- Destination 7: Coron (Primary image + additional)
(N'destination', 7, N'/uploads/destinations/7/coron.jpg', 1),
(N'destination', 7, N'/uploads/destinations/7/coron-1.jpg', 2),
(N'destination', 7, N'/uploads/destinations/7/coron-2.jpg', 3),
-- Destination 8: Vigan (Primary image + additional)
(N'destination', 8, N'/uploads/destinations/8/vigan.jpg', 1),
(N'destination', 8, N'/uploads/destinations/8/vigan-1.jpg', 2),
(N'destination', 8, N'/uploads/destinations/8/vigan-2.jpg', 3),
(N'destination', 8, N'/uploads/destinations/8/vigan-3.jpg', 4),
-- Destination 9: Sagada (Primary image + additional)
(N'destination', 9, N'/uploads/destinations/9/sagada.jpg', 1),
(N'destination', 9, N'/uploads/destinations/9/sagada-1.jpg', 2),
(N'destination', 9, N'/uploads/destinations/9/sagada-2.jpg', 3),
-- Destination 10: Batanes (Primary image + additional)
(N'destination', 10, N'/uploads/destinations/10/batanes.jpg', 1),
(N'destination', 10, N'/uploads/destinations/10/batanes-1.jpg', 2),
(N'destination', 10, N'/uploads/destinations/10/batanes-2.jpg', 3),
(N'destination', 10, N'/uploads/destinations/10/batanes-3.jpg', 4),
(N'destination', 10, N'/uploads/destinations/10/batanes-4.jpg', 5);

-- Flight Images (1-2 images per flight)
INSERT INTO dbo.entity_images(entity_type, entity_id, image_url, display_order) VALUES
-- Flight 1: MNL to MPH (Boracay) (Primary image + additional)
(N'flight', 1, N'/uploads/flights/1/pal-mnl-boracay.jpg', 1),
(N'flight', 1, N'/uploads/flights/1/pal-mnl-boracay-1.jpg', 2),
-- Flight 2: MNL to CEB (Primary image)
(N'flight', 2, N'/uploads/flights/2/cebu-pac-mnl-ceb.jpg', 1),
-- Flight 3: MNL to PPS (Primary image + additional)
(N'flight', 3, N'/uploads/flights/3/airasia-mnl-puerto.jpg', 1),
(N'flight', 3, N'/uploads/flights/3/airasia-mnl-puerto-1.jpg', 2),
-- Flight 4: MNL to TAG (Bohol) (Primary image)
(N'flight', 4, N'/uploads/flights/4/pal-mnl-bohol.jpg', 1),
-- Flight 5: MNL to BAG (Baguio) (Primary image + additional)
(N'flight', 5, N'/uploads/flights/5/cebu-pac-mnl-baguio.jpg', 1),
(N'flight', 5, N'/uploads/flights/5/cebu-pac-mnl-baguio-1.jpg', 2),
-- Flight 6: MNL to USU (El Nido) (Primary image + additional)
(N'flight', 6, N'/uploads/flights/6/pal-mnl-el-nido.jpg', 1),
(N'flight', 6, N'/uploads/flights/6/pal-mnl-el-nido-1.jpg', 2),
-- Flight 7: MNL to DVO (Primary image)
(N'flight', 7, N'/uploads/flights/7/cebu-pac-mnl-davao.jpg', 1),
-- Flight 8: MNL to DGT (Primary image + additional)
(N'flight', 8, N'/uploads/flights/8/airasia-mnl-dumaguete.jpg', 1),
(N'flight', 8, N'/uploads/flights/8/airasia-mnl-dumaguete-1.jpg', 2),
-- Flight 9: CEB to MPH (Primary image)
(N'flight', 9, N'/uploads/flights/9/pal-ceb-boracay.jpg', 1),
-- Flight 10: MNL to ILO (Primary image + additional)
(N'flight', 10, N'/uploads/flights/10/cebu-pac-mnl-iloilo.jpg', 1),
(N'flight', 10, N'/uploads/flights/10/cebu-pac-mnl-iloilo-1.jpg', 2),
-- Flight 11: MNL to VGN (Primary image)
(N'flight', 11, N'/uploads/flights/11/pal-mnl-vigan.jpg', 1),
-- Flight 12: MNL to LGP (Primary image + additional)
(N'flight', 12, N'/uploads/flights/12/cebu-pac-mnl-legazpi.jpg', 1),
(N'flight', 12, N'/uploads/flights/12/cebu-pac-mnl-legazpi-1.jpg', 2),
-- Flight 13: MNL to BCD (Primary image)
(N'flight', 13, N'/uploads/flights/13/airasia-mnl-bacolod.jpg', 1),
-- Flight 14: MNL to ZAM (Primary image + additional)
(N'flight', 14, N'/uploads/flights/14/pal-mnl-zamboanga.jpg', 1),
(N'flight', 14, N'/uploads/flights/14/pal-mnl-zamboanga-1.jpg', 2),
-- Flight 15: MNL to CDO (Primary image)
(N'flight', 15, N'/uploads/flights/15/cebu-pac-mnl-cagayan.jpg', 1);

-- Transfer Images (1-2 images per transfer)
INSERT INTO dbo.entity_images(entity_type, entity_id, image_url, display_order) VALUES
-- Transfer 1: Manila to Boracay (Primary image + additional)
(N'transfer', 1, N'/uploads/transfers/1/manila-boracay.jpg', 1),
(N'transfer', 1, N'/uploads/transfers/1/manila-boracay-1.jpg', 2),
-- Transfer 2: Cebu to Bohol (Primary image)
(N'transfer', 2, N'/uploads/transfers/2/cebu-bohol.jpg', 1),
-- Transfer 3: Manila to Baguio (Primary image + additional)
(N'transfer', 3, N'/uploads/transfers/3/manila-baguio.jpg', 1),
(N'transfer', 3, N'/uploads/transfers/3/manila-baguio-1.jpg', 2),
-- Transfer 4: Puerto Princesa to El Nido (Primary image + additional)
(N'transfer', 4, N'/uploads/transfers/4/puerto-el-nido.jpg', 1),
(N'transfer', 4, N'/uploads/transfers/4/puerto-el-nido-1.jpg', 2),
-- Transfer 5: Manila to Tagaytay (Primary image)
(N'transfer', 5, N'/uploads/transfers/5/manila-tagaytay.jpg', 1),
-- Transfer 6: Davao to General Santos (Primary image + additional)
(N'transfer', 6, N'/uploads/transfers/6/davao-gensan.jpg', 1),
(N'transfer', 6, N'/uploads/transfers/6/davao-gensan-1.jpg', 2),
-- Transfer 7: Manila to Vigan (Primary image + additional)
(N'transfer', 7, N'/uploads/transfers/7/manila-vigan.jpg', 1),
(N'transfer', 7, N'/uploads/transfers/7/manila-vigan-1.jpg', 2),
-- Transfer 8: Manila to Sagada (Primary image)
(N'transfer', 8, N'/uploads/transfers/8/manila-sagada.jpg', 1),
-- Transfer 9: Manila to Batanes (Primary image + additional)
(N'transfer', 9, N'/uploads/transfers/9/manila-batanes.jpg', 1),
(N'transfer', 9, N'/uploads/transfers/9/manila-batanes-1.jpg', 2);

