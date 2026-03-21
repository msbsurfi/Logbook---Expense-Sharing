# Logbook — Halkhata Debt & Expense Management System

<p align="center">
  <img src="public/logo.png" alt="Logbook Logo" width="120" />
</p>

<p align="center">
  <strong>A collaborative expense-splitting and debt-tracking web application built with PHP.</strong>
</p>

<p align="center">
  <img alt="PHP Version" src="https://img.shields.io/badge/PHP-%3E%3D8.0-777BB4?logo=php&logoColor=white" />
  <img alt="License" src="https://img.shields.io/badge/License-Proprietary-red" />
  <img alt="MySQL" src="https://img.shields.io/badge/Database-MySQL-4479A1?logo=mysql&logoColor=white" />
  <img alt="Apache" src="https://img.shields.io/badge/Server-Apache-D22128?logo=apache&logoColor=white" />
</p>

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [URL Routes](#url-routes)
- [Security](#security)
- [Contributing](#contributing)
- [License](#license)

---

## Overview

**Logbook** (also known as *Halkhata*) is a lightweight, self-contained PHP web application that helps groups of friends or colleagues **track shared expenses**, **split bills**, and **settle debts** seamlessly. It features a custom MVC architecture built without any heavyweight framework, relying only on PHP 8, MySQL, and PHPMailer.

The system supports multi-user collaboration with friend networks, real-time notifications, an administrative control panel, and email-based verification — all in a clean, straightforward codebase.

---

## Features

### 🔐 Authentication & User Management
- User registration with **email verification**
- Secure login / logout with **PHP session management**
- **Forgot password** / password-reset flow
- Rate-limited verification email resend (max 5 per day, 5-minute cooldown)
- **Admin approval workflow** — new accounts require admin activation

### 👥 Friendship System
- Send, cancel, accept, or decline **friend requests**
- **Unfriend** any connected user
- View pending incoming and outgoing requests

### 💸 Expense Management
- Create shared expenses with a description and total amount
- Choose the **payer** (the person who paid the bill)
- Two split modes:
  - **Equal split** — divide the total evenly among all participants
  - **Custom split** — specify each person's exact share
- Automatically generates individual transaction records for each participant

### 💰 Transaction Tracking
- Create one-on-one **IOU records** between a lender and a borrower
- View the **net balance** with each friend on the dashboard
- See a complete **transaction history**
- **Settle transactions** — mark individual debts as paid

### 🔔 Notifications
- Real-time notification system for friend requests, new expenses, and settlements
- Mark notifications as read
- Unread notification count endpoint (suitable for polling)

### 🛡️ Admin Panel
- **Approve / reject** pending user registrations
- **Ban / unban** users with a stated reason
- **Promote / demote** users (role management)
- **Impersonate** any user for support purposes
- Export data to CSV:
  - Users export
  - Transactions export
  - Expenses export
- Analytics dashboard with usage statistics
- Application activity log

### 📧 Email Notifications
- Email verification on registration
- Password reset emails
- Configurable SMTP settings via `config/mail.php`
- Background **cron job** for periodic email summaries (`scripts/cron/email_summary.php`)
- First-run installer at `/install.php` with automatic schema import and admin bootstrap

---

## Tech Stack

| Layer | Technology |
|---|---|
| **Language** | PHP ≥ 8.0 |
| **Architecture** | Custom MVC (no framework) |
| **Database** | MySQL / MariaDB |
| **Web Server** | Apache (mod_rewrite via `.htaccess`) |
| **Email** | [PHPMailer 6.8](https://github.com/PHPMailer/PHPMailer) |
| **Dependency Manager** | Composer |
| **Frontend** | HTML5, CSS3, Vanilla JavaScript |
| **Security** | PDO prepared statements, CSRF tokens, `password_hash` |

---

## Project Structure

```
Logbook/
├── app/
│   ├── Controllers/           # Business logic — one controller per feature
│   │   ├── AdminController.php
│   │   ├── ExpenseController.php
│   │   ├── FriendController.php
│   │   ├── NotificationController.php
│   │   ├── TransactionController.php
│   │   └── UserController.php
│   ├── Lib/                   # Reusable utility classes
│   │   ├── Logger.php         # Application logging
│   │   ├── Mailer.php         # PHPMailer wrapper
│   │   └── Security.php       # CSRF & session helpers
│   ├── Models/                # Data access layer (PDO)
│   │   ├── Database.php       # PDO wrapper / query builder
│   │   ├── Expense.php
│   │   ├── Friend.php
│   │   ├── Notification.php
│   │   ├── Transaction.php
│   │   └── User.php
│   └── Views/                 # PHP/HTML view templates
│       ├── admin/             # Admin panel views
│       ├── expenses/          # Expense creation form
│       ├── friends/           # Friends list & requests
│       ├── layouts/           # Shared header & footer
│       ├── transactions/      # History & settle views
│       ├── dashboard.php
│       ├── login.php
│       ├── register.php
│       ├── forget-password.php
│       └── resend_verification.php
├── config/
│   ├── database.php           # Database connection settings
│   └── mail.php               # SMTP / email settings
├── public/                    # Web root (document root)
│   ├── index.php              # Front controller / router
│   ├── .htaccess              # URL rewriting rules
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript files
│   ├── logo.png
│   └── avatar.png
├── scripts/
│   └── cron/
│       └── email_summary.php  # Scheduled email task
├── vendor/                    # Composer dependencies (auto-generated)
├── composer.json
└── composer.lock
```

---

## Requirements

- PHP **8.0** or higher with extensions: `pdo`, `pdo_mysql`, `mbstring`, `openssl`
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Apache** web server with `mod_rewrite` enabled
- **Composer** (PHP dependency manager)
- An SMTP email account (Gmail, Mailgun, or any provider)

---

## Installation

### 1. Clone the Repository

```bash
git clone https://YOURDOMAIN/repository.git
cd Logbook
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Set the Document Root

Configure your Apache virtual host or your hosting control panel to point the document root to the `public/` directory:

```apache
<VirtualHost *:80>
    ServerName YOURDOMAIN
    DocumentRoot /path/to/Logbook/public

    <Directory /path/to/Logbook/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 4. Run the Installer

Visit `/install.php` in your browser. The installer will:

- collect database credentials
- optionally collect SMTP credentials or let you skip mail setup for later
- create the database if needed
- import `database/install.sql`
- create the first admin account
- hand off to `fix.php` to remove the installer script

### 5. Set File Permissions

```bash
chmod -R 755 .
```

### 6. (Optional) Set Up the Cron Job

Add the following cron entry to send periodic email summaries:

```cron
0 8 * * 1 php /path/to/Logbook/scripts/cron/email_summary.php
```

---

## Configuration

### `config/database.php`

```php
<?php
define('DB_HOST', 'YOURDOMAIN');
define('DB_PORT', 3306);
define('DB_USER', 'YOURDOMAIN');
define('DB_PASS', 'YOURDOMAIN');
define('DB_NAME', 'YOURDOMAIN');
```

### `config/mail.php`

```php
<?php
define('SMTP_HOST', 'mail.YOURDOMAIN');
define('SMTP_USER', 'noreply@YOURDOMAIN');
define('SMTP_PASS', 'YOURDOMAIN');
define('SMTP_PORT', 465);
define('SMTP_FROM_EMAIL', 'noreply@YOURDOMAIN');
define('SMTP_FROM_NAME', 'Logbook');
define('SMTP_SECURE', 'ssl');
```

The installer writes both files for you. These placeholder values are intentionally non-functional so the public repository does not expose real credentials or domains.

---

## Usage

### Registering a New Account

1. Visit `/register` and fill in your name, email, and password.
2. Check your inbox for the verification email and click the link.
3. Wait for an **admin to approve** your account.
4. Once approved, log in at `/login`.

### Adding Friends

1. Go to `/friends`.
2. Search for a user by their **profile code**.
3. Send a friend request — the other user will receive a notification.
4. Once accepted, you can start sharing expenses and recording transactions.

### Creating a Shared Expense

1. Navigate to `/expenses/create`.
2. Enter a description, the total amount, and select the payer.
3. Select participants and choose **equal** or **custom** split.
4. Submit — individual transactions are automatically created for each participant.

### Settling a Debt

1. Open the dashboard or transaction history at `/transactions/history`.
2. Click **Settle** next to an outstanding transaction.
3. Confirm — the transaction is marked as paid and all parties are notified.

### Admin Panel

Administrators can access `/admin` to manage users, view analytics, and export data.

---

## URL Routes

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/register` | Show registration form |
| POST | `/register` | Submit registration |
| GET | `/verify?token=...` | Verify email address |
| GET | `/login` | Show login form |
| POST | `/login` | Submit login |
| GET | `/logout` | Log out |
| GET | `/dashboard` | User dashboard |
| GET | `/friends` | Friends list |
| POST | `/friends/send` | Send friend request |
| POST | `/friends/respond/{id}/{action}` | Accept or decline request |
| POST | `/friends/unfriend/{id}` | Remove a friend |
| POST | `/transactions/create` | Create a transaction |
| GET | `/transactions/settle/{id}` | Show settle page |
| POST | `/transactions/settle/{id}` | Mark transaction as settled |
| GET | `/transactions/history` | Transaction history |
| GET | `/expenses/create` | Show expense form |
| POST | `/expenses/create` | Create shared expense |
| GET | `/notifications/list` | List notifications (JSON) |
| POST | `/notifications/mark-read` | Mark notifications as read |
| GET | `/notifications/unread-count` | Get unread count (JSON) |
| GET | `/admin` | Admin panel |
| POST | `/admin/approve/{id}` | Approve a user |
| POST | `/admin/ban/{id}` | Ban a user |
| GET | `/admin/export-users-csv` | Export users to CSV |
| GET | `/admin/export-transactions-csv` | Export transactions to CSV |
| GET | `/admin/export-expenses-csv` | Export expenses to CSV |

---

## Security

The application employs several security measures:

- **CSRF Protection** — All state-changing forms include a CSRF token validated server-side.
- **Password Hashing** — Passwords are stored using `password_hash()` with `PASSWORD_DEFAULT` (bcrypt).
- **SQL Injection Prevention** — All database queries use PDO prepared statements with bound parameters.
- **Input Sanitization** — User input is filtered using `filter_input_array`.
- **Session Security** — Sessions are regenerated on login; sensitive session data is cleared on logout.
- **Email Verification** — Accounts require email verification before becoming active.
- **Admin Approval** — Even verified accounts need explicit admin approval to access the system.
- **Rate Limiting** — Verification email resends are rate-limited (5 per day, 5-minute intervals).

---

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on submitting issues, suggesting features, and opening pull requests.

---

## License

Copyright © 2024 **MD Shifat Bin Siddique Urfi**. All rights reserved.

This software and all associated source code, documentation, and assets are the exclusive intellectual property of MD Shifat Bin Siddique Urfi. See the [LICENSE](LICENSE) file for full terms.
