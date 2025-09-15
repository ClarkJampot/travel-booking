## Project Index

This document provides a concise overview of the travel booking website project: structure, setup, scripts, APIs, and roadmap. It is meant for students and beginners.

---

### 1) Tech Stack
- Backend: Node.js (Express)
- Database: MySQL or MariaDB
- Frontend: HTML, CSS, JavaScript (no frameworks)
- Style: Minimal dependencies, beginner-friendly code

---

### 2) Repository Structure
```
travel-booking/
├─ backend/                 # Express server, routes, DB access
│  ├─ models/
│  │  └─ db.js             # MySQL connector (promise-based)
│  ├─ routes/
│  │  ├─ index.js          # API router root, mounted at /api
│  │  └─ hotels.js         # GET /api/hotels
│  ├─ scripts/
│  │  └─ db.js             # db:init, db:seed, db:reset helpers
│  ├─ package.json
│  ├─ package-lock.json
│  └─ server.js            # Express app entry
├─ public/                  # Static frontend assets
│  ├─ index.html
│  └─ js/
│     └─ main.js
├─ docs/
│  └─ PROJECT_INDEX.md      # This file
├─ LICENSE
└─ README.md
```

Planned (may exist outside this snapshot):
- `database/` with `schema.sql` and `seed.sql`
- `.env` and `.env.example` for configuration

---

### 3) Environment Variables
Create `.env` in `backend/`:
```
PORT=3000
DB_HOST=localhost
DB_USER=your_user
DB_PASS=your_password
DB_NAME=travel_booking
```

Optional: add `.env.example` with the same keys and sample values.

---

### 4) Install and Run
From project root:
```
cd backend
npm install
npm start
```
Server runs at `http://localhost:3000` and serves static files from `public/`.

---

### 5) Database Management
Script: `backend/scripts/db.js` provides helpers for init/seed/reset. Typical flow:
```
# Initialize schema (requires database/schema.sql)
node scripts/db.js init

# Seed data (requires database/seed.sql)
node scripts/db.js seed

# Reset (drop + recreate + seed)
node scripts/db.js reset
```
Note: Ensure MySQL is running and `.env` DB settings are correct. Place `schema.sql` and `seed.sql` under a `database/` directory at repo root.

---

### 6) API Overview
Base URL: `/api`

Implemented:
- `GET /api/hotels` → list all hotels

Planned routes:
- Flights: `GET /api/flights`, `POST /api/flights` (admin/agency)
- Activities: `GET /api/activities`, `POST /api/activities`
- Transfers: `GET /api/transfers`, `POST /api/transfers`
- Bookings: `GET /api/bookings`, `POST /api/bookings`, `GET /api/bookings/:id`
- Admin/Management: CRUD for listings, approvals, basic ads management

Response format: JSON. Use query params for filters (e.g., dates, city, price range) in future endpoints.

---

### 7) Frontend Pages (Static)
- `public/index.html` → landing page with links to sections
- Future pages:
  - `public/hotels.html` + `public/js/hotels.js` to fetch `/api/hotels`
  - `public/flights.html`, `public/activities.html`, `public/transfers.html`
  - `public/bookings.html` for viewing basic booking info

Rules: Use plain HTML/CSS/JS, fetch API endpoints, and render lists/cards. Keep code simple, commented sparingly, and readable.

---

### 8) Coding Conventions
- JavaScript: use meaningful names, early returns, minimal nesting
- API: `express.json()` body parsing, async/await, try/catch with 500 on errors
- DB: use parameterized queries with `models/db.js`
- Linting: keep code consistent and beginner-friendly (no advanced patterns)

---

### 9) Roadmap
1. Add routes: flights, activities, transfers, bookings
2. Add admin/management endpoints (listings CRUD, simple ads)
3. Create basic frontend pages to consume APIs
4. Add `database/schema.sql` and `database/seed.sql` if missing
5. Provide docs: user guide, admin guide, technical setup

---

### 10) Testing Quick Check
- Start server and hit `GET http://localhost:3000/api/hotels`
- Open `http://localhost:3000/` to view the static site


