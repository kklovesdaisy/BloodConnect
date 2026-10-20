# 🩸 BloodConnect — Blood Donation Management System

**BloodConnect** is a web-based blood donation management system developed using PHP and MySQL. It aims to connect patients who need blood with suitable donors and help administrators manage users, blood requests, and donation activities.

The project follows a **Domain-Driven Design (DDD)-inspired architecture** to separate business logic, application services, infrastructure, and presentation concerns.

## ✨ Key Features

### 👤 Patient Dashboard
- Register and log in to the system.
- Request blood when needed.
- Search for suitable blood donors.
- Track blood request status.
- Receive notifications about request updates.
- Cancel blood requests when applicable.

### 🩸 Donor Dashboard
- Manage donor profile and availability.
- View blood donation requests.
- Accept or decline donation requests.
- View donation history.
- Track donation eligibility and next available donation date.
- Receive notifications about blood requests.

### 🛡️ Admin Dashboard
- Manage users and donors.
- Monitor and manage blood requests.
- Manage blood groups and master data.
- Manage notifications and system information.

### 🔐 Authentication and Authorization
- User registration and login.
- Email verification workflow.
- Role-based access control (RBAC).
- Different dashboards and permissions for administrators, donors, and patients.

### ⚙️ Business Logic
- Prevent duplicate blood requests where applicable.
- Enforce donor eligibility rules, including a three-month donation cooldown.
- Prevent donors from accepting requests when they have an active accepted request.
- Maintain donation history and update donor availability after an accepted donation workflow.

## 🛠️ Technologies Used

| Technology | Purpose |
|---|---|
| PHP | Backend development |
| MySQL | Relational database |
| PDO | Database access |
| HTML5 | Page structure |
| CSS3 | Styling |
| JavaScript | Client-side interactivity |
| Git | Version control |
| GitHub | Source code hosting |
| XAMPP | Local development environment |

## 🏗️ Architecture

BloodConnect uses a DDD-inspired layered structure to improve separation of concerns and maintainability.

```text
BloodConnect/
├── App/
│   ├── Admin/
│   │   ├── Application/
│   │   ├── Domain/
│   │   ├── Infrastructure/
│   │   └── Presentation/
│   ├── Authentication/
│   ├── BloodRequest/
│   ├── Donation/
│   ├── Donor/
│   ├── Notification/
│   ├── Shared/
│   └── User/
├── public/
└── README.md
```

*This is a simplified overview. Folder names and layers may vary in the current implementation.*

### Architectural Concepts

- **Domain Layer:** Contains business rules and domain concepts.
- **Application Layer:** Coordinates use cases and application workflows.
- **Infrastructure Layer:** Handles technical concerns such as database access.
- **Presentation Layer:** Handles user interaction and application responses.
- **Shared Components:** Contains reusable functionality used across modules.

## 🚀 Getting Started

### Prerequisites

Install the following tools before running the project:

- [XAMPP](https://www.apachefriends.org/) — Apache, PHP, and MySQL
- [Git](https://git-scm.com/)
- A modern web browser
- A code editor such as Visual Studio Code

### 1. Clone the repository

```bash
git clone https://github.com/kklovesdaisy/BloodConnect.git
```

### 2. Move into the project directory

```bash
cd BloodConnect
```

### 3. Place the project in XAMPP

Copy or clone the project into your XAMPP `htdocs` directory. For example:

```text
C:\xampp_new\htdocs\BloodConnect
```

### 4. Start Apache and MySQL

Open the XAMPP Control Panel and start **Apache** and **MySQL**.

### 5. Configure the database

1. Open phpMyAdmin at `http://localhost/phpmyadmin/`.
2. Create the database using the name expected by the project configuration.
3. Import the project's SQL file if one is included.
4. Configure the database connection settings for your local environment.

Check the project source for the exact database name, schema, and configuration file before running the application.

### 6. Open the application

Visit the appropriate local URL in your browser:

```text
http://localhost/BloodConnect/public/
```

The exact URL may depend on the application's entry point and routing configuration.

## 🔄 Example Workflow

1. A patient registers and verifies their email address.
2. The patient logs in and submits a blood request.
3. The system checks the request and identifies suitable donors according to the implemented business rules.
4. Donors receive notifications and can accept or decline requests.
5. The system records donation activity and updates the donor's next available date according to the business rules.
6. Administrators can monitor users and blood requests.

## 🎯 Project Objectives

- Build a practical blood donation management application.
- Apply object-oriented programming principles in PHP.
- Practice DDD-inspired architecture and layered design.
- Implement authentication and role-based authorization.
- Organize business rules into maintainable modules.
- Work with MySQL, PDO, Git, and GitHub.
- Develop reliable workflows for blood requests and donation tracking.

## 👩‍💻 Author

**Htet Nandar Winn**

- GitHub: [@kklovesdaisy](https://github.com/kklovesdaisy)
- Project repository: [BloodConnect](https://github.com/kklovesdaisy/BloodConnect)

## 🔒 Security Notes

- Never commit `.env` files, database passwords, API keys, or other secrets.
- Configure credentials locally and exclude sensitive configuration from Git.
- Validate user input and enforce authorization on the server side.
- Do not treat a hidden button or dashboard as a substitute for backend permission checks.

## 📄 License

No license is specified in this README. Add a license file if you intend to define permissions for reuse, modification, and distribution.

---

*BloodConnect — Connecting patients with potential blood donors and supporting a more organized donation process.*
