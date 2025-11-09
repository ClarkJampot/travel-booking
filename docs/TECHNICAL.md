# Technical Documentation

## System Requirements

### Server Requirements
- **PHP**: Version 8.0 or higher
- **Web Server**: Apache (via XAMPP) or Nginx
- **Database**: Microsoft SQL Server Express or higher
- **PHP Extensions**: 
  - `php_sqlsrv.dll`
  - `php_pdo_sqlsrv.dll`
  - `php_fileinfo.dll` (for file uploads)

### Browser Compatibility
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Installation & Deployment Guide

### Step 1: Install XAMPP
1. Download and install XAMPP from https://www.apachefriends.org/
2. Start Apache service

### Step 2: Install SQL Server
1. Download and install Microsoft SQL Server Express
2. Enable TCP/IP in SQL Server Configuration Manager
3. Restart SQL Server service

### Step 3: Install PHP SQL Server Drivers
1. Download Microsoft PHP drivers for SQL Server
2. Copy `php_sqlsrv.dll` and `php_pdo_sqlsrv.dll` to `xampp/php/ext/`
3. Edit `xampp/php/php.ini` and add:
   ```
   extension=php_sqlsrv.dll
   extension=php_pdo_sqlsrv.dll
   ```
4. Restart Apache

### Step 4: Database Setup
1. Create database: `booking-system`
2. Create SQL Login with `db_owner` permissions
3. Run schema:
   ```bash
   sqlcmd -S localhost -d booking-system -U <user> -P <pass> -i database\schema.sql
   ```
4. Run seed data:
   ```bash
   sqlcmd -S localhost -d booking-system -U <user> -P <pass> -i database\seed.sql
   ```

### Step 5: Configure Application
1. Copy `api/config.php` and update with your database credentials
2. Update `JWT_SECRET` with a secure random string
3. Place project in `C:/xampp/htdocs/travel-booking/`

### Step 6: Verify Installation
1. Access `http://localhost/travel-booking/api/` - should return `{"ok":true,"message":"Travel Booking API v1.0"}`
2. Access `http://localhost/travel-booking/public/index.html` - should display homepage

## Source Code Structure

```
travel-booking/
├── api/                      # Backend API
│   ├── bootstrap.php         # Core bootstrap (CORS, error handling)
│   ├── config.php            # Configuration (DB, JWT)
│   ├── db.php                # Database connection
│   ├── jwt.php               # JWT token functions
│   ├── index.php             # Router
│   ├── controllers/          # API controllers
│   │   ├── auth.php          # Authentication
│   │   ├── destinations.php  # Destinations CRUD
│   │   ├── hotels.php        # Hotels CRUD
│   │   ├── flights.php       # Flights CRUD
│   │   ├── activities.php    # Activities CRUD
│   │   ├── transfers.php     # Transfers CRUD
│   │   ├── bookings.php      # Bookings CRUD
│   │   ├── ads.php           # Ads CRUD
│   │   ├── upload.php        # File upload
│   │   ├── search.php        # Unified search
│   │   └── top.php           # Top items
│   └── middleware/
│       └── auth.php          # Authentication middleware
├── public/                    # Frontend
│   ├── index.html            # Homepage
│   ├── destinations.html     # Destination listing
│   ├── destination-details.html
│   ├── hotels.html           # Hotel listing
│   ├── hotel-details.html
│   ├── flights.html          # Flight listing
│   ├── flight-details.html
│   ├── activities.html       # Activity listing
│   ├── activity-details.html
│   ├── transfers.html        # Transfer listing
│   ├── transfer-details.html
│   ├── bookings.html         # User bookings
│   ├── login.html            # Login page
│   ├── register.html         # Registration page
│   ├── profile.html          # User profile
│   ├── includes/
│   │   ├── header.html       # Navigation header
│   │   └── footer.html       # Footer
│   ├── css/
│   │   └── style.css         # Custom styles
│   ├── js/
│   │   ├── main.js           # Common utilities
│   │   └── auth.js           # Authentication functions
│   └── uploads/              # Uploaded images
├── database/
│   ├── schema.sql            # Database schema
│   └── seed.sql              # Seed data
└── docs/                     # Documentation
```

## API Documentation

### Base URL
```
http://localhost/travel-booking/api
```

### Authentication
All protected endpoints require a JWT token in the Authorization header:
```
Authorization: Bearer <token>
```

### Endpoints

#### Authentication
- `POST /auth/register` - Register new user
- `POST /auth/login` - Login (returns JWT token)
- `POST /auth/logout` - Logout (revoke token)
- `GET /auth/me` - Get current user
- `POST /auth/refresh` - Refresh JWT token

#### Destinations
- `GET /destinations` - List destinations (filters: country, featured)
- `GET /destinations/:id` - Get single destination
- `POST /destinations` - Create destination (admin only)
- `PUT /destinations/:id` - Update destination (admin only)
- `DELETE /destinations/:id` - Delete destination (admin only)

#### Hotels
- `GET /hotels` - List hotels (filters: city, country, destination_id, minPrice, maxPrice, ratingMin)
- `GET /hotels/:id` - Get single hotel
- `POST /hotels` - Create hotel (owner/admin only)
- `PUT /hotels/:id` - Update hotel (owner/admin only)
- `DELETE /hotels/:id` - Delete hotel (owner/admin only)

#### Flights
- `GET /flights` - List flights (filters: origin, destination, date)
- `GET /flights/:id` - Get single flight
- `POST /flights` - Create flight (agency/admin only)
- `PUT /flights/:id` - Update flight (agency/admin only)
- `DELETE /flights/:id` - Delete flight (agency/admin only)

#### Activities
- `GET /activities` - List activities (filters: city, destination_id, date)
- `GET /activities/:id` - Get single activity
- `POST /activities` - Create activity (agency/admin only)
- `PUT /activities/:id` - Update activity (agency/admin only)
- `DELETE /activities/:id` - Delete activity (agency/admin only)

#### Transfers
- `GET /transfers` - List transfers (filters: origin, destination, date)
- `GET /transfers/:id` - Get single transfer
- `POST /transfers` - Create transfer (agency/admin only)
- `PUT /transfers/:id` - Update transfer (agency/admin only)
- `DELETE /transfers/:id` - Delete transfer (agency/admin only)

#### Bookings
- `GET /bookings` - List user's bookings (requires auth)
- `GET /bookings/:id` - Get single booking
- `POST /bookings` - Create booking (requires auth)
- `PUT /bookings/:id` - Update booking status (requires auth)
- `DELETE /bookings/:id` - Cancel booking (requires auth)

#### Ads
- `GET /ads` - List ads (filter: placement)
- `POST /ads` - Create ad (admin only)
- `PUT /ads/:id` - Update ad (admin only)
- `DELETE /ads/:id` - Delete ad (admin only)

#### Upload
- `POST /upload` - Upload image (requires auth, form-data: image, category)

#### Search
- `GET /search?q=<query>&type=<type>` - Unified search (type: hotels, flights, activities, transfers, destinations, all)

#### Top Items
- `GET /top/hotels?limit=<n>` - Top hotels
- `GET /top/flights?limit=<n>` - Top flights
- `GET /top/activities?limit=<n>` - Top activities
- `GET /top/transfers?limit=<n>` - Top transfers

### Response Format
All responses are JSON:
- Success: `{"results": [...], "page": 1, "limit": 10}`
- Error: `{"error": "Error message"}`

### Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `500` - Internal Server Error


