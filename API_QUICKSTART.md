# VyaparCRM Backend API - Quick Start

## Setup

1. Copy `.env.example` to `.env` and configure database:
```bash
cp .env.example .env
```

2. Install dependencies:
```bash
composer install
```

3. Generate app key:
```bash
php artisan key:generate
```

4. Run migrations:
```bash
php artisan migrate
```

5. Seed demo data:
```bash
php artisan db:seed
```

6. Start server:
```bash
php artisan serve
```

## Demo Credentials
- **Email**: `admin@vyaparcrm.local`
- **Password**: `password123`

---

## API Endpoints & Examples

Base URL: `http://localhost:8000/api/v1`

### Authentication

**Login:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@vyaparcrm.local","password":"password123"}'
```

**Get Current User:**
```bash
curl http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Logout:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### Leads

**List Leads:**
```bash
curl "http://localhost:8000/api/v1/leads?page=1&per_page=10&stage=new" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Create Lead:**
```bash
curl -X POST http://localhost:8000/api/v1/leads \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "phone": "9876543210",
    "email": "john@example.com",
    "city": "Mumbai",
    "state": "Maharashtra",
    "source": "website",
    "expected_value": 50000
  }'
```

**Convert Lead to Client:**
```bash
curl -X POST http://localhost:8000/api/v1/leads/1/convert \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "business",
    "business_name": "John Enterprises"
  }'
```

---

### Clients

**List Clients:**
```bash
curl "http://localhost:8000/api/v1/clients?search=john" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Create Client:**
```bash
curl -X POST http://localhost:8000/api/v1/clients \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "business",
    "business_name": "ABC Traders",
    "contact_name": "Rahul Sharma",
    "phone": "9876543210",
    "email": "rahul@abctraders.com",
    "gstin": "27AABCU9603R1ZM",
    "billing_address": {
      "line1": "123 Market Street",
      "city": "Mumbai",
      "state": "Maharashtra",
      "pincode": "400001"
    }
  }'
```

---

### Quotes

**Create Quote:**
```bash
curl -X POST http://localhost:8000/api/v1/quotes \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": 1,
    "valid_till": "2025-01-31",
    "items": [
      {
        "product_name": "Steel Pipe 2inch",
        "qty": 100,
        "unit_price": 250,
        "gst_percent": 18,
        "unit": "Mtr"
      }
    ]
  }'
```

**Update Quote Status:**
```bash
curl -X POST http://localhost:8000/api/v1/quotes/1/status \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"sent"}'
```

---

### Products

**Create Product:**
```bash
curl -X POST http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "sku": "PIPE-STL-2IN",
    "name": "Steel Pipe 2 inch",
    "sale_price": 250,
    "gst_percent": 18,
    "unit": "Mtr",
    "stock_qty": 500
  }'
```

---

### Reports

**Dashboard Stats:**
```bash
curl http://localhost:8000/api/v1/reports/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Leads Report:**
```bash
curl "http://localhost:8000/api/v1/reports/leads?from_date=2024-12-01&to_date=2024-12-31" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Webhook Testing

### Facebook Webhook Verification
```bash
curl "http://localhost:8000/api/v1/webhooks/facebook?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=test123"
```

### Facebook Webhook Test Payload
```bash
curl -X POST http://localhost:8000/api/v1/webhooks/facebook \
  -H "Content-Type: application/json" \
  -d '{
    "object": "page",
    "entry": [{
      "id": "YOUR_PAGE_ID",
      "time": 1703376000,
      "changes": [{
        "field": "leadgen",
        "value": {
          "leadgen_id": "1234567890",
          "page_id": "YOUR_PAGE_ID",
          "form_id": "YOUR_FORM_ID"
        }
      }]
    }]
  }'
```

---

## Artisan Commands

**Pull IndiaMART Leads (manual):**
```bash
php artisan indiamart:pull-leads
# or for specific company
php artisan indiamart:pull-leads 1
```

**Backfill Facebook Leads:**
```bash
php artisan facebook:backfill-leads
# or with date filter
php artisan facebook:backfill-leads --since=2024-12-01
```

---

## Queue Worker

Start the queue worker for async job processing:
```bash
php artisan queue:work
```

## Scheduler

Run the scheduler for IndiaMART auto-pull:
```bash
php artisan schedule:work
```
