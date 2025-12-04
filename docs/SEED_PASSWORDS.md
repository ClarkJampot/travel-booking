# Seed Data Passwords

This document contains the unhashed passwords for all seed users in the database.

**⚠️ SECURITY WARNING:** These passwords are for development/testing purposes only. **NEVER use these passwords in production environments.** All passwords must be changed immediately after deployment.

## Password Hashing Method

All passwords in the seed data are hashed using PHP's `password_hash()` function with `PASSWORD_DEFAULT` algorithm (bcrypt).

**Hash used for all seed users:** `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`

## Seed User Passwords

### All Users
**Password:** `password123`

This password is shared by all seed users for easy testing. In production, each user should have a unique, strong password.

---

## Users by Role

### Customers (3 users)

| Email | Password | Role | Notes |
|-------|----------|------|-------|
| `customer1@example.com` | `password123` | customer | Juan Dela Cruz |
| `customer2@example.com` | `password123` | customer | Maria Santos |
| `customer3@example.com` | `password123` | customer | Pedro Garcia |

### Travel Agencies (3 users)

| Email | Password | Role | Notes |
|-------|----------|------|-------|
| `agency1@example.com` | `password123` | agency | Travel Express |
| `agency2@example.com` | `password123` | agency | Adventure Tours |
| `agency3@example.com` | `password123` | agency | Paradise Travel |

### Hotel Owners (3 users)

| Email | Password | Role | Notes |
|-------|----------|------|-------|
| `owner1@example.com` | `password123` | owner | Hotel Magnate |
| `owner2@example.com` | `password123` | owner | Resort Group |
| `owner3@example.com` | `password123` | owner | Luxury Hotels |

### Administrators (2 users)

| Email | Password | Role | Notes |
|-------|----------|------|-------|
| `admin@example.com` | `password123` | admin | System Administrator |
| `admin2@example.com` | `password123` | admin | Site Manager |

---

## Testing

To test login functionality:
1. Use any of the email addresses above
2. Enter password: `password123`
3. The system should authenticate successfully

## Production Deployment

**IMPORTANT:** Before deploying to production:

1. **Change all passwords** - Never use these default passwords in production
2. **Force password reset** - Require all users to change their passwords on first login
3. **Use strong passwords** - Enforce password complexity requirements
4. **Remove this file** - Do not deploy this documentation file to production servers

---

## Ownership Distribution

For reference, here's how entities are distributed across users:

### Hotels
- **Owner 1** (`owner1@example.com`, user_id: 7): Hotels 1-5
- **Owner 2** (`owner2@example.com`, user_id: 8): Hotels 6-10
- **Owner 3** (`owner3@example.com`, user_id: 9): Hotels 11-15

### Flights
- **Agency 1** (`agency1@example.com`, user_id: 4): Flights 1-5
- **Agency 2** (`agency2@example.com`, user_id: 5): Flights 6-10
- **Agency 3** (`agency3@example.com`, user_id: 6): Flights 11-15

### Activities
- **Agency 1** (`agency1@example.com`, user_id: 4): Activities 1-5
- **Agency 2** (`agency2@example.com`, user_id: 5): Activities 6-10
- **Agency 3** (`agency3@example.com`, user_id: 6): Activities 11-15

### Transfers
- **Agency 1** (`agency1@example.com`, user_id: 4): Transfers 1-3
- **Agency 2** (`agency2@example.com`, user_id: 5): Transfers 4-6
- **Agency 3** (`agency3@example.com`, user_id: 6): Transfers 7-9

### Bookings
- **Customer 1** (`customer1@example.com`, user_id: 1): Bookings 1-3
- **Customer 2** (`customer2@example.com`, user_id: 2): Bookings 4-6
- **Customer 3** (`customer3@example.com`, user_id: 3): Bookings 7-9

---

Generated: 2024  
Last Updated: Phase 4 - Complete Seed Data Rewrite



