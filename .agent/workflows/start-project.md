---
description: Start the VyaparCRM project for local development
---

# Start VyaparCRM Project

// turbo-all

1. Clear old sessions and config cache:
```
&"C:\xampp\php\php.exe" -r "array_map('unlink', glob('storage/framework/sessions/*')); @unlink('bootstrap/cache/config.php'); echo 'Cache cleared.';"
```
Run in: `c:\xampp\htdocs\rvallsolutionscrm-main\backend`

2. Start the Laravel dev server with correct PHP 8.2:
```
&"C:\xampp\php\php.exe" artisan serve --host=localhost --port=8080
```
Run in: `c:\xampp\htdocs\rvallsolutionscrm-main\backend`

3. Open browser: **http://localhost:8080/login**

## Login Credentials
- Email: `rvsolution696@gmail.com`
- Password: `9773256235`

## Important
- Always use `C:\xampp\php\php.exe` (PHP 8.2) — NOT just `php` (which uses old PHP 8.1 from C:\xampp1)
- Use `http://localhost:8080` — NOT Apache URL
