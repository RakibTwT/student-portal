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

// ----------------- Fetch all students -----------------
$students_rs = $conn->query("SELECT * FROM students ORDER BY created_at DESC");

// ----------------- Charts Data -----------------

// Students per Course (Bar)
$course_labels = [];
$course_data   = [];
$course_rs = $conn->query("
    SELECT COALESCE(NULLIF(course,''),'Unspecified') AS course_name, COUNT(*) AS total
    FROM students
    GROUP BY course_name
    ORDER BY total DESC
");
while($row = $course_rs->fetch_assoc()){
    $course_labels[] = $row['course_name'];
    $course_data[] = (int)$row['total'];
}

// Gender distribution (Pie)
$gender_labels = [];
$gender_data   = [];
$gender_rs = $conn->query("
    SELECT COALESCE(NULLIF(gender,''),'Unspecified') AS gender, COUNT(*) AS total
    FROM students
    GROUP BY gender
");
while($row = $gender_rs->fetch_assoc()){
    $gender_labels[] = $row['gender'];
    $gender_data[] = (int)$row['total'];
}

// New students per month (Line)
$month_labels = [];
$month_data   = [];
$month_rs = $conn->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS total
    FROM students
    GROUP BY month
    ORDER BY month ASC
");
while($row = $month_rs->fetch_assoc()){
    $month_labels[] = $row['month'];
    $month_data[] = (int)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Students - Student Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* ----------------- General ----------------- */
body {margin:0;font-family:'Segoe UI',sans-serif;display:flex;min-height:100vh;background:#f5f7fb;transition:0.3s;}
a{text-decoration:none;color:inherit;}
h2{color:#1e293b;}

/* ----------------- Sidebar ----------------- */
.sidebar {width:260px;background:#1f2937;color:white;display:flex;flex-direction:column;transition:0.3s;position:relative;}
.sidebar.collapsed {width:70px;}
.sidebar h2{text-align:center;padding:20px 0;font-size:1.4rem;font-weight:700;border-bottom:1px solid rgba(255,255,255,0.2);}
.sidebar h2 span{color:white;} /* <-- FIX: make title visible */
.sidebar.collapsed h2 span{display:none;}
.sidebar a{color:white;padding:15px 20px;display:flex;align-items:center;gap:15px;transition:0.3s;cursor:pointer;position:relative;}
.sidebar a i{width:20px;text-align:center;}
.sidebar a:hover,.sidebar a.active{background:#2563eb;}
.sidebar .submenu{display:none;flex-direction:column;padding-left:20px;}
.sidebar a.toggle-submenu::after{content:'\f078'; font-family:'Font Awesome 6 Free'; font-weight:900; margin-left:auto; transition:0.3s;}
.sidebar a.toggle-submenu.active::after{transform:rotate(-180deg);}
.sidebar.collapsed a span{display:none;}
.sidebar.collapsed .submenu{position:absolute; left:70px; background:#111827; top:auto; display:none; width:200px; border-radius:6px; z-index:99;}
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
.sidebar a.secondary-btn {background: #10b981; margin: 8px 15px; border-radius:6px; text-align:center; font-weight:600; transition:0.3s;}
.sidebar a.secondary-btn:hover {background:#059669;}
#sidebarToggle{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);cursor:pointer;color:white;font-size:18px;}

/* ----------------- Main ----------------- */
.main{flex:1;padding:20px 30px;transition:0.3s;}
.topbar{display:flex;justify-content:flex-end;align-items:center;margin-bottom:20px;}
.dropdown{position:relative;}
.dropbtn{display:flex;align-items:center;gap:8px;background:#2563eb;color:white;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;font-weight:600;transition:0.3s;}
.dropbtn:hover{background:#1e40af;}
.dropdown-content{display:none;position:absolute;right:0;background:white;color:#333;min-width:160px;box-shadow:0 4px 8px rgba(0,0,0,0.2);border-radius:8px;z-index:1;}
.dropdown-content a{color:#333;padding:12px 16px;display:block;text-decoration:none;}
.dropdown-content a:hover{background:#f1f1f1;}
.dropdown:hover .dropdown-content{display:block;}

/* ----------------- Table ----------------- */
table{width:100%;border-collapse:collapse;margin-top:20px;border-radius:8px;overflow:hidden;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,0.08);}
th,td{text-align:left;padding:12px;border-bottom:1px solid #ddd;}
th{background:#2563eb;color:white;}
tr:hover{background:#f1f7ff;}
.table-actions a{margin-right:5px;padding:5px 10px;border-radius:6px;text-decoration:none;color:white;font-weight:600;}
.table-actions a.edit{background:#fbbf24;}
.table-actions a.edit:hover{background:#f59e0b;}
.table-actions a.delete{background:#ef4444;}
.table-actions a.delete:hover{background:#dc2626;}

/* ----------------- Charts ----------------- */
.chart-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px,1fr));
    gap:25px;
    margin-top:40px;
}
.chart-container {
    background:#fff;
    padding:25px 20px;
    border-radius:12px;
    box-shadow:0 4px 16px rgba(0,0,0,0.08);
    height:350px;
    display:flex;
    flex-direction:column;
}
.chart-container h3 {
    text-align:center;
    margin-bottom:20px;
    color:#2563eb;
    font-size:1.2rem;
    font-weight:600;
}
canvas {flex:1;}
@media(max-width:768px){.chart-row{grid-template-columns:1fr;}}
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <h2><span>📚 Student Portal</span></h2>
    <a href="dashboard.php" data-tooltip="Dashboard"><i class="fas fa-home"></i><span>Dashboard</span></a>
    <a href="add_student.php" class="secondary-btn" data-tooltip="Add Student"><i class="fas fa-plus-circle"></i><span>Add Student</span></a>
    <a href="students.php" class="active" data-tooltip="Manage Students"><i class="fas fa-user-graduate"></i><span>Manage Students</span></a>
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
            <button class="dropbtn"><?php echo htmlspecialchars($user['name']); ?> <i class="fas fa-chevron-down"></i></button>
            <div class="dropdown-content">
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <h2>Manage Students</h2>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Course</th>
                <th>Year</th>
                <th>GPA</th>
                <th>Advisor</th>
                <th>Enrollment ID</th>
                <th>Gender</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if($students_rs && $students_rs->num_rows>0): ?>
                <?php while($s=$students_rs->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['name']); ?></td>
                        <td><?php echo htmlspecialchars($s['email']); ?></td>
                        <td><?php echo htmlspecialchars($s['course']); ?></td>
                        <td><?php echo htmlspecialchars($s['year']); ?></td>
                        <td><?php echo htmlspecialchars($s['gpa']); ?></td>
                        <td><?php echo htmlspecialchars($s['advisor']); ?></td>
                        <td><?php echo htmlspecialchars($s['enrollment_id']); ?></td>
                        <td><?php echo htmlspecialchars($s['gender']); ?></td>
                        <td><?php echo htmlspecialchars($s['created_at']); ?></td>
                        <td class="table-actions">
                            <a href="edit_student.php?id=<?php echo $s['id']; ?>" class="edit">Edit</a>
                            <a href="delete_student.php?id=<?php echo $s['id']; ?>" class="delete" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="10" style="text-align:center;">No students found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Charts -->
    <div class="chart-row">
        <div class="chart-container">
            <h3>Students per Course</h3>
            <canvas id="barCourse"></canvas>
        </div>
        <div class="chart-container">
            <h3>Gender Distribution</h3>
            <canvas id="pieGender"></canvas>
        </div>
        <div class="chart-container">
            <h3>New Students per Month</h3>
            <canvas id="lineMonth"></canvas>
        </div>
    </div>
</div>

<script>
// Sidebar submenu toggle
document.querySelectorAll('.sidebar .toggle-submenu').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        btn.classList.toggle('active');
        const submenu = btn.nextElementSibling;
        submenu.style.display = submenu.style.display==='flex'?'none':'flex';
        submenu.style.flexDirection='column';
    });
});
// Collapsible sidebar
const sidebar=document.getElementById('sidebar');
const toggleBtn=document.getElementById('sidebarToggle');
toggleBtn.addEventListener('click',()=>{sidebar.classList.toggle('collapsed');});

// Charts
const courseChart = new Chart(document.getElementById('barCourse'),{
    type:'bar',
    data:{
        labels: <?php echo json_encode($course_labels); ?>,
        datasets:[{label:'Students per Course', data: <?php echo json_encode($course_data); ?>, backgroundColor:'#2563eb'}]
    },
    options:{responsive:true, maintainAspectRatio:false, scales:{y:{beginAtZero:true}}}
});
const genderChart = new Chart(document.getElementById('pieGender'),{
    type:'pie',
    data:{
        labels: <?php echo json_encode($gender_labels); ?>,
        datasets:[{data: <?php echo json_encode($gender_data); ?>, backgroundColor:['#2563eb','#dc2626','#facc15','#16a34a']}]
    },
    options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}}}
});
const lineChart = new Chart(document.getElementById('lineMonth'),{
    type:'line',
    data:{
        labels: <?php echo json_encode($month_labels); ?>,
        datasets:[{
            label:'New Students per Month',
            data: <?php echo json_encode($month_data); ?>,
            borderColor:'#2563eb',
            backgroundColor:'rgba(37,99,235,0.2)',
            fill:true,
            tension:0.3,
            pointRadius:4,
            pointBackgroundColor:'#2563eb'
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        scales:{
            y:{beginAtZero:true, precision:0},
            x:{ticks:{maxRotation:45, minRotation:45}}
        },
        plugins:{legend:{display:false}}
    }
});
</script>

</body>
</html>
