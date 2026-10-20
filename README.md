# SpoofShield — AI-Assisted DNS and Email Spoofing Investigation System

SpoofShield is a cybersecurity platform consisting of a **web dashboard** and a **Chrome/Edge browser extension** for investigating suspicious websites, DNS records, and email headers.

## Project Structure

```
SpoofShield/
├── api/                    # PHP backend API endpoints
│   ├── config.php          # Database configuration
│   ├── dns_scan.php        # DNS investigation endpoint
│   ├── email_analyze.php   # Email header analysis endpoint
│   ├── health.php          # Health check
│   ├── history.php         # Investigation history
│   └── report.php          # Report retrieval
├── browser-extension/      # Chrome/Edge extension (Manifest V3)
│   ├── manifest.json       # Extension manifest
│   ├── popup.html          # Extension popup UI
│   ├── popup.css           # Popup styles
│   └── popup.js            # Popup logic
├── database/
│   └── schema.sql          # MySQL database schema
├── web/                    # Frontend dashboard
│   ├── index.html          # Dashboard overview
│   ├── dns.html            # DNS Scanner
│   ├── email.html          # Email Analyzer
│   ├── history.html        # Investigation History
│   ├── reports.html        # Reports Viewer
│   └── styles.css          # Shared styles
├── docs/                   # Documentation
└── tests/                  # Test scripts
```

## Prerequisites

- **PHP 8.0+** with PDO MySQL extension
- **MySQL/MariaDB** database
- **XAMPP** (recommended for Windows) or similar local server
- **Chrome/Edge** browser for extension testing

## Installation & Setup

### 1. Database Setup

1. Start MySQL (via XAMPP Control Panel or service)
2. Import the schema:
   ```bash
   mysql -u root < database/schema.sql
   ```
   Or use phpMyAdmin to run `database/schema.sql`

### 2. PHP Backend Configuration

Edit `api/config.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'spoofshield');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your MySQL password
```

### 3. Run PHP Development Server

From the `api/` directory:
```bash
cd api
php -S localhost:8000
```

The API will be available at `http://localhost:8000/`

### 4. Web Dashboard

Serve the `web/` directory:
```bash
cd web
php -S localhost:8001
```
Open `http://localhost:8001/index.html`

**Note:** Update API URLs in HTML files if using different ports. Currently they use relative paths (`../api/...`) which work when both are served from the same domain root.

### 5. Chrome/Edge Extension Installation

1. Open `chrome://extensions/` (or `edge://extensions/`)
2. Enable **Developer mode** (top right)
3. Click **Load unpacked**
4. Select the `browser-extension/` folder
5. Pin the SpoofShield icon to toolbar

**Backend URL Configuration:** The extension currently points to `http://localhost/SpoofShield/api/`. Update `popup.js` if your backend runs on a different URL.

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `health.php` | GET | Health check |
| `dns_scan.php` | POST | Scan domain DNS records |
| `email_analyze.php` | POST | Analyze email headers |
| `history.php` | GET | List investigations |
| `report.php?id=X` | GET | Get investigation report |

### Example Requests

**DNS Scan:**
```bash
curl -X POST http://localhost:8000/dns_scan.php \
  -H "Content-Type: application/json" \
  -d '{"domain": "example.com"}'
```

**Email Analysis:**
```bash
curl -X POST http://localhost:8000/email_analyze.php \
  -H "Content-Type: application/json" \
  -d '{"headers": "From: test@example.com\nTo: user@domain.com\nSubject: Test\n\nBody"}'
```

## Features

### Web Dashboard
- **Overview**: Statistics cards and quick domain scan
- **DNS Scanner**: A, AAAA, MX, NS, TXT record lookup with risk scoring
- **Email Analyzer**: SPF, DKIM, DMARC header parsing with findings
- **History**: Searchable, paginated investigation log
- **Reports**: Detailed investigation reports with print support

### Browser Extension
- One-click scan of current website
- Displays hostname, risk score, and severity
- Links to full dashboard report
- Minimal permissions (activeTab, storage only)

## Security & Privacy

- **No browsing history collection**: Extension only scans on explicit user action
- **Local database**: All data stays on your server
- **Input validation**: Domain and header validation on backend
- **Prepared statements**: SQL injection prevention
- **No external AI dependencies**: Core logic uses deterministic rules

## Limitations

- DNS checks depend on local resolver availability
- SPF/DKIM/DMARC parsing reads reported headers only (does not independently verify signatures)
- Risk scoring is heuristic-based, not definitive proof of spoofing
- Extension requires backend running on accessible host (localhost for development)

## Development

### Database Schema
The `database/schema.sql` creates:
- `investigations` — main record table
- `dns_records` — cached DNS records
- `findings` — individual risk findings

### Testing
```bash
# Run API tests (if implemented)
php tests/api_test.php
```

## License

MIT License — see LICENSE file for details.