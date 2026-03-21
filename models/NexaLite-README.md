# NexaLite - Quick Reference Guide

## ⚠️ PENTING: Ini untuk SQLite!

**Jangan salah pakai!**
- `NexaModel` = untuk **MySQL**
- `NexaLite` = untuk **SQLite**

## Quick Start

```php
// ✅ BENAR - Untuk SQLite
use App\System\NexaLite;

$db = new NexaLite(); // SQLite version

// ❌ SALAH - Jangan pakai ini untuk SQLite
// use App\System\NexaModel; // Ini untuk MySQL!
```

## Basic Operations

### SELECT
```php
// Get all
$db->table('users')->get();

// With conditions
$db->table('users')->where('status', '=', 'active')->get();

// First record
$db->table('users')->where('id', '=', 1)->first();
```

### INSERT
```php
$db->table('users')->insert([
    'name' => 'John',
    'email' => 'john@example.com'
]);
```

### UPDATE
```php
$db->table('users')
    ->where('id', '=', 1)
    ->update(['name' => 'Jane']);
```

### DELETE
```php
$db->table('users')->where('id', '=', 1)->delete();
```

## SQLite vs MySQL Differences

| Feature | MySQL | SQLite |
|---------|-------|--------|
| Date Format | `DATE_FORMAT(date, '%Y-%m')` | `STRFTIME('%Y-%m', date)` |
| Current Time | `NOW()` | `datetime('now')` |
| Upsert | `ON DUPLICATE KEY UPDATE` | `ON CONFLICT ... DO UPDATE` |
| Show Tables | `SHOW TABLES` | `SELECT name FROM sqlite_master` |

## Common SQLite Functions

```php
// Date formatting
->select("STRFTIME('%Y-%m-%d', created_at) as date")

// Current datetime
->select("datetime('now') as now")

// Unix timestamp
->select("strftime('%s', datetime('now')) as timestamp")
```

## Important Notes

1. **Primary Key**: Gunakan `INTEGER PRIMARY KEY` untuk auto-increment
2. **Transactions**: SQLite mendukung transactions
3. **Concurrency**: SQLite hanya satu writer pada satu waktu
4. **Date Storage**: Disarankan format ISO8601 atau unix timestamp

Untuk dokumentasi lengkap, lihat `NexaLite.md`

