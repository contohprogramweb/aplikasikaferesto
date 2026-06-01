# Rate Limiter Implementation Guide
## Berdasarkan SRS v4.0 Bab 3.4.7 dan NFR-SEC-16

## Overview

Rate Limiter ini mengimplementasikan pembatasan request untuk mencegah abuse dan DDoS attack pada aplikasi restoran. Sistem menggunakan **sliding window algorithm** dengan dukungan blocking otomatis.

## Fitur Utama

1. **Sliding Window Algorithm** - Menghitung request dalam window waktu yang bergerak
2. **Auto-blocking** - Blokir otomatis setelah melebihi limit
3. **Dual Storage** - Support database dan file-based storage
4. **Smart Identifier** - IP address untuk publik, session_id untuk authenticated user
5. **Login Attempt Tracking** - Track dan blokir setelah 5x gagal login dalam 15 menit
6. **Admin Panel** - Dashboard untuk manage blocked IPs
7. **Auto-expire** - Block otomatis expire setelah duration tertentu

## Konfigurasi Rate Limit

```php
$limits = [
    'table_check' => ['limit' => 10, 'window' => 60, 'block' => 300],    // 10 req/menit, block 5 menit
    'session'     => ['limit' => 10, 'window' => 60, 'block' => 300],    // 10 req/menit, block 5 menit
    'polling'     => ['limit' => 1,  'window' => 3,  'block' => 0],      // 1 req/3 detik, no block
    'login'       => ['limit' => 5,  'window' => 900, 'block' => 900],   // 5x/15 menit, block 15 menit
    'admin'       => ['limit' => 60, 'window' => 60, 'block' => 300],    // 60 req/menit, block 5 menit
];
```

### Parameter Configuration

| Parameter | Description | Example |
|-----------|-------------|---------|
| `limit` | Jumlah maksimum request dalam window | `10` |
| `window` | Ukuran window dalam detik | `60` (1 menit) |
| `block` | Durasi blocking dalam detik (0 = no block) | `300` (5 menit) |

## Database Setup

Jalankan migration untuk membuat tabel yang diperlukan:

```bash
mysql -u username -p database_name < database/migrations/001_rate_limiter_tables.sql
```

### Tabel yang Dibuat

1. **rate_limits** - Menyimpan informasi blocking
2. **rate_limit_requests** - Menyimpan timestamps request untuk sliding window
3. **login_attempts** - Track login attempts untuk security
4. **activity_logs** - Audit trail untuk semua activity

## Usage

### 1. Penggunaan di Controller (Extend Base_Controller)

```php
class Api_controller extends Base_Controller {
    
    public function check_table_status()
    {
        // Check rate limit untuk endpoint table_check
        if (!$this->check_rate_limit('table_check')) {
            return; // 429 response sudah dikirim otomatis
        }
        
        // Logic endpoint...
        $this->json_success(['status' => 'ok']);
    }
    
    public function login()
    {
        $username = $this->input->post('username');
        $password = $this->input->post('password');
        
        // Check rate limit untuk login
        if (!$this->check_rate_limit('login')) {
            return;
        }
        
        // Authenticate user
        $user = $this->auth_model->authenticate($username, $password);
        
        if ($user) {
            // Record successful login
            $this->record_login_attempt($username, true);
            
            // Login success logic...
            $this->json_success(['token' => $user->token]);
        } else {
            // Record failed login
            $this->record_login_attempt($username, false);
            
            $this->json_error('Invalid credentials', 401);
        }
    }
}
```

### 2. Penggunaan Langsung dengan Library

```php
$this->load->library('rate_limiter');

// Check rate limit
$result = $this->rate_limiter->check_limit('polling');

if (!$result['allowed']) {
    // Return 429 response
    $this->output
        ->set_status_header(429)
        ->set_content_type('application/json')
        ->set_header('Retry-After: ' . $result['retry_after'])
        ->set_output(json_encode([
            'status' => 'error',
            'message' => 'Terlalu banyak permintaan. Silakan tunggu.',
            'code' => 429,
            'retry_after' => $result['retry_after']
        ]));
    return;
}

// Continue with normal logic...
```

### 3. Custom Identifier

```php
// Gunakan custom identifier (misal: user ID)
$user_id = $this->session->userdata('user_id');
$result = $this->rate_limiter->check_limit('admin', 'user:' . $user_id);
```

### 4. Manual Blocking/Unblocking

```php
// Get blocked IPs
$blocked = $this->rate_limiter->get_blocked_ips();

// Unblock specific IP
$this->rate_limiter->unblock_identifier('ip:192.168.1.100', 'login');

// Cleanup expired records
$this->rate_limiter->cleanup();
```

## Response Format

### Success (Allowed)

```json
{
    "allowed": true,
    "remaining": 9,
    "reset": 1625097600,
    "blocked": false,
    "retry_after": 0
}
```

### Rate Limit Exceeded (429)

```json
{
    "status": "error",
    "message": "Terlalu banyak permintaan. Silakan tunggu.",
    "code": 429
}
```

**Headers:**
```
HTTP/1.1 429 Too Many Requests
Content-Type: application/json
Retry-After: 300
```

### Blocked

```json
{
    "status": "error",
    "message": "Terlalu banyak permintaan. Silakan tunggu.",
    "code": 429,
    "blocked": true,
    "retry_after": 285
}
```

## Admin Panel

### Akses

URL: `/admin/rate_limiter_admin`

### Fitur Admin

1. **Dashboard** - List semua IP yang sedang diblokir
2. **Filter by Endpoint Group** - Filter berdasarkan tipe endpoint
3. **Manual Unblock** - Unblock IP secara manual
4. **Detail View** - Lihat detail request history
5. **Statistics** - Statistik rate limiting dan login attempts
6. **Cleanup** - Manual cleanup expired records

### API Endpoints Admin

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/rate_limiter_admin` | Dashboard blocked IPs |
| POST | `/admin/rate_limiter_admin/unblock/{id}` | Unblock by ID |
| POST | `/admin/rate_limiter_admin/unblock_by_identifier` | Unblock by identifier |
| GET | `/admin/rate_limiter_admin/detail/{id}` | Detail block |
| POST | `/admin/rate_limiter_admin/cleanup` | Manual cleanup |
| GET | `/admin/rate_limiter_admin/stats` | Statistics |

## Hook Integration

Untuk menerapkan rate limiting secara global, gunakan hook di `application/config/hooks.php`:

```php
$hook['pre_controller'][] = [
    'class'    => 'RateLimiterHook',
    'function' => 'apply_rate_limit',
    'filename' => 'RateLimiterHook.php',
    'filepath' => 'hooks'
];
```

## Storage Options

### Database Storage (Default)

```php
$config['storage_type'] = 'database';
```

Keuntungan:
- Persistent storage
- Query yang lebih cepat dengan index
- Support untuk distributed systems

### File-based Storage

```php
$config['storage_type'] = 'file';
```

File disimpan di `application/cache/rate_limits/`

Keuntungan:
- Tidak perlu setup database
- Cocok untuk development atau low-traffic

## Scheduled Cleanup

Setup cron job untuk cleanup otomatis:

```bash
# Jalankan setiap jam
0 * * * * curl https://yourdomain.com/admin/rate_limiter_admin/cleanup
```

Atau gunakan MySQL Event Scheduler (sudah termasuk di migration):

```sql
-- Event sudah dibuat di migration
SHOW EVENTS LIKE 'cleanup_rate_limits';
```

## Security Considerations

1. **IP Spoofing** - Pastikan server configured untuk trust hanya proxy yang sah
2. **Session Hijacking** - Gunakan HTTPS dan secure session configuration
3. **Distributed Attacks** - Untuk production high-traffic, pertimbangkan Redis-based rate limiting
4. **False Positives** - Monitor logs dan adjust limits sesuai kebutuhan

## Monitoring & Logging

Semua activity dicatat di tabel `activity_logs`:

- `login_failed` - Failed login attempts
- `login_success` - Successful logins
- `rate_limit_exceeded` - Rate limit violations
- `ip_unblocked` - Manual unblock actions

Query untuk monitoring:

```sql
-- Failed login attempts last 24 hours
SELECT ip_address, COUNT(*) as failed_count 
FROM login_attempts 
WHERE success = 0 AND attempted_at >= NOW() - INTERVAL 24 HOUR
GROUP BY ip_address 
ORDER BY failed_count DESC;

-- Rate limit events last 24 hours
SELECT action, COUNT(*) as count 
FROM activity_logs 
WHERE timestamp >= NOW() - INTERVAL 24 HOUR
GROUP BY action;
```

## Testing

### Unit Test Example

```php
public function test_rate_limit()
{
    $this->load->library('rate_limiter');
    
    // First 10 requests should be allowed
    for ($i = 0; $i < 10; $i++) {
        $result = $this->rate_limiter->check_limit('table_check', 'test:ip');
        $this->assertTrue($result['allowed']);
    }
    
    // 11th request should be blocked
    $result = $this->rate_limiter->check_limit('table_check', 'test:ip');
    $this->assertFalse($result['allowed']);
    $this->assertTrue($result['blocked']);
}
```

## Troubleshooting

### Issue: Rate limit tidak bekerja

**Solution:**
- Pastikan library loaded: `$this->load->library('rate_limiter')`
- Cek konfigurasi storage type
- Verify database tables exists

### Issue: Blocking tidak auto-expire

**Solution:**
- Jalankan manual cleanup: `$this->rate_limiter->cleanup()`
- Setup cron job atau MySQL Event Scheduler

### Issue: False positive blocking

**Solution:**
- Increase limit atau window size
- Check untuk shared IP (NAT, proxy)
- Gunakan session-based identifier untuk authenticated users

## Files Created

| File | Purpose |
|------|---------|
| `application/libraries/Rate_limiter.php` | Main rate limiter library |
| `application/hooks/RateLimiterHook.php` | Hook untuk global rate limiting |
| `application/core/Base_Controller.php` | Base controller dengan rate limiting methods |
| `application/models/Rate_limit_model.php` | Model untuk database operations |
| `application/controllers/admin/Rate_limiter_admin.php` | Admin controller untuk manage blocks |
| `database/migrations/001_rate_limiter_tables.sql` | Database migration |
| `docs/RATE_LIMITER_GUIDE.md` | Documentation |

## References

- SRS v4.0 Bab 3.4.7 - Rate Limiting Requirements
- NFR-SEC-16 - Security: Protection Against DDoS and Brute Force
- OWASP Rate Limiting Guidelines
- CodeIgniter 3.x Documentation
