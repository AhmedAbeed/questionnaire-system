# 📊 University Questionnaire & Survey System

A comprehensive, enterprise-grade academic survey system built with **Laravel**. This platform is designed to manage, deploy, and analyze questionnaires across the entire university ecosystem, supporting students, faculty members, and external respondents.

![Laravel](https://img.shields.io/badge/Laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-%2300f.svg?style=for-the-badge&logo=mysql&logoColor=white)

## ✨ Core Features

- **Dynamic Questionnaire Builder**: Create complex survey templates with multiple question types, categories, and options.
- **Academic Structure Management**: Fully supports the university hierarchy including Faculties, Programs, Semesters, Courses, and Lectures.
- **Targeted Deployment**: Deploy questionnaires to specific target audiences (e.g., Students in a specific course, Faculty Members, or External Respondents).
- **Role-Based Access**: Specialized interfaces and permissions for Deans, Instructors, Students, and System Admins.
- **Advanced Analytics & Reporting**: Generate comprehensive analytical reports with visual data. Supports PDF report downloads powered by `Spatie\Browsershot`.
- **Background Processing & Auditing**: Tracks all system activities using Audit Logs and processes heavy tasks (like mass emails or imports) asynchronously.

## 🛠️ Architecture & Tech Stack

- **Backend Framework**: Laravel (PHP)
- **Database**: Relational DB (MySQL/PostgreSQL) with advanced Eloquent ORM relationships.
- **Report Generation**: `Spatie\Browsershot` (Puppeteer) for high-quality PDF exports.
- **Job Queues**: Built-in Laravel queuing for background tasks and reminder emails.
- **Frontend Assets**: Managed via Vite.

## 🗄️ Key Data Models

- `DeployedQuestionnaire`, `QuestionnaireTemplate`, `Question`, `Response`
- `Faculty`, `Program`, `Course`, `SemesterCourse`, `Lecture`
- `User`, `Student`, `FacultyMember`, `ExternalRespondent`
- `AuditLog`, `ImportProgress`, `BgTaskLog`

## 🚀 Getting Started

### Prerequisites
- PHP >= 8.1
- Composer
- Node.js & NPM (for Vite)
- MySQL / PostgreSQL
- Chrome/Chromium (Required by Browsershot for PDF generation)

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/AhmedAbeed/questionnaire-system.git
   cd questionnaire-system
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install frontend dependencies:
   ```bash
   npm install
   npm run build
   ```

4. Environment Setup:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Configure your database credentials in the `.env` file.*

5. Run Database Migrations & Seeders:
   ```bash
   php artisan migrate --seed
   ```

6. Start the server:
   ```bash
   php artisan serve
   ```

## 🤝 Contributing
Contributions, issues, and feature requests are welcome!

## 📝 License
This project is open-source and available under the [MIT License](LICENSE).
