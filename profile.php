<?php
require_once 'config.php';

// Protect page
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch user info
$stmt = $conn->prepare("SELECT name, email, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle profile image upload
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $targetDir = "assets/uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    $fileName = basename($_FILES["profile_image"]["name"]);
    $targetFilePath = $targetDir . time() . '_' . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    $allowedTypes = ['jpg','jpeg','png','gif'];
    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $targetFilePath, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $message = "Profile image updated successfully.";
                $user['profile_image'] = $targetFilePath;
            } else $message = "Error updating database.";
            $stmt->close();
        } else $message = "Error uploading file.";
    } else $message = "Only JPG, JPEG, PNG & GIF allowed.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - Student Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {margin:0;font-family:'Segoe UI',sans-serif;display:flex;min-height:100vh;background:#f5f7fb;transition:0.3s;}
a{text-decoration:none;color:inherit;}
h2{color:#1e293b;}

/* Sidebar */
.sidebar {width:260px;background:#1f2937;color:white;display:flex;flex-direction:column;transition:0.3s;position:relative;}
.sidebar.collapsed {width:70px;}
.sidebar h2{text-align:center;padding:20px 0;font-size:1.4rem;font-weight:700;border-bottom:1px solid rgba(255,255,255,0.2);}
.sidebar h2 span{color:white;}
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

/* Profile Card */
.profile-card {max-width:400px;margin:30px auto;padding:30px;border-radius:12px;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,0.08);text-align:center;}
.profile-card img {width:130px;height:130px;border-radius:50%;object-fit:cover;border:3px solid #2563eb;margin-bottom:15px;transition:0.3s;}
.profile-card h3 {margin-bottom:5px;}
.profile-card p {color:#555;margin-bottom:15px;font-weight:500;}
.profile-card input[type="file"]{margin-bottom:10px;}
.profile-card button{width:100%;background:#2563eb;color:white;border:none;padding:10px;border-radius:6px;font-weight:600;cursor:pointer;transition:0.3s;}
.profile-card button:hover{background:#1d4ed8;}
.message{margin-bottom:10px;font-weight:600;color:green;}
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <h2><span>📚 Student Portal</span></h2>
    <a href="dashboard.php" data-tooltip="Dashboard"><i class="fas fa-home"></i><span>Dashboard</span></a>
    <a href="students.php" data-tooltip="Manage Students"><i class="fas fa-user-graduate"></i><span>Manage Students</span></a>
    <a class="active" data-tooltip="Profile"><i class="fas fa-user"></i><span>Profile</span></a>
    <a class="toggle-submenu" data-tooltip="Settings"><i class="fas fa-cog"></i><span>Settings</span></a>
    <div class="submenu">
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

    <h2>My Profile</h2>

    <div class="profile-card">
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <img id="profilePreview" src="<?php echo $user['profile_image'] ? htmlspecialchars($user['profile_image']) : 'assets/img/default-avatar.png'; ?>" alt="Profile Image">
        <h3><?php echo htmlspecialchars($user['name']); ?></h3>
        <p><?php echo htmlspecialchars($user['email']); ?></p>

        <form method="post" enctype="multipart/form-data">
            <input type="file" name="profile_image" accept="image/*" required onchange="previewImage(event)">
            <button type="submit">Upload New Photo</button>
        </form>
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

// Live image preview
function previewImage(event){
    const reader = new FileReader();
    reader.onload = function(){
        const output = document.getElementById('profilePreview');
        output.src = reader.result;
    }
    reader.readAsDataURL(event.target.files[0]);
}
</script>

</body>
</html>
