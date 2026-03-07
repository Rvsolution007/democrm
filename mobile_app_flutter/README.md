# VyaparCRM Mobile App

Flutter mobile application for VyaparCRM that connects to the Laravel backend via REST APIs.

## Features

- ✅ Token-based authentication (Laravel Sanctum)
- ✅ Leads management (list, detail, stage updates)
- ✅ Customers management (list, detail)
- ✅ Profile with logout
- ✅ Pull-to-refresh and infinite scroll pagination
- ✅ Search and filter functionality
- ✅ Light/Dark theme support

## Prerequisites

- Flutter SDK 3.2.0 or higher
- Laravel backend running at localhost:8000

## Setup

1. **Install dependencies:**
   ```bash
   flutter pub get
   ```

2. **Configure API URL:**
   Edit `lib/config.dart` and set the correct `baseUrl`:
   ```dart
   static const String baseUrl = 'http://127.0.0.1:8000';
   // For Android emulator: 'http://10.0.2.2:8000'
   // For physical device: 'http://YOUR-LAN-IP:8000'
   ```

3. **Start Laravel backend:**
   ```bash
   cd ../backend
   php artisan serve
   ```

4. **Run the app:**
   ```bash
   # For web (quick preview)
   flutter run -d chrome --web-port=3000
   
   # For Android
   flutter run -d android
   ```

## Demo Credentials

- Email: `admin@vyaparcrm.local`
- Password: `password123`

## Project Structure

```
lib/
├── main.dart                 # App entry point
├── config.dart               # Configuration (BASE_URL)
├── core/
│   ├── api/                  # Dio client, exceptions
│   ├── router/               # GoRouter configuration
│   ├── storage/              # Secure token storage
│   └── theme/                # App theming
├── features/
│   ├── auth/                 # Login, auth state
│   ├── leads/                # Leads list & detail
│   ├── customers/            # Customers list & detail
│   └── profile/              # User profile, logout
└── widgets/                  # Shared widgets
```

## Architecture

- **State Management**: Riverpod
- **Routing**: GoRouter with auth redirect
- **HTTP Client**: Dio with interceptors
- **Storage**: flutter_secure_storage

## API Endpoints Used

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/auth/login` | POST | Login |
| `/api/v1/auth/logout` | POST | Logout |
| `/api/v1/auth/me` | GET | Current user |
| `/api/v1/leads` | GET | List leads |
| `/api/v1/leads/{id}` | GET | Lead detail |
| `/api/v1/leads/{id}/stage` | POST | Update stage |
| `/api/v1/clients` | GET | List customers |
| `/api/v1/clients/{id}` | GET | Customer detail |
