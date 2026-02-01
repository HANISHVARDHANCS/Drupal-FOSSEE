# Event Registration Manager

[![Drupal 10](https://img.shields.io/badge/Drupal-10.x-blue.svg)](https://www.drupal.org/project/drupal)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://www.php.net/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

A **production-ready Drupal 10 custom module** for comprehensive event registration management. This module enables administrators to configure events and allows users to register through a dynamic, AJAX-powered form with full validation, email notifications, and admin reporting capabilities.

> **Note:** This module uses **NO contributed modules** - it is built entirely with Drupal Core APIs.

---

## Table of Contents

1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Enabling the Module](#enabling-the-module)
5. [Module URLs](#module-urls)
6. [Database Schema](#database-schema)
7. [Validation Logic](#validation-logic)
8. [Email Notification System](#email-notification-system)
9. [Permissions](#permissions)
10. [Configuration Guide](#configuration-guide)
11. [File Structure](#file-structure)
12. [API Reference](#api-reference)
13. [Troubleshooting](#troubleshooting)

---

## Features

- **Event Configuration**: Full CRUD operations for events with date-based registration periods
- **Dynamic Registration Form**: AJAX-powered cascading dropdowns for seamless event selection
- **Smart Validation**: Duplicate prevention, email verification, and character restrictions
- **Email Notifications**: Automated confirmations to users and optional admin alerts
- **Admin Dashboard**: Filterable registration listing with participant counts
- **CSV Export**: One-click export of registration data
- **Access Control**: Granular custom permissions for all admin functions
- **Modern UI**: Professional styling with animations and responsive design

---

## Requirements

| Requirement | Version |
|-------------|---------|
| Drupal Core | 10.x |
| PHP | 8.1 or higher |
| Database | MySQL 5.7+ / MariaDB 10.3+ / PostgreSQL 12+ |

**Dependencies (Core Only):**
- `drupal:datetime`
- `drupal:options`

---

## Installation

### Step 1: Download the Module

Clone or download this repository to your Drupal installation:

```bash
# Navigate to custom modules directory
cd /path/to/drupal/web/modules/custom/

# Clone the repository
git clone https://github.com/your-repo/event_registration_manager.git

# OR copy the module folder directly
cp -r event_registration_manager /path/to/drupal/web/modules/custom/
```

### Step 2: Verify File Structure

Ensure the module is placed correctly:

```
drupal/
└── web/
    └── modules/
        └── custom/
            └── event_registration_manager/
                ├── event_registration_manager.info.yml
                ├── event_registration_manager.module
                ├── src/
                └── ... (other files)
```

### Step 3: Check File Permissions

```bash
# Set proper ownership (adjust user/group as needed)
chown -R www-data:www-data event_registration_manager/

# Set directory permissions
find event_registration_manager -type d -exec chmod 755 {} \;

# Set file permissions
find event_registration_manager -type f -exec chmod 644 {} \;
```

---

## Enabling the Module

### Option A: Using Drush (Recommended)

```bash
# Enable the module
drush pm:enable event_registration_manager -y

# Clear all caches
drush cache:rebuild

# Verify installation
drush pm:list --filter=event_registration_manager
```

### Option B: Using Admin UI

1. Navigate to **Administration → Extend** (`/admin/modules`)
2. Search for "Event Registration Manager"
3. Check the checkbox next to the module
4. Click **Install** at the bottom of the page
5. Clear caches at **Administration → Configuration → Development → Performance**

### Option C: Using Composer (if integrated)

```bash
# If module has composer.json in drupal root
composer require drupal/event_registration_manager

drush pm:enable event_registration_manager -y
drush cache:rebuild
```

---

## Module URLs

After installation, the following URLs become available:

### Admin URLs

| Page | URL | Permission Required |
|------|-----|---------------------|
| **Module Settings** | `/admin/config/event-registration/settings` | `configure event registration settings` |
| **Event Configuration** | `/admin/config/event-registration/events` | `manage events` |
| **Add New Event** | `/admin/config/event-registration/events/add` | `manage events` |
| **Edit Event** | `/admin/config/event-registration/events/{id}/edit` | `manage events` |
| **Registration Listing** | `/admin/reports/event-registrations` | `administer event registrations` |
| **CSV Export** | `/admin/reports/event-registrations/export` | `administer event registrations` |

### Public URLs

| Page | URL | Permission Required |
|------|-----|---------------------|
| **Registration Form** | `/event-registration` | `register for events` |

### AJAX Endpoints (Internal)

| Endpoint | URL Pattern |
|----------|-------------|
| Get Event Dates | `/event-registration/ajax/event-dates/{category}` |
| Get Event Names | `/event-registration/ajax/event-names/{category}/{date}` |
| Admin Filter | `/admin/reports/event-registrations/ajax/event-names/{date}` |

---

## Database Schema

The module creates **two custom database tables** via `hook_schema()`:

### Table: `event_config`

Stores event configuration data created by administrators.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique event identifier |
| `event_name` | VARCHAR(255) | NOT NULL | Display name of the event |
| `category` | VARCHAR(100) | NOT NULL, INDEXED | Event category (online_workshop, hackathon, conference, one_day_workshop) |
| `event_date` | VARCHAR(20) | NOT NULL, INDEXED | Event date in YYYY-MM-DD format |
| `registration_start_date` | VARCHAR(20) | NOT NULL | When registration opens (YYYY-MM-DD) |
| `registration_end_date` | VARCHAR(20) | NOT NULL | When registration closes (YYYY-MM-DD) |
| `created` | INT | NOT NULL | Unix timestamp of creation |
| `changed` | INT | NOT NULL | Unix timestamp of last modification |

**Indexes:**
- `category` - For filtering events by category
- `event_date` - For date-based queries
- `registration_dates` - Composite index for date range queries

### Table: `event_registration`

Stores user registration data.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique registration identifier |
| `full_name` | VARCHAR(255) | NOT NULL | Registrant's full name |
| `email` | VARCHAR(255) | NOT NULL, INDEXED | Registrant's email address |
| `college_name` | VARCHAR(255) | NOT NULL | Educational institution |
| `department` | VARCHAR(255) | NOT NULL | Academic department |
| `event_id` | INT UNSIGNED | NOT NULL, FOREIGN KEY | Reference to event_config.id |
| `category` | VARCHAR(100) | NOT NULL | Denormalized event category |
| `event_date` | VARCHAR(20) | NOT NULL, INDEXED | Denormalized event date |
| `event_name` | VARCHAR(255) | NOT NULL | Denormalized event name |
| `created` | INT | NOT NULL | Unix timestamp of registration |

**Indexes:**
- `event_id` - Foreign key index
- `email` - For email lookups
- `event_date` - For date filtering
- `email_event_date` - Composite index for duplicate checking

**Foreign Key:**
- `event_id` → `event_config(id)` with ON DELETE RESTRICT

### Schema Diagram

```
┌─────────────────────────────┐         ┌─────────────────────────────┐
│       event_config          │         │     event_registration      │
├─────────────────────────────┤         ├─────────────────────────────┤
│ id (PK)                     │◄────────│ event_id (FK)               │
│ event_name                  │         │ id (PK)                     │
│ category                    │         │ full_name                   │
│ event_date                  │         │ email                       │
│ registration_start_date     │         │ college_name                │
│ registration_end_date       │         │ department                  │
│ created                     │         │ category                    │
│ changed                     │         │ event_date                  │
└─────────────────────────────┘         │ event_name                  │
                                        │ created                     │
                                        └─────────────────────────────┘
```

---

## Validation Logic

The module implements comprehensive validation at multiple levels:

### 1. Duplicate Registration Prevention

**Logic:** Each email address can only register once per event date.

```php
// Check performed in EventStorageService::checkDuplicateRegistration()
SELECT COUNT(*) FROM event_registration 
WHERE email = :email AND event_date = :event_date
```

**User Message:** *"You have already registered for an event on this date. Each email can only be registered once per event date."*

### 2. Email Format Validation

**Logic:** Uses PHP's `filter_var()` with `FILTER_VALIDATE_EMAIL` flag.

```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Return error
}
```

**User Message:** *"Please enter a valid email address."*

### 3. Text Field Character Restrictions

**Logic:** Regex pattern validation to prevent special characters.

| Field | Allowed Characters |
|-------|-------------------|
| Full Name | Letters, numbers, spaces, periods, hyphens |
| College Name | Letters, numbers, spaces, periods, hyphens, commas, ampersands |
| Department | Letters, numbers, spaces, periods, hyphens, commas, ampersands |
| Event Name | Letters, numbers, spaces, hyphens, underscores, periods, commas, ampersands |

```php
// Example: Full Name validation
if (preg_match('/[^a-zA-Z0-9\s\.\-]/', $full_name)) {
    // Return error
}
```

**User Message:** *"[Field] contains invalid characters. Only letters, numbers, spaces, periods, and hyphens are allowed."*

### 4. Date Logic Validation (Admin)

**Rules:**
- Registration end date must be ≥ registration start date
- Registration end date must be ≤ event date

```php
if (strtotime($registration_start) > strtotime($registration_end)) {
    // Error: End date before start date
}

if (strtotime($registration_end) > strtotime($event_date)) {
    // Error: Registration closes after event
}
```

### 5. Registration Period Gating

**Logic:** Public registration form only shows events where:

```php
$today = date('Y-m-d');
// Active events query
WHERE registration_start_date <= $today 
  AND registration_end_date >= $today
```

---

## Email Notification System

The module uses Drupal's **Mail API** (`hook_mail()`) for all email communications.

### Email Types

#### 1. User Confirmation Email

**Trigger:** Sent immediately after successful registration  
**Recipient:** The registrant's email address  
**Template Key:** `registration_confirmation`

**Contents:**
- Personalized greeting with registrant's name
- Event name
- Event date (formatted)
- Event category
- Thank you message

#### 2. Admin Notification Email

**Trigger:** Sent after successful registration (if enabled)  
**Recipient:** Configured admin email address  
**Template Key:** `admin_notification`

**Contents:**
- Registration details (name, email, college, department)
- Event details (name, date, category)

### Configuration

Navigate to `/admin/config/event-registration/settings`:

| Setting | Description |
|---------|-------------|
| **Admin Notification Email** | Email address to receive notifications |
| **Enable Admin Notifications** | Toggle to enable/disable admin emails |

### Implementation Flow

```
User Submits Registration
         │
         ▼
┌─────────────────────────┐
│ Save to Database        │
└─────────────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Send User Confirmation  │──► User's Email
└─────────────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Check: Admin Enabled?   │
└─────────────────────────┘
         │
    YES  │  NO
         ▼
┌─────────────────────────┐
│ Send Admin Notification │──► Admin's Email
└─────────────────────────┘
```

### Code Location

- Mail hook: `event_registration_manager.module` → `hook_mail()`
- Mail service: `src/Service/EventMailService.php`

---

## Permissions

The module defines **four custom permissions** in `event_registration_manager.permissions.yml`:

### Permission Definitions

| Permission | Machine Name | Description | Recommended Roles |
|------------|--------------|-------------|-------------------|
| **Administer Event Registrations** | `administer event registrations` | Access admin listing, view registrations, export CSV | Administrator, Event Manager |
| **Configure Settings** | `configure event registration settings` | Configure module settings (admin email, toggles) | Administrator |
| **Manage Events** | `manage events` | Create, edit, delete events | Administrator, Event Manager |
| **Register for Events** | `register for events` | Access public registration form | Authenticated User, Anonymous |

### Assigning Permissions

1. Navigate to **Administration → People → Permissions** (`/admin/people/permissions`)
2. Search for "Event Registration"
3. Check the appropriate boxes for each role
4. Click **Save permissions**

### Usage in Code

**Routes (routing.yml):**
```yaml
requirements:
  _permission: 'administer event registrations'
```

**Controllers:**
```php
// Permission check is automatic via routing
// For manual checks:
if (\Drupal::currentUser()->hasPermission('manage events')) {
    // Allow access
}
```

### Recommended Role Configuration

| Role | Permissions |
|------|-------------|
| Anonymous | `register for events` (optional) |
| Authenticated User | `register for events` |
| Event Manager | `manage events`, `administer event registrations` |
| Administrator | All permissions |

---

## Configuration Guide

### Initial Setup

1. **Enable the module** (see [Enabling the Module](#enabling-the-module))

2. **Configure Settings** at `/admin/config/event-registration/settings`:
   - Set the admin notification email
   - Enable/disable admin notifications

3. **Set Permissions** at `/admin/people/permissions`:
   - Grant `register for events` to appropriate roles
   - Grant admin permissions to event managers

4. **Create Events** at `/admin/config/event-registration/events/add`:
   - Fill in event details
   - Set registration period dates
   - Choose event category

5. **Test Registration** at `/event-registration`:
   - Verify form displays correctly
   - Test AJAX dropdowns
   - Submit test registration
   - Verify emails are received

### Event Categories

The module supports four pre-defined categories:

| Machine Name | Display Label |
|--------------|---------------|
| `online_workshop` | Online Workshop |
| `hackathon` | Hackathon |
| `conference` | Conference |
| `one_day_workshop` | One-day Workshop |

---

## File Structure

```
event_registration_manager/
│
├── config/
│   └── install/
│       └── event_registration_manager.settings.yml  # Default config values
│
├── css/
│   ├── event_registration_manager.admin.css         # Admin page styling
│   └── event_registration_manager.form.css          # Registration form styling
│
├── js/
│   ├── admin_listing.js                             # Admin listing AJAX
│   └── registration_form.js                         # Form UX enhancements
│
├── sql/
│   └── event_registration_manager.sql               # Database schema dump
│
├── src/
│   ├── Controller/
│   │   ├── AdminListingController.php               # Registration listing + CSV export
│   │   ├── AjaxController.php                       # AJAX endpoints
│   │   └── EventController.php                      # Event admin listing
│   │
│   ├── Form/
│   │   ├── AdminFilterForm.php                      # Admin listing filters
│   │   ├── EventConfigForm.php                      # Create/edit events
│   │   ├── EventDeleteForm.php                      # Delete confirmation
│   │   ├── RegistrationForm.php                     # Public registration
│   │   └── SettingsForm.php                         # Module settings
│   │
│   └── Service/
│       ├── EventMailService.php                     # Email handling
│       └── EventStorageService.php                  # Database operations
│
├── composer.json                                    # Composer metadata
├── composer.lock                                    # Dependency lock
├── event_registration_manager.info.yml              # Module definition
├── event_registration_manager.install               # Schema + install hooks
├── event_registration_manager.libraries.yml         # Asset libraries
├── event_registration_manager.links.action.yml      # Action links
├── event_registration_manager.links.menu.yml        # Menu links
├── event_registration_manager.links.task.yml        # Local tasks
├── event_registration_manager.module                # Hook implementations
├── event_registration_manager.permissions.yml       # Permission definitions
├── event_registration_manager.routing.yml           # Route definitions
├── event_registration_manager.services.yml          # Service definitions
└── README.md                                        # This documentation
```

---

## API Reference

### Services

Services are defined in `event_registration_manager.services.yml` and use **Dependency Injection**.

#### EventStorageService

**Service ID:** `event_registration_manager.storage`

```php
// Get service (in controllers/forms, use DI instead)
$storage = \Drupal::service('event_registration_manager.storage');
```

**Methods:**

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `createEvent()` | `array $data` | `int` | Create event, returns ID |
| `updateEvent()` | `int $id, array $data` | `bool` | Update event |
| `deleteEvent()` | `int $id` | `bool` | Delete event |
| `getEvent()` | `int $id` | `?array` | Get single event |
| `getAllEvents()` | - | `array` | Get all events |
| `getActiveEvents()` | - | `array` | Get currently registrable events |
| `getActiveCategories()` | - | `array` | Get categories with active events |
| `getEventDatesByCategory()` | `string $category` | `array` | Get dates for category |
| `getEventsByCategoryAndDate()` | `string $cat, string $date` | `array` | Get events by cat + date |
| `createRegistration()` | `array $data` | `int` | Create registration |
| `checkDuplicateRegistration()` | `string $email, string $date` | `bool` | Check for duplicates |
| `getRegistrations()` | `array $filters` | `array` | Get filtered registrations |
| `getRegistrationCount()` | `array $filters` | `int` | Count registrations |

#### EventMailService

**Service ID:** `event_registration_manager.mailer`

```php
$mailer = \Drupal::service('event_registration_manager.mailer');
```

**Methods:**

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `sendUserConfirmation()` | `array $registration` | `bool` | Send user email |
| `sendAdminNotification()` | `array $registration` | `bool` | Send admin email |

---

## Troubleshooting

### Common Issues

#### 1. Module Not Appearing in Extend Page

**Cause:** Invalid YAML syntax or missing `.info.yml`

**Solution:**
```bash
# Check YAML syntax
drush config:validate

# Verify file exists
ls -la modules/custom/event_registration_manager/*.info.yml
```

#### 2. Database Tables Not Created

**Cause:** `hook_schema()` not executed

**Solution:**
```bash
# Uninstall and reinstall
drush pm:uninstall event_registration_manager -y
drush pm:enable event_registration_manager -y

# Verify tables exist
drush sqlq "SHOW TABLES LIKE 'event_%'"
```

#### 3. Emails Not Sending

**Cause:** Mail system not configured

**Solution:**
1. Verify mail configuration in `settings.php`
2. Check admin email is set in module settings
3. Enable admin notifications
4. Check Drupal logs: `/admin/reports/dblog`

#### 4. AJAX Not Working

**Cause:** JavaScript error or cache issue

**Solution:**
```bash
# Clear all caches
drush cache:rebuild

# Check browser console for errors
# Verify jQuery is loaded
```

#### 5. Permission Denied Errors

**Cause:** User lacks required permissions

**Solution:**
1. Check user's roles
2. Verify permissions at `/admin/people/permissions`
3. Clear caches after permission changes

### Debug Mode

Enable verbose logging:

```php
// In settings.php
$config['system.logging']['error_level'] = 'verbose';
```

Check logs at `/admin/reports/dblog` and filter by "event_registration_manager".

---

## Contributing

1. Fork the repository
2. Create a feature branch
3. Follow Drupal coding standards
4. Submit a pull request

### Coding Standards

```bash
# Check coding standards
phpcs --standard=Drupal,DrupalPractice modules/custom/event_registration_manager

# Auto-fix issues
phpcbf --standard=Drupal,DrupalPractice modules/custom/event_registration_manager
```

---

## License

This project is licensed under the **GNU General Public License v2.0 or later**.

See [LICENSE](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html) for details.

---

## Version

**Current Version:** 1.0.0

---

## Author

Developed as a comprehensive Drupal 10 custom module demonstrating industry best practices:

- ✅ PSR-4 Autoloading
- ✅ Dependency Injection
- ✅ Drupal Form API
- ✅ Drupal Config API
- ✅ Drupal Mail API
- ✅ Database Schema API
- ✅ AJAX Integration
- ✅ Custom Permissions
- ✅ Clean Code Architecture
