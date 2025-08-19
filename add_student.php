<?php
require_once 'config.php';

// ----------------- Protect page -----------------
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// ----------------- Fetch logged-in user -----------------
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $course = trim($_POST['course']);
    $enrollment_id = trim($_POST['enrollment_id']);
    $year = trim($_POST['year']);
    $gpa = trim($_POST['gpa']);
    $advisor = trim($_POST['advisor']);
    $notes = trim($_POST['notes']);

    // Validation
    if (empty($name) || empty($email) || empty($course)) {
        $error = "Name, Email, and Course are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Email already exists for another student.";
        }
        $stmt->close();
    }

    // Insert student
    if (!$error) {
        $stmt = $conn->prepare("INSERT INTO students 
            (name,email,dob,gender,phone,address,course,enrollment_id,year,gpa,advisor,notes)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param(
            "sssssssssdss",
            $name, $email, $dob, $gender, $phone, $address, $course,
            $enrollment_id, $year, $gpa, $advisor, $notes
        );
        if ($stmt->execute()) {
            $success = "Student added successfully.";
        } else {
            $error = "Failed to add student.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Student - Student Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {margin:0;font-family:'Segoe UI',sans-serif;display:flex;min-height:100vh;background:#f5f7fb;transition:0.3s;}
a {text-decoration:none;}
/* Sidebar */
.sidebar {width:260px;background:#1f2937;color:white;display:flex;flex-direction:column;transition:0.3s;position:relative;}
.sidebar.collapsed {width:70px;}
.sidebar h2{text-align:center;padding:20px 0;font-size:1.4rem;font-weight:700;border-bottom:1px solid rgba(255,255,255,0.2);}
.sidebar.collapsed h2 span{display:none;}
.sidebar a{color:white;padding:15px 20px;display:flex;align-items:center;gap:15px;transition:0.3s;cursor:pointer;position:relative;}
.sidebar a i{width:20px;text-align:center;}
.sidebar a:hover, .sidebar a.active{background:#2563eb;}
.sidebar .submenu{display:none;flex-direction:column;padding-left:20px;}
.sidebar a.toggle-submenu::after{content:'\f078';font-family:'Font Awesome 6 Free';font-weight:900;margin-left:auto;transition:0.3s;}
.sidebar a.toggle-submenu.active::after{transform:rotate(-180deg);}
.sidebar.collapsed a span{display:none;}
.sidebar.collapsed .submenu{position:absolute;left:70px;background:#111827;top:auto;display:none;width:200px;border-radius:6px;z-index:99;}
.sidebar a[data-tooltip]::after{display:none;}
.sidebar.collapsed a[data-tooltip]:hover::after{
    content: attr(data-tooltip);
    position: absolute;
    left: 70px;
    top: 50%;
    transform: translateY(-50%);
    background:#2563eb;
    padding:5px 10px;
    border-radius:6px;
    white-space: nowrap;
    font-size:0.9rem;
    color:white;
    display:block;
    z-index:1000;
}
/* Main */
.main{flex:1;padding:20px 30px;transition:0.3s;}
.topbar{display:flex;justify-content:flex-end;align-items:center;margin-bottom:20px;}
.dropdown{position:relative;}
.dropbtn{display:flex;align-items:center;gap:8px;background:#2563eb;color:white;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;font-weight:600;transition:0.3s;}
.dropbtn:hover{background:#1e40af;}
.dropdown-content{display:none;position:absolute;right:0;background:white;color:#333;min-width:160px;box-shadow:0 4px 8px rgba(0,0,0,0.2);border-radius:8px;z-index:1;}
.dropdown-content a{color:#333;padding:12px 16px;display:block;text-decoration:none;}
.dropdown-content a:hover{background:#f1f1f1;}
.dropdown:hover .dropdown-content{display:block;}

/* Card-style heading */
.page-header{
    background:#2563eb;color:white;padding:20px 25px;border-radius:12px;
    display:flex;align-items:center;gap:15px;margin-bottom:25px;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
}
.page-header i{font-size:1.5rem;}

/* Form styling */
form{background:white;padding:25px;border-radius:12px;box-shadow:0 3px 12px rgba(0,0,0,0.1);max-width:700px;margin:0 auto;}
label{display:block;margin-top:12px;font-weight:600;}
input, select, textarea{width:100%;padding:10px;margin-top:5px;border-radius:6px;border:1px solid #ccc;}
button{margin-top:20px;padding:12px 25px;background:#2563eb;color:white;border:none;border-radius:6px;cursor:pointer;font-weight:600;}
button:hover{background:#1e40af;}
.error{color:#d93025;margin-bottom:10px;font-weight:600;}
.success{color:#16a34a;margin-bottom:10px;font-weight:600;}
</style>
</head>
<body>
<div class="sidebar" id="sidebar">
    <h2><span>📚 Student Portal</span></h2>
    <a href="dashboard.php" data-tooltip="Dashboard"><i class="fas fa-home"></i><span>Dashboard</span></a>
    <a href="students.php" data-tooltip="Manage Students"><i class="fas fa-user-graduate"></i><span>Manage Students</span></a>
    <a href="add_student.php" class="active" data-tooltip="Add Student"><i class="fas fa-plus-circle"></i><span>Add Student</span></a>
    <a class="toggle-submenu" data-tooltip="Settings"><i class="fas fa-cog"></i><span>Settings</span></a>
    <div class="submenu">
        <a href="profile.php" data-tooltip="Profile"><i class="fas fa-user"></i><span>Profile</span></a>
        <a href="logout.php" data-tooltip="Logout"><i class="fas fa-right-from-bracket"></i><span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="dropdown">
            <button class="dropbtn"><?php echo htmlspecialchars($user['name']); ?> <i class="fas fa-chevron-down"></i></button>
            <div class="dropdown-content">
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <!-- Card-style header -->
    <div class="page-header">
        <i class="fas fa-user-plus"></i>
        <h2>Add New Student</h2>
    </div>

    <?php if($error) echo "<p class='error'>$error</p>"; ?>
    <?php if($success) echo "<p class='success'>$success</p>"; ?>

    <form method="post">
        <label>Full Name *</label>
        <input type="text" name="name" required>

        <label>Email *</label>
        <input type="email" name="email" required>

        <label>Date of Birth</label>
        <input type="date" name="dob">

        <label>Gender</label>
        <select name="gender">
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select>

        <label>Phone</label>
        <input type="text" name="phone">

        <label>Address</label>
        <textarea name="address"></textarea>

        <label>Course *</label>
        <input type="text" name="course" required>

        <label>Enrollment ID</label>
        <input type="text" name="enrollment_id">

        <label>Year / Semester</label>
        <input type="text" name="year">

        <label>GPA / Grade</label>
        <input type="number" step="0.01" name="gpa">

        <label>Advisor / Teacher</label>
        <input type="text" name="advisor">

        <label>Notes / Comments</label>
        <textarea name="notes"></textarea>

        <button type="submit"><i class="fas fa-plus-circle"></i> Add Student</button>
    </form>
</div>

<script>
// Sidebar submenu toggle
document.querySelectorAll('.sidebar .toggle-submenu').forEach(btn=>{
    btn.addEventListener('click',()=>{
        btn.classList.toggle('active');
        const submenu = btn.nextElementSibling;
        submenu.style.display = submenu.style.display==='flex'?'none':'flex';
        submenu.style.flexDirection='column';
    });
});
</script>
</body>
</html>
