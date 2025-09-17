# Job Scraper Web Interface Setup

## 1. Database Setup
```bash
# Run migrations
php artisan migrate

# Seed with sample data (optional)
php artisan db:seed --class=JobListingSeeder
```

## 2. Routes Registration
Add routes to your web.php file or create a separate routes file.

## 3. Start the Application
```bash
php artisan serve
```

## 4. Access the Web Interface

### Main Pages:
- **Dashboard**: `http://localhost:8000/jobs/dashboard`
- **Job Listings**: `http://localhost:8000/jobs`
- **Individual Job**: `http://localhost:8000/jobs/show/{id}`

### API Endpoints:
- **Scrape All**: `POST /jobs/scrape`
- **Scrape Company**: `POST /jobs/scrape/{company}`
- **Get Stats**: `GET /jobs/api/stats`
- **Get Logs**: `GET /jobs/api/logs`

## 5. Testing the Scraper

### Via Web Interface:
1. Go to `http://localhost:8000/jobs/dashboard`
2. Click "Scrape All Companies" or individual company buttons
3. Monitor progress in real-time
4. View results in the job listings page

### Via Command Line:
```bash
# Test individual commands
php artisan jobs:scrape --no-save
php artisan jobs:scrape --company=AssureSoft
php artisan jobs:stats
```
