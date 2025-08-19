# 🎓 Student Portal

A simple student management portal built with **PHP**, **MySQL**, and **XAMPP**.  
It supports student registration, login (with Google OAuth), profile management, and basic CRUD operations.

---

## 📂 Project Structure
student_portal/
│── assets/
│ ├── css/
│ │ └── style.css
│ ├── img/
│ │ └── 1.png
│ └── js/
│ └── script.js
│── uploads/ # Uploaded student images
│── add_student.php # Add new student
│── config.php # Database configuration
│── dashboard.php # Dashboard with KPIs and charts
│── delete_student.php # Delete student record
│── edit_student.php # Edit student details
│── google_login.php # Google OAuth login
│── index.php # Landing / Login page
│── login_process.php # Handles login logic
│── logout.php # Logout functionality
│── profile.php # Student profile page
│── register.php # Registration page
│── students.php # Students listing page
│── Readme.md # Project documentation

---

## ⚙️ Setup Instructions

### 1. Clone the repository
```bash
git clone https://github.com/RakibTwT/student-portal.git
cd student-portal

2. Configure Database

Create a database named student_portal in phpMyAdmin.

Import the provided SQL file (if available).

Update your config.php file with database credentials:
$host = "localhost";
$user = "root";
$password = "";
$dbname = "student_portal";

3. Run on XAMPP

Place the project inside the htdocs folder.

Start Apache and MySQL in XAMPP.

Visit:
http://localhost/student_portal/

🚀 Features

✅ Student registration & login

✅ Google OAuth login integration

✅ Profile management with image upload

✅ Dashboard with KPIs (Total Students, New Students Today, etc.)

✅ CRUD operations (Add, Edit, Delete students)

✅ Responsive design

🛠️ Tech Stack

Frontend: HTML, CSS, JavaScript

Backend: PHP

Database: MySQL

Auth: Google OAuth 2.0

👨‍💻 Author

Rakib Talukder
📧 talukderrakib190@gmail.com

🌐 GitHub Profile

📜 License

This project is licensed under the MIT License – feel free to use and modify it.