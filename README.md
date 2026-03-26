# Bank Management System

A simple PHP-based Bank Management System with admin and customer panels.

## Features

### Admin Panel
- Dashboard with total balance, accounts, and customers overview
- Manage accounts (create, delete, add balance)
- Manage users

### Customer Panel
- View account balance
- Deposit money
- Withdraw money
- Transfer money to other accounts
- View transaction history

## Setup

1. **Import Database**
   - Create a MySQL database named `bank_management`
   - Import `database/schema.sql`

2. **Configure Database**
   - Edit `config/database.php` with your credentials

3. **Run Server**
   - XAMPP/WAMP: Place project in `htdocs`, access `http://localhost/bank-management/`
   - Or: `php -S localhost:8000`

## Default Login

| Role  | Username | Password  |
|-------|----------|-----------|
| Admin | admin    | admin123  |

## Tech Stack

- PHP
- MySQL
- CSS3
