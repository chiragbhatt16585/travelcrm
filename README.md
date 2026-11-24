# Travel World – PHP + MySQL setup

## Requirements
- PHP 8+ with PDO MySQL
- MySQL 8+

## Database config
Edit `config/database.php` if needed. Current local defaults:
- DB: `travelworld`
- User: `travelworld`
- Password: `travelworld`

## Setup
```bash
mysql -u travelworld -ptravelworld < database/schema.sql
php database/seed.php
php -S 127.0.0.1:8000 router.php
```

## Admin login
- URL: http://localhost:8000/admin/login.php
- Username: `admin`
- Password: `admin123`

## What admin can manage
- Destinations (domestic / international)
- Packages (itinerary, hotels, inclusions, exclusions, price, images)

Frontend home, destination and package pages load live data from MySQL.
