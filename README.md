# Laravel Project Setup

This is a Laravel application designed to work purely as a backend API or service without the need for any frontend. This guide will help you set up the project, configure your environment, and run migrations and seeders.

## Prerequisites

Make sure you have the following installed on your machine:

* **PHP** (8.2 - 8.4) - Laravel 12.x requires PHP 8.2 - 8.4.
* **Composer** - Dependency manager for PHP.
* **MySQL** or any other database supported by Laravel.
* **Git** - To clone the repository.

## Steps to Set Up the Laravel Project

### 1. **Clone the Repository**

First, clone the repository to your local machine:

```bash
git clone https://github.com/algavania/technical-be
cd technical-be
```

### 2. **Install PHP Dependencies**

Run the following command to install the required PHP dependencies using **Composer**:

```bash
composer install
```

### 3. **Set Up Environment File**

Laravel uses an `.env` file for environment-specific configurations. Copy the `.env.example` file to create your `.env` file:

```bash
cp .env.example .env
```

### 4. **Generate the Application Key**

Laravel requires an application key for encryption. Run the following command to generate it:

```bash
php artisan key:generate
```

This will automatically set the `APP_KEY` in your `.env` file.

### 5. **Configure the Database**

Open the `.env` file and configure your database connection settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
```

Make sure the database exists and the credentials are correct.

### 6. **Run Database Migrations**

Now, run the migrations to create the necessary tables in your database:

```bash
php artisan migrate
```

### 7. **Seed the Database**

To populate the database with sample data, run the database seeders:

```bash
php artisan db:seed
```

This command will run the default seeder class, which is located in `database/seeders/DatabaseSeeder.php`.

### 8. **Run the Application**

You can run the Laravel backend API locally using Laravel's built-in development server:

```bash
php artisan serve
```

By default, the application will be accessible at `http://127.0.0.1:8000`.
