# Seed Database Credentials

## Default Password
**All seed users have the same password:**
- Password: `password123`

## User Accounts by Role

### Customers (Role: customer)
1. **Email:** customer1@example.com
   - Name: Juan Dela Cruz
   
2. **Email:** customer2@example.com
   - Name: Maria Santos
   
3. **Email:** customer3@example.com
   - Name: Pedro Garcia

### Travel Agencies (Role: agency)
1. **Email:** agency1@example.com
   - Business Name: Travel Express
   
2. **Email:** agency2@example.com
   - Business Name: Adventure Tours
   
3. **Email:** agency3@example.com
   - Business Name: Paradise Travel

### Hotel Owners (Role: owner)
1. **Email:** owner1@example.com
   - Business Name: Hotel Magnate
   
2. **Email:** owner2@example.com
   - Business Name: Resort Group
   
3. **Email:** owner3@example.com
   - Business Name: Luxury Hotels

### Administrators (Role: admin)
1. **Email:** admin@example.com
   - Name: System Administrator
   
2. **Email:** admin2@example.com
   - Name: Site Manager

---

**Note:** All passwords are hashed using PHP `password_hash()` with `PASSWORD_DEFAULT`. The hash stored in the database is: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`

