<?php
require_once 'config.php';
if(session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location:index.php"); exit(); }

// Fetch logged-in user
$stmt = $conn->prepare("SELECT name,email FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ---------- KPIs ----------
$total_students = $conn->query("SELECT COUNT(*) AS cnt FROM students")->fetch_assoc()['cnt'] ?? 0;
$total_courses  = $conn->query("SELECT COUNT(DISTINCT course) AS cnt FROM students")->fetch_assoc()['cnt'] ?? 0;
$new_students_today = $conn->query("SELECT COUNT(*) AS cnt FROM students WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['cnt'] ?? 0;

// ---------- Recent Students ----------
$recent_students = [];
$res = $conn->query("SELECT name, email, course FROM students ORDER BY id DESC LIMIT 5");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $recent_students[] = $row;
    }
}

// ---------- Top 5 Courses ----------
$top_courses = [];
$res = $conn->query("SELECT course, COUNT(*) AS cnt FROM students GROUP BY course ORDER BY cnt DESC LIMIT 5");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $top_courses[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Student Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/countup.js/2.6.2/countUp.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
/* --- keep your existing CSS unchanged --- */
body{margin:0;font-family:'Segoe UI',sans-serif;display:flex;min-height:100vh;background:#f5f7fb;}
a{text-decoration:none;color:inherit;}
.sidebar{width:260px;background:#1f2937;color:white;display:flex;flex-direction:column;transition:0.3s;position:relative;}
.sidebar.collapsed{width:70px;}
.sidebar h2{text-align:center;padding:20px 0;font-size:1.4rem;font-weight:700;border-bottom:1px solid rgba(255,255,255,0.2);}
.sidebar.collapsed h2 span{display:none;}
.sidebar a{color:white;padding:15px 20px;display:flex;align-items:center;gap:15px;transition:0.3s;cursor:pointer;position:relative;}
.sidebar a i{width:20px;text-align:center;}
.sidebar a:hover,.sidebar a.active{background:#2563eb;}
.sidebar .submenu{display:none;flex-direction:column;padding-left:20px;}
.sidebar a.toggle-submenu::after{content:'\f078'; font-family:'Font Awesome 6 Free'; font-weight:900;margin-left:auto;transition:0.3s;}
.sidebar a.toggle-submenu.active::after{transform:rotate(-180deg);}
.sidebar.collapsed a span{display:none;}
.sidebar.collapsed .submenu{position:absolute; left:70px; background:#111827; top:auto; display:none; width:200px; border-radius:6px; z-index:99;}
.sidebar a[data-tooltip]::after{display:none;}
.sidebar.collapsed a[data-tooltip]:hover::after{content: attr(data-tooltip); position:absolute; left:70px; top:50%; transform:translateY(-50%); background:#2563eb; padding:5px 10px; border-radius:6px; white-space: nowrap; font-size:0.9rem; color:white; display:block; z-index:1000;}
#sidebarToggle{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);cursor:pointer;color:white;font-size:18px;}
.main{flex:1;padding:20px 30px;}
.topbar{display:flex;justify-content:flex-end;align-items:center;margin-bottom:20px;}
.dropdown{position:relative;}
.dropbtn{display:flex;align-items:center;gap:8px;background:#2563eb;color:white;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;font-weight:600;}
.dropbtn:hover{background:#1e40af;}
.dropdown-content{display:none;position:absolute;right:0;background:white;color:#333;min-width:160px;box-shadow:0 4px 8px rgba(0,0,0,0.2);border-radius:8px;z-index:1;}
.dropdown-content a{color:#333;padding:12px 16px;display:block;text-decoration:none;}
.dropdown-content a:hover{background:#f1f1f1;}
.dropdown:hover .dropdown-content{display:block;}
.kpi-cards{display:flex;flex-wrap:wrap;gap:20px;margin-bottom:30px;}
.kpi-card{flex:1;min-width:180px;background:white;padding:20px;border-radius:12px;box-shadow:0 3px 12px rgba(0,0,0,0.1);text-align:center;}
.kpi-card h3{font-size:1rem;color:#2563eb;margin-bottom:10px;}
.kpi-card p{font-size:1.6rem;font-weight:700;color:#1e293b;}
.quick-actions{display:flex;gap:15px;flex-wrap:wrap;margin-bottom:30px;}
.quick-actions a{flex:1;min-width:120px;padding:20px;text-align:center;background:#2563eb;color:white;font-weight:600;border-radius:12px;text-decoration:none;}
.quick-actions a:hover{background:#1e40af;transform:translateY(-3px);}
table{width:100%;border-collapse:collapse;margin-top:20px;border-radius:8px;overflow:hidden;background:white;}
th,td{text-align:left;padding:12px;border-bottom:1px solid #ddd;}
th{background:#2563eb;color:white;}
tr:hover{background:#f1f7ff;}
.table-actions a{margin-right:5px;padding:5px 10px;border-radius:6px;text-decoration:none;color:white;}
.table-actions a.edit{background:#fbbf24;}
.table-actions a.edit:hover{background:#f59e0b;}
.table-actions a.delete{background:#ef4444;}
.table-actions a.delete:hover{background:#dc2626;}
.chart-row{display:flex;gap:20px;flex-wrap:wrap;margin-top:40px;}
.chart-container{flex:1;min-width:280px;background:white;padding:20px;border-radius:12px;box-shadow:0 3px 12px rgba(0,0,0,0.1);height:auto;}
.chart-container h3{text-align:center;margin-bottom:15px;color:#2563eb;}
.course-item{display:flex;justify-content:space-between;padding:10px 15px;margin-bottom:10px;background:#f1f5f9;border-radius:8px;font-weight:600;}
.course-item span{font-weight:700;color:#2563eb;}
@media(max-width:768px){.sidebar{width:70px;}.main{padding:15px;}}
</style>
</head>
<body>
<div class="sidebar" id="sidebar">
<h2><span>📚 Student Portal</span></h2>
<a href="dashboard.php" class="active" data-tooltip="Dashboard"><i class="fas fa-home"></i><span>Dashboard</span></a>
<a href="students.php" data-tooltip="Manage Students"><i class="fas fa-user-graduate"></i><span>Manage Students</span></a>
<a href="add_student.php" data-tooltip="Add Student"><i class="fas fa-plus-circle"></i><span>Add Student</span></a>
<a class="toggle-submenu" data-tooltip="Settings"><i class="fas fa-cog"></i><span>Settings</span></a>
<div class="submenu">
<a href="profile.php" data-tooltip="Profile"><i class="fas fa-user"></i><span>Profile</span></a>
<a href="logout.php" data-tooltip="Logout"><i class="fas fa-right-from-bracket"></i><span>Logout</span></a>
</div>
<div id="sidebarToggle"><i class="fas fa-angle-double-left"></i></div>
</div>

<div class="main">
<div class="topbar">
<div class="dropdown">
<button class="dropbtn"><?= htmlspecialchars($user['name']); ?> <i class="fas fa-chevron-down"></i>
<span id="notifBadge" style="background:red;color:white;padding:2px 6px;border-radius:50%;font-size:0.8rem;display:none;"></span>
</button>
<div class="dropdown-content">
<a href="profile.php">Profile</a>
<a href="logout.php">Logout</a>
</div>
</div>
</div>

<div class="kpi-cards">
<div class="kpi-card"><h3>Total Students</h3><p id="totalStudents"><?= $total_students ?></p></div>
<div class="kpi-card"><h3>Total Courses</h3><p id="totalCourses"><?= $total_courses ?></p></div>
<div class="kpi-card"><h3>New Students Today</h3><p id="newStudentsToday"><?= $new_students_today ?></p></div>
</div>

<div class="quick-actions">
<a href="add_student.php"><i class="fas fa-plus-circle"></i> Add Student</a>
<a href="students.php"><i class="fas fa-user-graduate"></i> Manage Students</a>
<a href="profile.php"><i class="fas fa-user"></i> Profile</a>
<a href="logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>

<h2>Recent Students</h2>
<table id="recentStudents">
<thead><tr><th>Name</th><th>Email</th><th>Course</th></tr></thead>
<tbody>
<?php foreach($recent_students as $s): ?>
<tr>
<td><?= htmlspecialchars($s['name']) ?></td>
<td><?= htmlspecialchars($s['email']) ?></td>
<td><?= htmlspecialchars($s['course']) ?></td>
</tr>
<?php endforeach; ?>
<?php if(empty($recent_students)): ?>
<tr><td colspan="3">No recent students found.</td></tr>
<?php endif; ?>
</tbody>
</table>

<div class="chart-row">
<div class="chart-container">
<h3>Top 5 Courses</h3>
<?php if(!empty($top_courses)): ?>
    <?php foreach($top_courses as $c): ?>
    <div class="course-item">
        <span><?= htmlspecialchars($c['course']) ?></span>
        <span><?= $c['cnt'] ?></span>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align:center;color:#888;">No course data available.</p>
<?php endif; ?>
</div>
</div>

<script>
const sidebar=document.getElementById('sidebar');
document.getElementById('sidebarToggle').addEventListener('click',()=>{sidebar.classList.toggle('collapsed');});
document.querySelectorAll('.sidebar .toggle-submenu').forEach(btn=>{
btn.addEventListener('click',()=>{
btn.classList.toggle('active');
const sub=btn.nextElementSibling;
sub.style.display=sub.style.display==='flex'?'none':'flex';
sub.style.flexDirection='column';
});
});

// CountUp KPIs
let totalStudentsCount=new CountUp('totalStudents', <?= $total_students ?>);
let totalCoursesCount=new CountUp('totalCourses', <?= $total_courses ?>);
let newStudentsTodayCount=new CountUp('newStudentsToday', <?= $new_students_today ?>);
totalStudentsCount.start();
totalCoursesCount.start();
newStudentsTodayCount.start();
</script>
</body>
</html>
