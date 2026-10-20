# SpoofShield API Documentation

## Base URL
- Development: `http://localhost:8000`
- Production: `https://yourdomain.com/api`

## Endpoints

### GET /health.php
Check API and database connectivity.

**Response:**
```json
{"status":"ok","db":"connected"}
```

### POST /dns_scan.php
Scan domain DNS records and calculate risk score.

**Request Body:**
```json
{
  "domain": "example.com"
}
```

**Response:**
```json
{
  "id": 1,
  "results": {
    "domain": "example.com",
    "risk_score": 0,
    "severity": "Low",
    "records": {
      "A": [{"host":"example.com","type":"A","ip":"93.184.216.34"}],
      "AAAA": [],
      "MX": [{"host":"example.com","type":"MX","target":"mail.example.com"}],
      "NS": [...],
      "TXT": [...]
    }
  }
}
```

**Error Response:**
```json
{"error":"Invalid domain"}
```

### POST /email_analyze.php
Analyze email headers for spoofing indicators.

**Request Body:**
```json
{
  "headers": "From: attacker@evil.com\nReturn-Path: bounce@phish.net\nAuthentication-Results: ... spf=fail; dkim=fail; dmarc=fail"
}
```

**Response:**
```json
{
  "id": 2,
  "results": {
    "risk_score": 100,
    "severity": "High",
    "findings": [
      {"title":"SPF Fail","severity":"High","detail":"SPF authentication failed"}
    ],
    "parsed_headers": {...}
  }
}
```

### GET /history.php
List investigation history.

**Query Parameters:**
- `limit` (optional): Number of results (default 50)

**Response:**
```json
[
  {
    "id": 1,
    "type": "dns",
    "target": "example.com",
    "risk_score": 0,
    "severity": "Low",
    "created_at": "2026-10-20 23:07:08"
  }
]
```

### GET /report.php
Retrieve full investigation report.

**Query Parameters:**
- `id` (required): Investigation ID

**Response:**
```json
{
  "id": 1,
  "type": "dns",
  "target": "example.com",
  "risk_score": 0,
  "severity": "Low",
  "summary": "Automated DNS scan",
  "raw_data": "{...}",
  "created_at": "2026-10-20 23:07:08"
}
```

## Database Schema

### investigations
- `id` (INT, PK, AUTO_INCREMENT)
- `type` (ENUM: 'dns', 'email')
- `target` (VARCHAR 255)
- `risk_score` (INT)
- `severity` (ENUM: 'Low', 'Medium', 'High', 'Unknown')
- `summary` (TEXT)
- `raw_data` (LONGTEXT)
- `created_at` (TIMESTAMP)

### dns_records
- `id` (INT, PK, AUTO_INCREMENT)
- `investigation_id` (INT, FK)
- `record_type` (VARCHAR)
- `value` (TEXT)
- `status` (VARCHAR)

### findings
- `id` (INT, PK, AUTO_INCREMENT)
- `investigation_id` (INT, FK)
- `severity` (ENUM)
- `title` (VARCHAR 255)
- `description` (TEXT)
- `evidence` (TEXT)
- `recommendation` (TEXT)
