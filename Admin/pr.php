<?php
require_once 'config.php'; // Include the database configuration file

$room = $_GET['room'] ?? '';
$month = $_GET['month'] ?? '';
$year = $_GET['year'] ?? '';

$bill_details = null;
$records = [];

if ($room == "ทั้งหมด") {
    // Fetch the latest bill for each room in the selected month and year
    $sql = "SELECT b.*, u.Room_number, u.First_name, u.Last_name, 
                   CASE 
                       WHEN u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301') THEN u.water_was 
                       WHEN u.Room_number = 'S1' THEN b.water_cost 
                       ELSE b.water_cost 
                   END as water_cost_display
            FROM bill b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.month = ? AND b.year = ? AND u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301', 'S1', 'S2')
            AND (u.Room_number, b.id) IN (
                SELECT u.Room_number, MAX(b.id)
                FROM bill b
                LEFT JOIN users u ON b.user_id = u.id
                WHERE b.month = ? AND b.year = ?
                GROUP BY u.Room_number
            )
            ORDER BY u.Room_number";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $month, $year, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
} else {
    // Fetch the latest bill for the selected room
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
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $room, $month, $year);
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
    <title>พิมพ์ใบเสร็จ</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 80%; margin: auto; padding: 20px; background: #f4f4f4; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .total { margin-top: 20px; font-weight: bold; }
        .print-button { margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <?php if ($room == "ทั้งหมด" && !empty($records)): ?>
        <h2>ผลลัพธ์:</h2>
        <table>
            <tr>
                <th>หมายเลขห้อง</th>
                <th>เดือน</th>
                <th>ปี</th>
                <th>ค่าไฟฟ้า</th>
                <th>ค่าน้ำ</th>
                <th>ค่าห้อง</th>
                <th>ค่าใช้จ่ายทั้งหมด</th>
            </tr>
            <?php foreach ($records as $record): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['Room_number']); ?></td>
                    <td><?php echo htmlspecialchars($record['month']); ?></td>
                    <td><?php echo htmlspecialchars($record['year']); ?></td>
                    <td><?php echo htmlspecialchars($record['electric_cost']); ?> บาท</td>
                    <td><?php echo htmlspecialchars($record['water_cost_display']); ?> บาท</td>
                    <td><?php echo htmlspecialchars($record['room_cost']); ?> บาท</td>
                    <td><?php echo htmlspecialchars($record['total_cost']); ?> บาท</td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="print-button">
            <button onclick="window.print()">พิมพ์ใบเสร็จทั้งหมด</button>
        </div>
    <?php elseif (!empty($bill_details)): ?>
        <h2>ใบเสร็จรับเงิน</h2>
        <table>
            <tr>
                <th>หมายเลขห้อง</th>
                <td><?php echo htmlspecialchars($bill_details['Room_number']); ?></td>
            </tr>
            <tr>
                <th>ชื่อ</th>
                <td><?php echo htmlspecialchars($bill_details['First_name']); ?> <?php echo htmlspecialchars($bill_details['Last_name']); ?></td>
            </tr>
            <tr>
                <th>เดือน</th>
                <td><?php echo htmlspecialchars($bill_details['month']); ?></td>
            </tr>
            <tr>
                <th>ปี</th>
                <td><?php echo htmlspecialchars($bill_details['year']); ?></td>
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
        <div class="print-button">
            <button onclick="window.print()">พิมพ์ใบเสร็จ</button>
        </div>
    <?php endif; ?>
</div>
</body>
</html>