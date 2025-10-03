# Travel Booking (PHP + SQL Server)

A school project using PHP under XAMPP and Microsoft SQL Server Express. This repo provides a simple backend API (plain PHP) and static frontend pages (HTML/CSS/JS) with no frameworks.

---

## Stack
- Backend: PHP 8.x (XAMPP Apache)
- Database: Microsoft SQL Server Express
- PHP drivers: Microsoft SQLSRV + PDO_SQLSRV
- Frontend: HTML/CSS/JS (no frameworks)

---

## Local Setup (Windows)

1) SQL Server Express + SSMS
- Enable TCP/IP in SQL Server Configuration Manager; restart service
- Create database: TRAVEL_BOOKING
- Create SQL Login with db_owner on TRAVEL_BOOKING

2) XAMPP (Apache + PHP 8.x)
- Install and start Apache

3) Microsoft PHP drivers for SQL Server
- Copy `php_sqlsrv.dll` and `php_pdo_sqlsrv.dll` to `xampp/php/ext/`
- In `xampp/php/php.ini` add:
  - `extension=php_sqlsrv.dll`
  - `extension=php_pdo_sqlsrv.dll`
- Restart Apache; verify via phpinfo()

4) Place project
- Path: `C:/xampp/htdocs/travel-booking`
- Frontend: `http://localhost/travel-booking/public/`
- API base: `http://localhost/travel-booking/api/`

5) Configuration
- Create `api/config.php` with DB credentials (to be added during scaffold):
  - DSN: `sqlsrv:Server=HOST,PORT;Database=TRAVEL_BOOKING`
  - USER/PASS: your SQL Login

6) Database
- `database/schema.sql` and `database/seed.sql` (T-SQL)
- Apply via SSMS or `sqlcmd`:
  - `sqlcmd -S localhost -d TRAVEL_BOOKING -U <user> -P <pass> -i database\schema.sql`

---

## Project Structure (planned)
```
travel-booking/
├─ api/                     # PHP API (router, controllers, db, middleware)
│  ├─ index.php             # Front controller
│  ├─ bootstrap.php         # session_start, headers
│  ├─ config.php            # DB creds (not committed)
│  ├─ db.php                # PDO SQLSRV
│  ├─ middleware/
│  │  ├─ auth.php           # requireAuth, requireRole
│  │  └─ validate.php       # requireFields, validators
│  └─ controllers/
│     ├─ auth.php           # register, login, logout, me
│     ├─ hotels.php         # GET/POST with filters
│     ├─ flights.php        # GET/POST
│     ├─ activities.php     # GET/POST
│     ├─ transfers.php      # GET/POST
│     ├─ bookings.php       # GET (list/id), POST
│     └─ ads.php            # GET
├─ public/                  # Static frontend (HTML/CSS/JS)
│  ├─ index.html            # Home + nav
│  ├─ css/
│  ├─ js/
│  ├─ hotels.html
│  ├─ flights.html
│  ├─ activities.html
│  ├─ transfers.html
│  └─ bookings.html
├─ database/
│  ├─ schema.sql            # SQL Server schema
│  └─ seed.sql              # Demo data
├─ .gitignore
└─ README.md
```

---

## API (high-level)
- Auth: POST `/api/auth/register`, `/api/auth/login`, `/api/auth/logout`; GET `/api/auth/me`
- Hotels: GET `/api/hotels?...` (filters + pagination), POST `/api/hotels` (owner/admin)
- Flights/Activities/Transfers: GET filters + pagination; POST (agency/admin)
- Bookings: GET `/api/bookings` and `/api/bookings/:id`; POST `/api/bookings` (requireAuth)
- Ads: GET `/api/ads?placement=`

JSON responses; errors: `{ "error": "message" }` with proper status codes.

---

## Workflow
- Enable SQLSRV/PDO_SQLSRV in PHP and verify with phpinfo()
- Create `api/config.php` with DB credentials
- Apply `database/schema.sql` and `database/seed.sql`
- Implement `api/` scaffold (auth + hotels first)
- Hook up `public/` pages to endpoints

---

## Notes
- Framework-free for clarity; not production-hardened