<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

require_once '../config.php';

// Get user information from the session
$room_number = $_SESSION["room_number"];
$username = $_SESSION["username"];

// Thai months array
$thai_months = [
    1 => 'มกราคม',
    2 => 'กุมภาพันธ์',
    3 => 'มีนาคม',
    4 => 'เมษายน',
    5 => 'พฤษภาคม',
    6 => 'มิถุนายน',
    7 => 'กรกฎาคม',
    8 => 'สิงหาคม',
    9 => 'กันยายน',
    10 => 'ตุลาคม',
    11 => 'พฤศจิกายน',
    12 => 'ธันวาคม'
];

// Buddhist year conversion
function to_buddhist_year($year) {
    return $year + 543;
}

// Handle the form submission
$bill_details = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['view_bill'])) {
    $selected_month = $_POST['selected_month'];
    $selected_year = $_POST['selected_year'];

    // Prepare and execute the SQL query to fetch the latest bill for the selected room and date range
    $sql = "SELECT b.*, u.Room_number, u.First_name, u.Last_name, 
                   CASE 
                       WHEN u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301') THEN u.water_was 
                       WHEN u.Room_number = 'S1' THEN b.water_cost 
                       ELSE b.water_cost 
                   END as water_cost_display
            FROM bill b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE u.Room_number = ? AND b.month = ? AND b.year = ?
            ORDER BY b.id DESC LIMIT 1";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sss", $room_number, $selected_month, $selected_year);
        $stmt->execute();
        $result = $stmt->get_result();
        $bill_details = $result->fetch_assoc();
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}

$conn->close();

// Current year in Buddhist calendar
$current_year_buddhist = to_buddhist_year(date('Y'));
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จรับเงินย้อนหลัง</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="ss.css"> <!-- Ensure this path is correct -->
</head>
<body>
    <nav class="navbar">
        <span class="navbar-brand">เจ้าสัว Apartment</span>
        <div class="navbar-menu">
            <a href="logout.php">ออกจากระบบ</a>
        </div>
    </nav>
    
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-menu">
                <a href="index.php"><i class="fas fa-tint"></i> การคำนวณค่าน้ำ-ค่าไฟ</a>
                <a href="bill_back.php"><i class="fas fa-history"></i> รายการย้อนหลัง</a>
            </div>
        </aside>
        <main class="main-content">
            <h1>ค่าใช้จ่ายย้อนหลัง</h1>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <label for="selected_month">เดือน:</label>
                <select id="selected_month" name="selected_month" required>
                    <?php foreach ($thai_months as $m => $month_name): ?>
                        <option value="<?php echo $m; ?>" <?php echo isset($selected_month) && $selected_month == $m ? 'selected' : ''; ?>>
                            <?php echo $month_name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="selected_year">ปี:</label>
                <select id="selected_year" name="selected_year" required>
                    <?php for ($y = to_buddhist_year(date('Y')); $y >= to_buddhist_year(date('Y')) - 10; $y--): ?>
                        <option value="<?php echo $y - 543; ?>" <?php echo isset($selected_year) && $selected_year == ($y - 543) ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button type="submit" name="view_bill">ดูใบเสร็จรับเงิน</button>
            </form>

            <?php if (!empty($bill_details)): ?>
                <h2>ใบเสร็จรับเงิน</h2>
                <table>
                    <tr>
                        <th>หมายเลขห้อง</th>
                        <td><?php echo htmlspecialchars($bill_details['Room_number']); ?></td>
                    </tr>
                    <tr>
                        <th>ชื่อ</th>
                        <td><?php echo htmlspecialchars($bill_details['First_name']) . ' ' . htmlspecialchars($bill_details['Last_name']); ?></td>
                    </tr>
                    <tr>
                        <th>เดือน</th>
                        <td><?php echo htmlspecialchars($thai_months[intval($bill_details['month'])]); ?></td>
                    </tr>
                    <tr>
                        <th>ปี</th>
                        <td><?php echo htmlspecialchars(to_buddhist_year($bill_details['year'])); ?></td>
                    </tr>
                    <tr>
                        <th>ค่าไฟฟ้า</th>
                        <td><?php echo htmlspecialchars($bill_details['electric_cost']); ?> บาท</td>
                    </tr>
                    <tr>
                        <th>ค่าน้ำ</th>
                        <td><?php echo htmlspecialchars($bill_details['water_cost_display']); ?> บาท</td>
                    </tr>
                    <tr>
                        <th>ค่าห้อง</th>
                        <td><?php echo htmlspecialchars($bill_details['room_cost']); ?> บาท</td>
                    </tr>
                    <tr>
                        <th>ค่าใช้จ่ายทั้งหมด</th>
                        <td><?php echo htmlspecialchars($bill_details['total_cost']); ?> บาท</td>
                    </tr>
                </table>
                <div class="total">
                    <strong>ยอดรวม: <?php echo htmlspecialchars($bill_details['total_cost']); ?> บาท</strong>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
