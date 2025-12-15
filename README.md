# TuklasPH - Travel Booking System

A comprehensive travel booking platform for discovering and booking hotels, flights, activities, and transfers across the Philippines.

---

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Functional Specifications](#functional-specifications)
3. [Tech Stack](#tech-stack)
4. [UI/UX Documentation](#uiux-documentation)
5. [Database Design](#database-design)
6. [Code Structure](#code-structure)
7. [Installation & Setup](#installation--setup)
8. [Usage Instructions](#usage-instructions)

---

## System Requirements

### Server Requirements

- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 8.0 or higher
- **Database**: Microsoft SQL Server 2017 or higher (with ODBC Driver 17+)
- **PHP Extensions**:
  - `pdo_sqlsrv` (SQL Server PDO driver)
  - `json`
  - `mbstring`
  - `fileinfo`
  - `gd` (for image processing)
- **Web Server Modules**: `mod_rewrite` (Apache) or equivalent URL rewriting

### Client Requirements

- **Modern Web Browser**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **JavaScript**: Enabled
- **Screen Resolution**: Responsive design supports 320px to 4K displays

### Recommended Server Configuration

- **RAM**: Minimum 2GB, Recommended 4GB+
- **Storage**: 10GB+ for application and uploads
- **Network**: Stable internet connection for API calls

---

## Functional Specifications

### User Roles

The system supports three user roles:

#### 1. **Customer** (`customer`)
- Browse and search hotels, flights, activities, transfers, and destinations
- View detailed information for each item
- Make bookings for hotels, flights, activities, and transfers
- View and manage personal bookings
- Cancel bookings
- View booking history

#### 2. **Owner** (`owner`)
- All customer capabilities
- Create, edit, and delete hotels
- View dashboard with booking statistics
- View bookings for their hotels
- Promote hotels (set as featured)
- Upload multiple images for hotels

#### 3. **Agency** (`agency`)
- All customer capabilities
- Create, edit, and delete flights and transfers
- View dashboard with booking statistics
- View bookings for their flights and transfers
- Promote flights and transfers (set as featured)
- Upload multiple images for flights and transfers

### Core Features

#### Booking System
- **Hotels**: Date-based booking with check-in/check-out dates and guest count
- **Flights**: One-way and round-trip bookings with class selection (Economy, Business, First)
- **Activities**: Date-based booking with participant count
- **Transfers**: Date-based booking with passenger count

#### Search & Discovery
- Global search across all content types
- Filter by location (province, city)
- Filter by price range
- Keyword search
- Featured/Top items on homepage
- Promoted items (marked with badges)

#### Content Management
- Soft delete (items marked as deleted but not removed from database)
- Image management (multiple images per item with display order)
- Promotion system (featured items)
- Discount system (percentage-based)

#### User Management
- JWT-based authentication
- Role-based access control
- User profiles
- Booking history

---

## Tech Stack

### Backend

- **Language**: PHP 8.0+ (Procedural with some OOP)
- **Framework**: Vanilla PHP (no framework)
- **Database**: Microsoft SQL Server
- **Authentication**: JWT (JSON Web Tokens)
- **API Architecture**: RESTful API
- **Error Handling**: Custom error handler with logging

### Frontend

- **Language**: Vanilla JavaScript (ES6+)
- **CSS Framework**: Bootstrap 5.3
- **Architecture**: Component-based (custom components)
- **State Management**: LocalStorage for authentication
- **HTTP Client**: Fetch API


---

## UI/UX Documentation

### Design Philosophy

The system follows a **clean, modern, and user-friendly** design approach with emphasis on:
- **Accessibility**: Semantic HTML, ARIA labels, keyboard navigation
- **Responsiveness**: Mobile-first design that scales to desktop
- **Performance**: Lazy loading, optimized images, efficient API calls
- **Consistency**: Unified component library, consistent spacing and typography

### Color Scheme

- **Primary**: Blue (#0d6efd) - Actions, links, primary buttons
- **Secondary**: Gray (#6c757d) - Secondary actions
- **Success**: Green (#198754) - Confirmations, success states
- **Danger**: Red (#dc3545) - Errors, delete actions
- **Warning**: Yellow (#ffc107) - Warnings, alerts
- **Info**: Cyan (#0dcaf0) - Information messages

### Component Library

#### Reusable Components

1. **Cards** (`Card.js`)
   - Standardized card display for hotels, activities, destinations
   - Supports images, badges, pricing, and action buttons

2. **Buttons** (`Button.js`)
   - Consistent button styling across the application
   - Variants: primary, secondary, outline, danger
   - Sizes: small, medium, large

3. **Modals** (`Modal.js`)
   - Confirmation dialogs
   - Alert messages
   - Custom content modals

4. **Filters** (`Filter.js`)
   - Standardized filter UI for listing pages
   - Supports: province, city, price range, keyword search
   - Responsive layout

5. **Forms** (`PostingModal.js`, `PostingModalFormHandler.js`)
   - Dynamic form generation
   - Validation
   - File upload support

6. **Loading States** (`LoadingSpinner.js`)
   - Consistent loading indicators
   - Skeleton screens for content loading

7. **Empty States** (`EmptyState.js`)
   - User-friendly messages when no content is available

### Page Layouts

#### Homepage (`index.html`)
- Hero banner with search bar
- Featured destinations section
- Top hotels section
- Top activities section
- Sticky search bar on scroll

#### Listing Pages (`hotels.html`, `activities.html`, etc.)
- Filter section (province, city, price, keyword)
- Promoted items section (if available)
- Main results grid/list
- Infinite scroll pagination
- Loading indicators

#### Detail Pages (`hotel-details.html`, etc.)
- Image carousel
- Item information
- Booking form (date picker, quantity selectors)
- Price calculation
- Related items

#### Profile Page (`profile.html`)
- User information
- Booking summary statistics
- User-created content (hotels, flights, activities, transfers)
- Edit/delete actions for owned items

#### Dashboard (`dashboard.html`)
- Statistics overview (bookings, revenue, items)
- Booking management table
- Item management table
- Filters and search

### Responsive Breakpoints

- **Mobile**: < 768px (single column, stacked filters)
- **Tablet**: 768px - 991px (2 columns, inline filters)
- **Desktop**: 992px+ (3-4 columns, full filter layout)

### User Experience Features

1. **Smooth Animations**: Fade-in, slide-up animations for content reveal
2. **Loading States**: Skeleton screens and spinners during data fetching
3. **Error Handling**: User-friendly error messages with actionable guidance
4. **Form Validation**: Real-time validation with clear error messages
5. **Image Fallbacks**: Placeholder images for missing or failed image loads
6. **Infinite Scroll**: Automatic loading of more content as user scrolls
7. **Search Suggestions**: Dropdown suggestions while typing in search bar

---

## Database Design

### Entity Relationship Overview

The database follows a normalized design with the following main entities:

```
Users (roles: customer, owner, agency)
├── Hotels (created by owners)
├── Activities (created by owners)
├── Flights (created by agencies)
└── Transfers (created by agencies)

Bookings
├── Hotel Bookings
├── Activity Bookings
├── Flight Bookings
└── Transfer Bookings

Location Hierarchy
├── Provinces
└── Cities (linked to provinces)

Content Items
├── Destinations
├── Hotels
├── Activities
├── Flight Routes
└── Transfer Routes
```

### Core Tables

#### User Management
- **`roles`**: User roles (customer, owner, agency)
- **`users`**: User accounts with authentication
- **`jwt_tokens`**: Active JWT tokens for session management

#### Location Data
- **`provinces`**: Philippine provinces with region information
- **`cities`**: Cities linked to provinces

#### Content Tables
- **`destinations`**: Destination pages/information
- **`hotels`**: Hotel listings with pricing and location
- **`activities`**: Activity listings with pricing and location
- **`flight_routes`**: Flight route definitions
- **`flight_route_pairs`**: Round-trip flight combinations
- **`flight_schedules`**: Recurring flight schedules
- **`flight_instances`**: Specific flight departures with availability
- **`transfer_routes`**: Transfer route definitions
- **`transfer_schedules`**: Recurring transfer schedules
- **`transfer_instances`**: Specific transfer departures with availability
- **`airports`**: Airport information

#### Booking Tables
- **`hotel_bookings`**: Hotel reservations
- **`activity_bookings`**: Activity reservations
- **`flight_bookings`**: Flight reservations
- **`transfer_bookings`**: Transfer reservations

#### Supporting Tables
- **`entity_images`**: Multiple images per entity (hotels, activities, etc.)

### Key Database Features

1. **Soft Delete**: `deleted_at` column for hotels, activities, flights, transfers
2. **Audit Trail**: `created_at`, `updated_at` timestamps
3. **Foreign Keys**: Referential integrity enforced
4. **Indexes**: Optimized for common queries (location, price, status)
5. **Constraints**: Check constraints for status values, data validation

### Database Schema File

The complete schema is defined in `database/schema.sql`. This file includes:
- Table definitions with all columns and constraints
- Foreign key relationships
- Indexes for performance
- Idempotent drop statements (safe to run multiple times)

---

## Code Structure

### Directory Organization

```
travel-booking/
├── api/                          # Backend API
│   ├── bootstrap.php            # Application initialization
│   ├── config.php               # Configuration (not in repo)
│   ├── config.sample.php        # Configuration template
│   ├── db.php                   # Database connection
│   ├── index.php                # API router
│   ├── jwt.php                  # JWT token management
│   ├── controllers/              # Request handlers
│   │   ├── auth.php             # Authentication endpoints
│   │   ├── hotels.php           # Hotel CRUD operations
│   │   ├── activities.php       # Activity CRUD operations
│   │   ├── bookings.php         # Booking management
│   │   ├── flight-instances.php # Flight booking & instances
│   │   ├── flight-routes.php    # Flight route management
│   │   ├── transfer-instances.php # Transfer booking & instances
│   │   ├── transfer-routes.php  # Transfer route management
│   │   ├── destinations.php     # Destination listings
│   │   ├── profile.php          # User profile endpoints
│   │   ├── dashboard.php        # Dashboard for owners/agencies
│   │   ├── search.php           # Global search
│   │   ├── top.php              # Top/featured items
│   │   └── upload.php           # Image upload handler
│   ├── services/                 # Business logic layer
│   │   ├── BaseService.php      # Base service class
│   │   ├── AuthService.php      # Authentication logic
│   │   ├── HotelService.php     # Hotel business logic
│   │   ├── BookingService.php   # Booking business logic
│   │   ├── FlightService.php    # Flight business logic
│   │   ├── TransferService.php  # Transfer business logic
│   │   ├── ProfileService.php   # Profile business logic
│   │   └── DashboardService.php # Dashboard business logic
│   ├── repositories/             # Data access layer
│   │   ├── BaseRepository.php   # Base repository class
│   │   ├── BookingRepository.php # Booking data access
│   │   ├── FlightRepository.php # Flight data access
│   │   ├── TransferRepository.php # Transfer data access
│   │   ├── ProfileRepository.php # Profile data access
│   │   └── DashboardRepository.php # Dashboard data access
│   ├── helpers/                  # Utility functions
│   │   ├── Router.php           # URL routing
│   │   ├── ResponseHelper.php   # API response formatting
│   │   ├── ErrorHandler.php     # Error logging and handling
│   │   ├── QueryBuilder.php     # Dynamic SQL query builder
│   │   ├── FilterHelper.php     # Filter parsing
│   │   ├── ImageHelper.php      # Image management
│   │   ├── BookingHelper.php    # Booking utilities
│   │   ├── PromotionHelper.php  # Promotion logic
│   │   └── ValidationHelper.php # Input validation
│   ├── middleware/               # Authentication middleware
│   │   └── auth.php             # Auth functions
│   └── validators/               # Input validators
│       ├── BaseValidator.php
│       ├── AuthValidator.php
│       └── HotelValidator.php
│
├── public/                       # Frontend application
│   ├── index.html               # Homepage
│   ├── login.html               # Login page
│   ├── register.html            # Registration page
│   ├── profile.html              # User profile
│   ├── bookings.html             # Customer bookings
│   ├── dashboard.html            # Owner/agency dashboard
│   ├── hotels.html               # Hotel listings
│   ├── hotel-details.html        # Hotel detail page
│   ├── activities.html           # Activity listings
│   ├── activity-details.html     # Activity detail page
│   ├── flights.html              # Flight search
│   ├── flight-details.html        # Flight detail page
│   ├── transfers.html            # Transfer listings
│   ├── transfer-details.html      # Transfer detail page
│   ├── destinations.html         # Destination listings
│   ├── destination-details.html   # Destination detail page
│   ├── css/                      # Stylesheets
│   │   ├── style.css            # Main stylesheet
│   │   ├── variables.css        # CSS variables
│   │   ├── base/                # Base styles
│   │   ├── components/          # Component styles
│   │   ├── layout/              # Layout styles
│   │   └── utilities/           # Utility classes
│   ├── js/                       # JavaScript
│   │   ├── main.js              # Main application script
│   │   ├── auth.js              # Authentication logic
│   │   ├── components/          # Reusable components
│   │   │   ├── Card.js          # Card component
│   │   │   ├── Button.js        # Button component
│   │   │   ├── Modal.js         # Modal component
│   │   │   ├── Filter.js        # Filter component
│   │   │   ├── Calendar.js      # Date picker
│   │   │   ├── FileUpload.js    # Image upload
│   │   │   └── ...              # Other components
│   │   ├── utils/               # Utility functions
│   │   │   ├── api.js           # API client
│   │   │   ├── formatters.js    # Data formatting
│   │   │   ├── authHelpers.js   # Auth utilities
│   │   │   └── ...              # Other utilities
│   │   └── pages/               # Page-specific scripts
│   │       └── home.js          # Homepage logic
│   └── uploads/                 # User-uploaded images
│       ├── hotels/
│       ├── activities/
│       ├── destinations/
│       └── ...
│
├── database/                     # Database files
│   ├── schema.sql               # Database schema
│   └── seed.sql                # Seed data (if available)
│
└── docs/                         # Documentation
    ├── DESIGN.md
    ├── TECHNICAL.md
    └── USER_GUIDE.md
```

### Architecture Patterns

#### Backend Architecture

1. **MVC-like Structure**:
   - **Controllers**: Handle HTTP requests, validate input, call services
   - **Services**: Business logic, orchestration
   - **Repositories**: Data access, SQL queries

2. **Separation of Concerns**:
   - Controllers are thin (routing and response formatting)
   - Business logic in services
   - Database queries in repositories
   - Utilities in helpers

3. **Error Handling**:
   - Centralized error handler in `ErrorHandler.php`
   - Consistent error responses via `ResponseHelper`
   - Logging for debugging

#### Frontend Architecture

1. **Component-Based**:
   - Reusable JavaScript components
   - Consistent rendering functions
   - Shared utilities

2. **API Client**:
   - Centralized API calls via `api.js`
   - Automatic token injection
   - Error handling

3. **State Management**:
   - LocalStorage for authentication state
   - URL parameters for filters
   - DOM state for UI interactions

### Key Files Explained

#### Backend

- **`api/index.php`**: Main API router, matches URLs to controllers
- **`api/bootstrap.php`**: Initializes application, sets up error handling, CORS
- **`api/db.php`**: Database connection function
- **`api/jwt.php`**: JWT token generation, validation, storage

#### Frontend

- **`public/js/main.js`**: Application initialization, global event handlers
- **`public/js/utils/api.js`**: API client with authentication
- **`public/js/utils/authHelpers.js`**: Authentication state management
- **`public/css/style.css`**: Main stylesheet with component styles

---

## Installation & Setup

### Step 1: Database Setup

1. **Create Database**:
   ```sql
   CREATE DATABASE [booking-system];
   ```

2. **Run Schema**:
   ```bash
   # Using sqlcmd (Windows)
   sqlcmd -S YOURPC\SQLEXPRESS -d booking-system -i database/schema.sql
   
   # Or use SQL Server Management Studio
   # Open database/schema.sql and execute
   ```

3. **Seed Data** (Optional):
   ```bash
   sqlcmd -S YOURPC\SQLEXPRESS -d booking-system -i database/seed.sql
   ```

### Step 2: Backend Configuration

1. **Copy Configuration Template**:
   ```bash
   cp api/config.sample.php api/config.php
   ```

2. **Edit `api/config.php`**:
   ```php
   // Database Configuration
   const DB_SERVER = 'YOURPC\\SQLEXPRESS';
   const DB_DATABASE = 'booking-system';
   const DB_UID = 'your_sql_login';
   const DB_PWD = 'your_password';
   
   // JWT Configuration
   const JWT_SECRET = 'your-secret-key-change-this-in-production';
   const JWT_EXPIRY = 86400; // 24 hours
   
   // Upload Configuration
   const UPLOAD_MAX_SIZE = 5242880; // 5MB
   const UPLOAD_ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
   
   // Environment
   const ENVIRONMENT = 'development'; // or 'production'
   ```

3. **Set Permissions**:
   - Ensure `public/uploads/` is writable by web server
   - Ensure `public/uploads/temp/` exists and is writable

### Step 3: Web Server Configuration

#### Apache Configuration

1. **Enable mod_rewrite**:
   ```bash
   # Windows (XAMPP/WAMP)
   # Usually enabled by default
   ```

2. **Create `.htaccess` in project root** (if needed):
   ```apache
   RewriteEngine On
   RewriteBase /travel-booking/
   
   # API routes
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^api/(.*)$ api/index.php [L,QSA]
   
   # Frontend routes
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ public/$1 [L,QSA]
   ```


### Step 4: PHP Configuration

Ensure PHP has required extensions enabled in `php.ini`:

```ini
extension=pdo_sqlsrv
extension=json
extension=mbstring
extension=fileinfo
extension=gd
```

### Step 5: Verify Installation

1. **Access Homepage**: `http://localhost/travel-booking/`
2. **Test API**: `http://localhost/travel-booking/api/` (should return JSON)
3. **Check Database Connection**: Try registering a user

---

## Usage Instructions

### For System Administrators

#### Initial Setup

1. **Database Setup** (see Installation section)
2. **Configure `api/config.php`** with database credentials
3. **Set up web server** to serve the application
4. **Verify file permissions** for uploads directory

#### Default Credentials

After running seed data, default users are available (see `SEED_CREDENTIALS.md`):
- **Password for all seed users**: `password123`
- **Customer**: `customer1@example.com`
- **Owner**: `owner1@example.com`
- **Agency**: `agency1@example.com`

**Important**: Change default passwords in production!

### For Customers

#### Registration

1. Navigate to **Register** page
2. Fill in:
   - Email address
   - Password (minimum 6 characters)
   - First name
   - Last name (optional)
   - Role: **Customer**
3. Click **Register**
4. You'll be automatically logged in

#### Making a Booking

1. **Browse** listings (hotels, activities, flights, transfers)
2. **Search** using the search bar or filters
3. **Click** on an item to view details
4. **Select** dates/options:
   - Hotels: Check-in, check-out, number of guests
   - Activities: Date, number of participants
   - Flights: Departure date, return date (if round-trip), class, passengers
   - Transfers: Date, number of passengers
5. **Review** price and details
6. **Click** "Book Now" button
7. Booking is confirmed immediately

#### Managing Bookings

1. Navigate to **My Bookings** (visible when logged in as customer)
2. View all bookings with status (confirmed, cancelled, completed)
3. **Cancel** bookings by clicking "Cancel" button
4. View booking details and item information

### For Hotel Owners

#### Registration

1. Register with role: **Owner**
2. After registration, you'll have access to:
   - All customer features
   - Profile page with content management
   - Dashboard for statistics

#### Creating a Hotel

1. Navigate to **Profile** page
2. Click **"Create Hotel"** button
3. Fill in form:
   - Hotel name
   - Location (province, city, optional destination)
   - Price per night
   - Description
   - Images (upload multiple)
4. Click **"Save"**
5. Hotel appears in listings immediately

#### Managing Hotels

1. Go to **Profile** page
2. View your hotels in **"My Content"** section
3. **Edit**: Click edit button, modify fields, save
4. **Delete**: Click delete button, confirm (soft delete - item hidden from listings)
5. **Promote**: Toggle promotion badge (featured status)

#### Dashboard

1. Navigate to **Dashboard** (visible in navigation for owners)
2. View statistics:
   - Total bookings
   - Total revenue
   - Number of hotels
   - Booking breakdown by status
3. View bookings for your hotels
4. Filter by status, customer name, keyword

### For Travel Agencies

#### Registration

1. Register with role: **Agency**
2. After registration, you'll have access to:
   - All customer features
   - Profile page with content management
   - Dashboard for statistics

#### Creating Flights

1. Navigate to **Profile** page
2. Click **"Create Flight"** button
3. Fill in form:
   - Origin airport
   - Destination airport
   - Airline
   - Base prices (Economy, Business, First)
   - Departure time
4. Click **"Save"**
5. System automatically generates flight instances for next 30 days

#### Creating Transfers

1. Navigate to **Profile** page
2. Click **"Create Transfer"** button
3. Fill in form:
   - Origin city
   - Destination city
   - Specific locations (optional)
   - Base price
   - Description
   - Departure time
4. Click **"Save"**
5. System automatically generates transfer instances for next 30 days

#### Managing Content

1. Go to **Profile** page
2. View your flights/transfers in **"My Content"** section
3. **Edit**: Click edit button, modify fields, save
4. **Delete**: Click delete button, confirm (soft delete)
5. **Promote**: Toggle promotion badge

#### Dashboard

1. Navigate to **Dashboard**
2. View statistics for your flights and transfers
3. View bookings
4. Filter and search bookings

### Common Operations

#### Searching

1. Use the **search bar** at the top of any page
2. Select content type (hotels, flights, etc.)
3. Type keywords
4. View suggestions dropdown
5. Click suggestion or press Enter to search

#### Filtering

1. On listing pages (hotels, activities, destinations), use the **filter section**
2. Select **Province** (optional)
3. Select **City** (optional, depends on province)
4. Enter **Price Range** (min/max)
5. Enter **Keyword** search
6. Click **"Filter"** button
7. Click **"Clear"** to reset filters

#### Image Upload

1. When creating/editing items, use the **image upload section**
2. Click to select files or drag and drop
3. Supported formats: JPEG, PNG, WebP
4. Maximum size: 5MB per image
5. Multiple images can be uploaded
6. Reorder images by dragging
7. Remove images by clicking X

#### Authentication

- **Login**: Email and password
- **Logout**: Click logout button (clears session)
- **Token Refresh**: Automatic on API calls
- **Session Duration**: 24 hours (configurable)

### Troubleshooting

#### Common Issues

1. **"Database connection failed"**
   - Check `api/config.php` database credentials
   - Verify SQL Server is running
   - Check ODBC driver is installed

2. **"Unauthorized" errors**
   - Ensure you're logged in
   - Check token hasn't expired
   - Try logging out and back in

3. **Images not uploading**
   - Check `public/uploads/` permissions
   - Verify file size is under 5MB
   - Check file type is allowed (JPEG, PNG, WebP)

4. **404 errors on API calls**
   - Verify web server rewrite rules are configured
   - Check API routes in `api/index.php`
   - Ensure `.htaccess` (Apache) or nginx config is correct

5. **CORS errors**
   - Check `CORS_ALLOWED_ORIGINS` in `api/config.php`
   - Verify `api/bootstrap.php` CORS headers

---

## API Documentation

### Base URL
All API endpoints are prefixed with `/api/`. For example:
- `http://localhost/travel-booking/api/auth/login`

### Authentication
Most endpoints require JWT authentication. Include the token in the `Authorization` header:
```
Authorization: Bearer <your-jwt-token>
```

### Response Format
All responses are JSON. Success responses follow this format:
```json
{
  "success": true,
  "data": { ... }
}
```

Error responses:
```json
{
  "error": "Error message"
}
```

---

### Authentication Endpoints

#### `POST /api/auth/register`
Register a new user account.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "first_name": "John",
  "last_name": "Doe",
  "role": "customer"
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "role": "customer"
    },
    "token": "jwt-token-here"
  }
}
```

**Roles:** `customer`, `owner`, `agency`

---

#### `POST /api/auth/login`
Authenticate and receive JWT token.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "user": { ... },
    "token": "jwt-token-here"
  }
}
```

---

#### `POST /api/auth/logout`
Revoke current JWT token.

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "message": "Logged out successfully"
  }
}
```

---

#### `GET /api/auth/me`
Get current authenticated user information.

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "role": "customer"
    }
  }
}
```

---

#### `POST /api/auth/refresh`
Refresh JWT token (get new token, revoke old one).

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "token": "new-jwt-token-here"
  }
}
```

---

### Hotels Endpoints

#### `GET /api/hotels`
List all hotels with optional filters.

**Query Parameters:**
- `id` (int, optional): Get single hotel by ID
- `destination_id` (int, optional): Filter by destination
- `province_id` (int, optional): Filter by province
- `city_id` (int, optional): Filter by city
- `min_price` (float, optional): Minimum price per night
- `max_price` (float, optional): Maximum price per night
- `keyword` (string, optional): Search in name, description, city, province
- `page` (int, default: 1): Page number
- `limit` (int, default: 20): Items per page

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "page": 1,
    "limit": 20,
    "promoted": [
      {
        "id": 1,
        "name": "Hotel Name",
        "price_per_night": 1500.00,
        "city_name": "Manila",
        "province_name": "Metro Manila",
        "image_url": "/uploads/hotels/1/image.jpg",
        "ad": 1,
        "discount_percent": 10.00,
        "discounted_price": 1350.00
      }
    ],
    "results": [ ... ]
  }
}
```

**Note:** Soft-deleted hotels are excluded from results.

---

#### `GET /api/hotels?id=X`
Get single hotel by ID.

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "hotel": {
      "id": 1,
      "name": "Hotel Name",
      "price_per_night": 1500.00,
      "description": "...",
      "city_name": "Manila",
      "province_name": "Metro Manila",
      "images": ["/uploads/hotels/1/img1.jpg", ...],
      "image_url": "/uploads/hotels/1/img1.jpg"
    }
  }
}
```

---

#### `POST /api/hotels`
Create a new hotel. **Requires:** `owner` or `admin` role.

**Headers:** `Authorization: Bearer <token>`

**Request Body:**
```json
{
  "name": "Hotel Name",
  "destination_id": 1,
  "city_id": 1,
  "province_id": 1,
  "price_per_night": 1500.00,
  "description": "Hotel description",
  "images": ["/uploads/temp/img1.jpg", ...]
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "data": {
    "hotel": { ... }
  }
}
```

---

#### `PUT /api/hotels/:id`
Update a hotel. **Requires:** `owner` or `admin` role (must own the hotel unless admin).

**Headers:** `Authorization: Bearer <token>`

**Request Body:** (all fields optional)
```json
{
  "name": "Updated Name",
  "price_per_night": 1600.00,
  "description": "Updated description",
  "images": ["/uploads/hotels/1/new-img.jpg", ...]
}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "hotel": { ... }
  }
}
```

---

#### `DELETE /api/hotels/:id`
Soft delete a hotel. **Requires:** `owner` or `admin` role (must own the hotel unless admin).

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "message": "Hotel deleted successfully"
  }
}
```

**Note:** Soft delete - hotel is hidden from listings but not removed from database.

---

### Activities Endpoints

#### `GET /api/activities`
List all activities with optional filters.

**Query Parameters:** Same as hotels (province_id, city_id, min_price, max_price, keyword, page, limit)

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "page": 1,
    "limit": 20,
    "promoted": [ ... ],
    "results": [ ... ]
  }
}
```

---

#### `GET /api/activities/:id`
Get single activity by ID.

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "activity": {
      "id": 1,
      "title": "Activity Title",
      "price": 500.00,
      "description": "...",
      "city_name": "Manila",
      "province_name": "Metro Manila",
      "images": [ ... ]
    }
  }
}
```

---

#### `POST /api/activities`
Create a new activity. **Requires:** `agency` or `admin` role.

**Headers:** `Authorization: Bearer <token>`

**Request Body:**
```json
{
  "title": "Activity Title",
  "destination_id": 1,
  "city_id": 1,
  "price": 500.00,
  "description": "Activity description",
  "images": [ ... ]
}
```

**Response:** `201 Created`

---

#### `PUT /api/activities/:id`
Update an activity. **Requires:** `agency` or `admin` role (must own the activity unless admin).

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`

---

#### `DELETE /api/activities/:id`
Soft delete an activity. **Requires:** `agency` or `admin` role (must own the activity unless admin).

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`

---

### Flight Endpoints

#### `GET /api/flights/routes`
List all flight routes.

**Query Parameters:**
- `id` (int, optional): Get single route by ID
- `origin_airport_id` (int, optional): Filter by origin airport
- `destination_airport_id` (int, optional): Filter by destination airport
- `airline` (string, optional): Filter by airline
- `q` (string, optional): Search in airline, airport names
- `createdBy` (int, optional): Filter by creator

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 1,
        "airline": "Philippine Airlines",
        "origin_code": "MNL",
        "origin_name": "Manila Airport",
        "destination_code": "CEB",
        "destination_name": "Cebu Airport",
        "base_price_economy": 5000.00,
        "base_price_business": 10000.00,
        "base_price_first": 15000.00
      }
    ]
  }
}
```

---

#### `GET /api/flights/routes/:id`
Get single flight route with full details.

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "route": {
      "id": 1,
      "airline": "Philippine Airlines",
      "origin_code": "MNL",
      "origin_city_name": "Manila",
      "origin_province_name": "Metro Manila",
      "destination_code": "CEB",
      "destination_city_name": "Cebu",
      "destination_province_name": "Cebu",
      "base_price_economy": 5000.00,
      "departure_time": "08:00"
    }
  }
}
```

---

#### `POST /api/flights/routes`
Create a new flight route. **Requires:** `agency` or `admin` role.

**Headers:** `Authorization: Bearer <token>`

**Request Body:**
```json
{
  "origin_province_id": 1,
  "origin_city_id": 1,
  "destination_province_id": 2,
  "destination_city_id": 2,
  "airline": "Philippine Airlines",
  "base_price_economy": 5000.00,
  "base_price_business": 10000.00,
  "base_price_first": 15000.00,
  "aircraft_type": "Boeing 737",
  "departure_time": "08:00",
  "days_of_week": "0,1,2,3,4,5,6",
  "ad": false,
  "discount_percent": 0
}
```

**Response:** `201 Created`

**Note:** Automatically creates schedule and generates flight instances for next 30 days.

---

#### `PUT /api/flights/routes/:id`
Update a flight route. **Requires:** `agency` or `admin` role (must own the route unless admin).

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`

---

#### `DELETE /api/flights/routes/:id`
Delete a flight route. **Requires:** `agency` or `admin` role (must own the route unless admin).

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`

**Note:** Cannot delete if route has associated schedules/instances.

---

#### `GET /api/flights/availability`
Get available dates for a flight route.

**Query Parameters:**
- `route_id` (int, required): Flight route ID
- `return` (boolean, optional): If true, get return route availability

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "available_dates": ["2025-01-20", "2025-01-21", ...]
  }
}
```

---

#### `GET /api/flights/instances`
Get flight instances for a specific date and route.

**Query Parameters:**
- `route_id` (int, required): Flight route ID
- `departure_date` (date, required): Format YYYY-MM-DD
- `return_date` (date, optional): For round-trip
- `class` (string, default: "economy"): economy, business, first
- `passenger_count` (int, default: 1): Number of passengers

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "outbound": {
      "instance": {
        "id": 1,
        "departure_datetime": "2025-01-20 08:00:00",
        "price_economy": 5000.00,
        "seats_economy_available": 150
      },
      "price": 5000.00
    },
    "return": { ... } // if return_date provided
  }
}
```

---

#### `POST /api/flights/book`
Book a flight. **Requires:** `customer` role.

**Headers:** `Authorization: Bearer <token>`

**Request Body:**
```json
{
  "instance_id": 1,
  "class": "economy",
  "passenger_count": 2
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "data": {
    "booking": {
      "id": 1,
      "instance_id": 1,
      "class": "economy",
      "passenger_count": 2,
      "total_price": 10000.00,
      "status": "confirmed"
    }
  }
}
```

---

### Transfer Endpoints

#### `GET /api/transfers/routes`
List all transfer routes.

**Query Parameters:**
- `id` (int, optional): Get single route by ID
- `origin_city_id` (int, optional): Filter by origin city
- `destination_city_id` (int, optional): Filter by destination city
- `q` (string, optional): Search in origin/destination cities, specific locations
- `createdBy` (int, optional): Filter by creator

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 1,
        "origin_city_name": "Manila",
        "destination_city_name": "Baguio",
        "origin_specific": "Manila Airport",
        "destination_specific": "Baguio Terminal",
        "base_price": 800.00
      }
    ]
  }
}
```

---

#### `GET /api/transfers/routes/:id`
Get single transfer route with full details.

**Response:** `200 OK`

---

#### `POST /api/transfers/routes`
Create a new transfer route. **Requires:** `agency` or `admin` role.

**Headers:** `Authorization: Bearer <token>`

**Request Body:**
```json
{
  "origin_city_id": 1,
  "destination_city_id": 2,
  "origin_specific": "Manila Airport",
  "destination_specific": "Baguio Terminal",
  "base_price": 800.00,
  "description": "Transfer description",
  "departure_time": "09:00",
  "days_of_week": "0,1,2,3,4,5,6",
  "ad": false,
  "discount_percent": 0
}
```

**Response:** `201 Created`

**Note:** Automatically creates schedule and generates transfer instances for next 30 days.

---

#### `PUT /api/transfers/routes/:id`
Update a transfer route. **Requires:** `agency` or `admin` role (must own the route unless admin).

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`

---

#### `DELETE /api/transfers/routes/:id`
Delete a transfer route. **Requires:** `agency` or `admin` role (must own the route unless admin).

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`

---

#### `GET /api/transfers/availability`
Get available dates for a transfer route.

**Query Parameters:**
- `transfer_id` (int, required): Transfer route ID

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "available_dates": ["2025-01-20", "2025-01-21", ...]
  }
}
```

---

#### `GET /api/transfer-instances`
Get transfer instances for a specific date and route.

**Query Parameters:**
- `transfer_id` (int, required): Transfer route ID
- `date` (date, required): Format YYYY-MM-DD
- `passenger_count` (int, default: 1): Number of passengers

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "instances": [
      {
        "id": 1,
        "departure_datetime": "2025-01-20 09:00:00",
        "price": 800.00,
        "seats_available": 30
      }
    ]
  }
}
```

---

#### `POST /api/transfers/book`
Book a transfer. **Requires:** `customer` role.

**Headers:** `Authorization: Bearer <token>`

**Request Body:**
```json
{
  "instance_id": 1,
  "passenger_count": 2
}
```

**Response:** `201 Created`

---

### Booking Endpoints

#### `GET /api/bookings`
Get user's bookings. **Requires:** Authentication (customers only).

**Headers:** `Authorization: Bearer <token>`

**Query Parameters:**
- `id` (int, optional): Get single booking by ID
- `item_type` (string, optional): Filter by type (hotel, flight, activity, transfer)
- `status` (string, optional): Filter by status (confirmed, cancelled, completed)
- `page` (int, default: 1): Page number
- `limit` (int, default: 10): Items per page

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "page": 1,
    "limit": 10,
    "total": 5,
    "results": [
      {
        "id": 1,
        "type": "hotel",
        "item_id": 1,
        "item_details": {
          "name": "Hotel Name",
          "city_name": "Manila"
        },
        "check_in": "2025-01-20",
        "check_out": "2025-01-22",
        "guests": 2,
        "total_price": 3000.00,
        "status": "confirmed",
        "booked_at": "2025-01-15T10:00:00Z"
      }
    ]
  }
}
```

**Note:** Only customers can access this endpoint. Owners and agencies cannot access bookings via this endpoint.

---

#### `POST /api/bookings`
Create a new booking. **Requires:** `customer` role.

**Headers:** `Authorization: Bearer <token>`

**Request Body (Hotel):**
```json
{
  "item_type": "hotel",
  "item_id": 1,
  "check_in": "2025-01-20",
  "check_out": "2025-01-22",
  "guests": 2,
  "total_price": 3000.00
}
```

**Request Body (Flight):**
```json
{
  "item_type": "flight",
  "item_id": 1,
  "class": "economy",
  "passenger_count": 2,
  "total_price": 10000.00
}
```

**Request Body (Activity):**
```json
{
  "item_type": "activity",
  "item_id": 1,
  "date": "2025-01-20",
  "participant_count": 2,
  "total_price": 1000.00
}
```

**Request Body (Transfer):**
```json
{
  "item_type": "transfer",
  "item_id": 1,
  "passenger_count": 2,
  "total_price": 1600.00
}
```

**Response:** `201 Created`

---

#### `PUT /api/bookings/:id`
Update booking status. **Requires:** Authentication (customer can update own bookings, owner/agency can update bookings for their items).

**Headers:** `Authorization: Bearer <token>`

**Request Body:**
```json
{
  "status": "cancelled"
}
```

**Valid statuses:** `confirmed`, `cancelled`, `completed`

**Response:** `200 OK`

---

#### `DELETE /api/bookings/:id`
Cancel a booking (sets status to cancelled). **Requires:** Authentication (customer can cancel own bookings).

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`

---

### Profile Endpoints

#### `GET /api/profile`
Get user profile with all created content. **Requires:** Authentication.

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "role": "owner"
    },
    "bookings": {
      "total": 5,
      "confirmed": 3,
      "cancelled": 1,
      "completed": 1
    },
    "promoted": {
      "hotels": [ ... ],
      "flights": [ ... ],
      "activities": [ ... ],
      "transfers": [ ... ]
    },
    "all": {
      "hotels": [ ... ],
      "flights": [ ... ],
      "activities": [ ... ],
      "transfers": [ ... ]
    }
  }
}
```

**Note:** Content is sorted with promoted items first, then by creation date.

---

### Dashboard Endpoints

#### `GET /api/dashboard`
Get dashboard statistics. **Requires:** `owner` or `agency` role.

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "stats": {
      "total_bookings": 50,
      "total_revenue": 150000.00,
      "hotels_count": 10,
      "flights_count": 5,
      "activities_count": 8,
      "transfers_count": 3,
      "bookings_by_status": {
        "confirmed": 30,
        "cancelled": 5,
        "completed": 15
      }
    }
  }
}
```

---

#### `GET /api/dashboard/bookings`
Get bookings for owner/agency's items. **Requires:** `owner` or `agency` role.

**Headers:** `Authorization: Bearer <token>`

**Query Parameters:**
- `item_type` (string, optional): Filter by type (hotel, flight, activity, transfer)
- `status` (string, optional): Filter by status
- `customer_name` (string, optional): Search by customer name
- `keyword` (string, optional): Search in item details
- `page` (int, default: 1)
- `limit` (int, default: 20)

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "page": 1,
    "limit": 20,
    "total": 50,
    "results": [ ... ]
  }
}
```

---

#### `GET /api/dashboard/items`
Get all items created by owner/agency. **Requires:** `owner` or `agency` role.

**Headers:** `Authorization: Bearer <token>`

**Query Parameters:**
- `item_type` (string, optional): Filter by type (hotel, flight, activity, transfer)

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "hotels": [ ... ],
    "flights": [ ... ],
    "activities": [ ... ],
    "transfers": [ ... ]
  }
}
```

---

### Search Endpoints

#### `GET /api/search`
Global search across all content types.

**Query Parameters:**
- `q` (string, required): Search query
- `type` (string, optional): Filter by type (`hotels`, `flights`, `destinations`, `transfers`, `activities`, `all`). Default: `all`

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "query": "manila",
    "results": {
      "hotels": [ ... ],
      "flights": [ ... ],
      "destinations": [ ... ],
      "transfers": [ ... ],
      "activities": [ ... ]
    }
  }
}
```

**Note:** Searches in names, descriptions, locations, and other relevant fields. Case-insensitive.

---

### Top/Featured Endpoints

#### `GET /api/top/hotels`
Get top hotels based on booking count and recency.

**Query Parameters:**
- `limit` (int, default: 4, max: 10): Number of results

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "results": [ ... ]
  }
}
```

**Note:** Uses weighted scoring: `(booking_count * 0.6) + (days_since_created_penalty * 0.4)`

---

#### `GET /api/top/flights`
Get top flights based on booking count.

**Query Parameters:**
- `limit` (int, default: 4, max: 10): Number of results

**Response:** `200 OK`

---

#### `GET /api/top/activities`
Get top activities based on booking count and recency.

**Query Parameters:**
- `limit` (int, default: 4, max: 10): Number of results

**Response:** `200 OK`

---

### Location Endpoints

#### `GET /api/provinces`
List all provinces.

**Query Parameters:**
- `id` (int, optional): Get single province by ID
- `region` (string, optional): Filter by region

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 1,
        "name": "Metro Manila",
        "code": "NCR",
        "region": "National Capital Region"
      }
    ]
  }
}
```

---

#### `GET /api/provinces/:id`
Get single province by ID.

**Response:** `200 OK`

---

#### `GET /api/cities`
List all cities.

**Query Parameters:**
- `id` (int, optional): Get single city by ID
- `province_id` (int, optional): Filter by province

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 1,
        "name": "Manila",
        "province_id": 1,
        "province_name": "Metro Manila",
        "region": "National Capital Region"
      }
    ]
  }
}
```

---

#### `GET /api/cities/:id`
Get single city by ID.

**Response:** `200 OK`

---

### Airport Endpoints

#### `GET /api/airports`
List all airports.

**Query Parameters:**
- `id` (int, optional): Get single airport by ID
- `city_id` (int, optional): Filter by city
- `q` (string, optional): Search in code or name

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 1,
        "code": "MNL",
        "name": "Ninoy Aquino International Airport",
        "city_id": 1,
        "city_name": "Manila",
        "province_name": "Metro Manila",
        "is_international": 1
      }
    ]
  }
}
```

---

#### `POST /api/airports`
Create a new airport. **Requires:** `admin` role.

**Headers:** `Authorization: Bearer <token>`

**Request Body:**
```json
{
  "code": "MNL",
  "name": "Ninoy Aquino International Airport",
  "city_id": 1,
  "is_international": true
}
```

**Response:** `201 Created`

---

#### `PUT /api/airports/:id`
Update an airport. **Requires:** `admin` role.

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`

---

#### `DELETE /api/airports/:id`
Delete an airport. **Requires:** `admin` role.

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`

**Note:** Cannot delete if airport is used in flight routes.

---

### Upload Endpoints

#### `POST /api/upload`
Upload an image file.

**Headers:** `Authorization: Bearer <token>`

**Request:** `multipart/form-data`
- `image` (file, required): Image file (JPEG, PNG, WebP)
- `category` (string, required): `general`, `hotel`, `flight`, `activity`, `transfer`, `destination`
- `entity_type` (string, optional): Entity type if saving to specific entity
- `entity_id` (int, optional): Entity ID if saving to specific entity

**Response:** `201 Created`
```json
{
  "success": true,
  "data": {
    "url": "/travel-booking/uploads/hotels/1/image_abc123.jpg",
    "filename": "image_abc123.jpg"
  }
}
```

**Note:**
- Maximum file size: 5MB
- Allowed types: JPEG, PNG, WebP
- If `entity_type` and `entity_id` provided, file is saved to entity folder
- Otherwise, file is saved to `temp/` folder for later association

---

### Destination Endpoints

#### `GET /api/destinations`
List all destinations.

**Query Parameters:**
- `id` (int, optional): Get single destination by ID
- `province_id` (int, optional): Filter by province
- `city_id` (int, optional): Filter by city
- `keyword` (string, optional): Search in name or description
- `page` (int, default: 1)
- `limit` (int, default: 20)

**Response:** `200 OK`

---

#### `GET /api/destinations/:id`
Get single destination by ID.

**Response:** `200 OK`

---

#### `POST /api/destinations`
Create a new destination. **Requires:** `admin` role.

**Headers:** `Authorization: Bearer <token>`

**Request Body:**
```json
{
  "name": "Boracay",
  "description": "Beautiful island destination",
  "images": [ ... ]
}
```

**Response:** `201 Created`

---

#### `PUT /api/destinations/:id`
Update a destination. **Requires:** `admin` role.

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`

---

#### `DELETE /api/destinations/:id`
Delete a destination. **Requires:** `admin` role.

**Headers:** `Authorization: Bearer <token>`

**Response:** `200 OK`

**Note:** Cannot delete if destination is used by hotels or activities.

---

**Last Updated**: 2025-01-15
**Version**: 1.0

