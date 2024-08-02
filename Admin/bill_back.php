<?php
require_once 'config.php'; // Include the database configuration file

$selected_room = $selected_start_date = $selected_end_date = "";
$rooms = [];
$dates = [];

// Fetch distinct rooms from the database for dropdown
$roomQuery = "SELECT DISTINCT Room_number FROM users WHERE Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301', 'S1', 'S2') ORDER BY Room_number";
$dateQuery = "SELECT DISTINCT date_record FROM electric ORDER BY date_record";

if ($roomResult = $conn->query($roomQuery)) {
    while ($row = $roomResult->fetch_assoc()) {
        $rooms[] = $row['Room_number'];
    }
}
if ($dateResult = $conn->query($dateQuery)) {
    while ($row = $dateResult->fetch_assoc()) {
        $dates[] = $row['date_record'];
    }
}

// Handle the form submission
$bill_details = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['view_bill'])) {
    $selected_room = $_POST['selected_room'];
    $selected_start_date = $_POST['selected_start_date'];
    $selected_end_date = $_POST['selected_end_date'];

    // Prepare and execute the SQL query to fetch the latest bill for the selected room and date range
    $sql = "SELECT b.*, u.Room_number, u.First_name, u.Last_name, 
                   CASE 
                       WHEN u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301') THEN u.water_was 
                       WHEN u.Room_number = 'S1' THEN b.water_cost 
                       ELSE b.water_cost 
                   END as water_cost_display
            FROM bill b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE u.Room_number = ? AND b.year >= YEAR(?) AND b.month >= MONTH(?) AND b.year <= YEAR(?) AND b.month <= MONTH(?)
            ORDER BY b.id DESC LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $selected_room, $selected_start_date, $selected_start_date, $selected_end_date, $selected_end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $bill_details = $result->fetch_assoc();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการย้อนหลัง</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bb.css">
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
                <a href="index.php"><i class="fas fa-home"></i>หน้าหลัก</a>
                <a href="crud.php"><i class="fas fa-users"></i>จัดการข้อมูลผู้ใช้</a>
                <a href="total.php"><i class="fas fa-tint"></i>การคำนวณค่าน้ำ-ค่าไฟ</a>
                <a href="bill.php"><i class="fas fa-file-invoice"></i>พิมพ์เอกสาร</a>
                <a href="bill_back.php"><i class="fas fa-history"></i> รายการย้อนหลัง</a>
                <a href="summary.php"><i class="fas fa-chart-bar"></i> สรุปยอดรายเดือน/ปี</a>
            </div>
        </aside>

        <div class="content">
            <h1>รายการย้อนหลัง</h1>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="selected_room">หมายเลขห้อง</label>
                    <select id="selected_room" name="selected_room" required>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room; ?>" <?php echo $room == $selected_room ? 'selected' : ''; ?>><?php echo $room; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="selected_start_date">วันที่เริ่มต้น</label>
                    <select id="selected_start_date" name="selected_start_date" required>
                        <?php foreach ($dates as $date): ?>
                            <option value="<?php echo $date; ?>" <?php echo $date == $selected_start_date ? 'selected' : ''; ?>><?php echo $date; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="selected_end_date">วันที่สิ้นสุด</label>
                    <select id="selected_end_date" name="selected_end_date" required>
                        <?php foreach ($dates as $date): ?>
                            <option value="<?php echo $date; ?>" <?php echo $date == $selected_end_date ? 'selected' : ''; ?>><?php echo $date; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="view_bill" class="small-button">ดูใบเสร็จรับเงิน</button>
            </form>

            <?php if (!empty($bill_details)): ?>
                <div class="printable"> <!-- ส่วนที่ต้องการพิมพ์ -->
                    <h2>เจ้าสัว Apartment</h2>
                    <table>
                        <tr>
                            <th>รายการ</th>
                            <th>รายละเอียด</th>
                        </tr>
                        <tr>
                            <td>หมายเลขห้อง</td>
                            <td><?php echo htmlspecialchars($bill_details['Room_number']); ?></td>
                        </tr>
                        <tr>
                            <td>ชื่อ-นามสกุล</td>
                            <td><?php echo htmlspecialchars($bill_details['First_name'] . ' ' . $bill_details['Last_name']); ?></td>
                        </tr>
                        <tr>
                            <td>เดือน/ปี</td>
                            <td><?php echo htmlspecialchars($bill_details['month'] . '/' . $bill_details['year']); ?></td>
                        </tr>
                        <tr>
                            <td>ค่าไฟฟ้า</td>
                            <td><?php echo number_format($bill_details['electric_cost'], 2); ?> บาท</td>
                        </tr>
                        <tr>
                            <td>ค่าน้ำ</td>
                            <td><?php echo number_format($bill_details['water_cost_display'], 2); ?> บาท</td>
                        </tr>
                        <tr>
                            <td>ค่าห้อง</td>
                            <td><?php echo number_format($bill_details['room_cost'], 2); ?> บาท</td>
                        </tr>
                    </table>
                    <div class="total">
                        ยอดรวมทั้งสิ้น: <?php echo number_format($bill_details['total_cost'], 2); ?> บาท
                    </div>
                </div>
                <div class="print-button">
                    <button onclick="window.print()">พิมพ์ใบเสร็จ</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>