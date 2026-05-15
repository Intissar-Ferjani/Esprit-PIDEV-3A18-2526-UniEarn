# UniEarn – Student Freelancing & E‑Learning Platform (Symfony Version)

## Overview
UniEarn is a comprehensive platform created for the **PIDEV – 3rd Year Engineering Program** at **Esprit School of Engineering** (Academic Year 2025‑2026). It empowers students by bridging academic learning with professional freelancing, allowing them to manage tasks, collaborate on projects, and earn while they learn.


## Features
- **User Management** – Secure registration, login, and profile handling for **clients** and **freelancers**.  
- **Project Marketplace** – Browse, apply for, and manage freelancing opportunities.  
- **Task Management** – Structured workflow for project execution and progress tracking.  
- **Evaluation System** – Integrated feedback and rating mechanism for quality assurance.  
- **Payment Integration** – Safe handling of transactions and escrow services.  
- **Real‑time Communication** – WebSocket‑based chat for seamless collaboration.  
- **Document Generation** – Automated PDF reports & contracts (leveraging **TCPDF**/*dompdf*).  

## Tech Stack

| Layer          | Technology & Version |
|----------------|----------------------|
| **Frontend**   | Twig templating, Bootstrap 5, custom CSS (modern, responsive UI) |
| **Backend**    | Symfony 6 (PHP 8.2) |
| **Database**   | MySQL 8 |
| **Dependency Management** | Composer |
| **Build / Dev** | Symfony CLI, Docker (optional) |
| **Real‑time**  | Mercure / Symfony WebSocket bundle |
| **PDF Generation** | TCPDF / Dompdf |

## Architecture
The project follows the **Model‑View‑Controller (MVC)** pattern promoted by Symfony:

- **Entity** – Doctrine ORM entities (`src/Entity/…`) model the domain objects (User, Project, Task, etc.).
- **Repository** – Data‑access layer (`src/Repository/…`) encapsulates query logic.
- **Service** – Business‑logic layer (`src/Service/…`) coordinates operations.
- **Controller** – HTTP request handling (`src/Controller/…`) delivers Twig views.
- **Twig Templates** – Presentation layer (`templates/…`) renders HTML with modern UI components.
- **WebSocket / Mercure** – Real‑time notifications and chat.

## Contributors
- **Intissar Ferjani**
- **Yassmine Tebrizi**
- - **Eya Ghzaiel**
- **Akrem Arbi**
- **Firas benAli**

## Academic Context
Developed at **Esprit School of Engineering – Tunisia**  
**PIDEV – 3A18 | 2025‑2026**

## Getting Started

### Prerequisites
1. **PHP 8.2** (or later) with the **intl**, **pdo_mysql**, **openssl**, **ctype**, **json**, **xml**, and **gd** extensions enabled.  
2. **Composer** (dependency manager).  
3. **MySQL 8** server (or compatible).  
4. **Node.js** ≥ 16 (optional, for front‑end asset compilation with **Encore**).  
5. **Git** (to clone the repository).  

### Installation Steps
```bash
# 1️⃣ Clone the repository
git clone https://github.com/Intissar-Ferjani/UniEarn-Symfony.git
cd UniEarn-Symfony

# 2️⃣ Install PHP dependencies
composer install

# 3️⃣ Install front‑end assets (optional, for compiled CSS/JS)
npm install
npm run dev   # or `npm run build` for production assets

# 4️⃣ Create the database
#    (adjust credentials in .env.local if needed)
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate   # apply schema

# 5️⃣ Load sample data (optional but helpful)
php bin/console doctrine:fixtures:load --no-interaction

# 6️⃣ Run the Symfony local server
symfony server:start
# Open http://127.0.0.1:8000 in your browser
```

> **Tip:** For a more isolated environment, wrap the above steps in Docker using the provided `docker-compose.yml`.

### Environment Configuration
Copy the example environment file and adjust DB credentials:

```bash
cp .env .env.local
# Edit .env.local:
# DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/uniearn"
```

### Building the Project (Production)
```bash
# Compile assets for production
npm run build

# Dump optimized config and routes
php bin/console cache:clear --env=prod --no-debug
```

Then deploy the `public/` directory to a web server (Apache/Nginx) configured to point to `public/index.php`.

## Documentation & Resources
- **Symfony Docs:** https://symfony.com/doc/current/index.html  
- **Doctrine ORM:** https://www.doctrine-project.org/projects/orm.html  
- **Mercure Hub:** https://mercure.rocks/  

## Acknowledgments
We thank **Esprit School of Engineering** for providing the academic framework, mentorship, and resources that made this project possible.
