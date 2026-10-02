# University Questionnaire & Survey Management System

A comprehensive, enterprise-grade academic survey system built with **Laravel** and **MySQL**. Developed as a **Graduation Project** and adopted as an official university-wide platform serving over **2,000+ active students** and faculty members across multiple departments.

![Laravel](https://img.shields.io/badge/Laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1.svg?style=for-the-badge&logo=mysql&logoColor=white)
![Architecture: RBAC](https://img.shields.io/badge/Security-RBAC_Enforced-orange.svg?style=for-the-badge)
![License: MIT](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)

---

## System Architecture & Workflow

The platform manages the end-to-end survey lifecycle from academic cohort segmentation to automated PDF reporting.

```mermaid
graph TD
    subgraph Roles ["Role-Based Access Control (RBAC)"]
        ADMIN["System Admin / Deans<br/>(Faculty Hierarchy, Template Studio)"]
        FACULTY["Instructors / Evaluators<br/>(Targeted Deployments, Live Analytics)"]
        STUDENT["Students / External Respondents<br/>(Form Responses, Secure Tokens)"]
    end

    subgraph CoreEngine ["Laravel Application Engine"]
        ROUTING["Authentication & Middleware Guard"]
        BUILDER["Dynamic Form & Questionnaire Engine"]
        DEPLOY["Cohort Segmentation & Deployment Manager"]
        QUEUES["Async Job Queue (Mass Email & Reminders)"]
        PDF["PDF Generator (Spatie Browsershot & Puppeteer)"]
    end

    subgraph DataStorage ["Data & Audit Layer (MySQL)"]
        ENTITIES["Academic Structure<br/>(Faculties, Courses, Semesters)"]
        SURVEYS["Questionnaires, Responses, Results"]
        AUDIT["Immutable Audit Trails & Activity Logs"]
    end

    ADMIN -->|"Configure System & Templates"| ROUTING
    FACULTY -->|"Deploy to Specific Cohort"| ROUTING
    STUDENT -->|"Submit Survey Responses"| ROUTING

    ROUTING --> BUILDER
    BUILDER --> DEPLOY
    DEPLOY -->|"Dispatches Jobs"| QUEUES
    DEPLOY -->|"Reads / Writes"| SURVEYS
    SURVEYS -->|"Feeds Analytics"| PDF
    CoreEngine -->|"Tracks Actions"| AUDIT
    CoreEngine -->|"Mirrors Structure"| ENTITIES
```

---

## Core Features

- **Dynamic Questionnaire Builder**: Create complex survey templates supporting 10+ question types, modular categories, and conditional logic.
- **Academic Hierarchy Integration**: Fully mirrors university organizational structures including Faculties, Programs, Semesters, Courses, and Lectures.
- **Targeted Deployment**: Segmented deployment workflows targeting specific cohorts (e.g., students in a specific course, department faculty, or external evaluators).
- **Role-Based Access Control (RBAC)**: Dedicated portals and granular permissions for Deans, Department Heads, Instructors, Students, and System Administrators.
- **Advanced Analytics & Reporting**: Real-time visual data breakdowns with exportable PDF analytical summaries powered by `Spatie\Browsershot`.
- **Background Processing & Audit Trails**: Enterprise activity logging via Audit Logs with asynchronous queue management for mass email distribution and bulk imports.

---

## Architecture & Tech Stack

- **Backend Framework**: Laravel (PHP 8.1+)
- **Database**: MySQL with optimized Eloquent ORM indexing and relational integrity.
- **Report Generation**: `Spatie\Browsershot` (Puppeteer engine) for vector-quality PDF reports.
- **Asynchronous Processing**: Laravel Queues for background batch operations.
- **Frontend & Assets**: Vite with responsive Blade UI components.

---

## Key Data Entities

- `DeployedQuestionnaire`, `QuestionnaireTemplate`, `Question`, `Response`
- `Faculty`, `Program`, `Course`, `SemesterCourse`, `Lecture`
- `User`, `Student`, `FacultyMember`, `ExternalRespondent`
- `AuditLog`, `ImportProgress`, `BgTaskLog`

---

## Getting Started

### Prerequisites
- PHP >= 8.1
- Composer
- Node.js & NPM (for Vite)
- MySQL
- Chromium (for Browsershot PDF exports)

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/AhmedAbeed/questionnaire-system.git
   cd questionnaire-system
   ```

2. Install backend dependencies:
   ```bash
   composer install
   ```

3. Install frontend dependencies and compile assets:
   ```bash
   npm install
   npm run build
   ```

4. Environment Configuration:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Configure your database credentials in `.env`.*

5. Run Migrations & Seeders:
   ```bash
   php artisan migrate --seed
   ```

6. Start the development server:
   ```bash
   php artisan serve
   ```

---

## License

This project is open-source and available under the [MIT License](LICENSE).
