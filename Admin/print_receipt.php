<?php
require_once 'config.php'; // Include the database configuration file

// Initialize variables with default values
$room = $_GET['room'] ?? '';
$month = $_GET['month'] ?? '';
$year = $_GET['year'] ?? '';

$bill_details = null;
$records = [];

// Validate the inputs to avoid SQL errors
$room = $room === '' ? null : $room;
$month = is_numeric($month) ? (int)$month : null;
$year = is_numeric($year) ? (int)$year : null;

if ($room === "ทั้งหมด") {
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

function monthInThai($month) {
    $months = [
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
    return $months[$month] ?? '';
}

function yearInBuddhistEraText() {
    return 'พ.ศ.';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>พิมพ์ใบเสร็จสำหรับห้อง</title>
    <link rel="stylesheet" href="pl.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">
    <?php if ($room === "ทั้งหมด" && !empty($records)): ?>
        <div id="printArea">
            <?php foreach ($records as $record): ?>
                <div class="receipt">
                    <h2>ใบเสร็จรับเงินสำหรับห้อง: <?php echo htmlspecialchars($record['Room_number']); ?></h2>
                    <p><strong>ชื่อ:</strong> <?php echo htmlspecialchars($record['First_name']); ?> <?php echo htmlspecialchars($record['Last_name']); ?></p>
                    <p><strong>เดือน/ปี:</strong> <?php echo monthInThai((int)$record['month']); ?> <?php echo yearInBuddhistEraText(); ?></p>
                    <table>
                        <thead>
                            <tr>
                                <th>รายละเอียด</th>
                                <th>ค่าใช้จ่าย</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>ค่าไฟฟ้า</td>
                                <td><?php echo htmlspecialchars($record['electric_cost']); ?> บาท</td>
                            </tr>
                            <tr>
                                <td>ค่าน้ำ</td>
                                <td><?php echo htmlspecialchars($record['water_cost_display']); ?> บาท</td>
                            </tr>
                            <tr>
                                <td>ค่าห้อง</td>
                                <td><?php echo htmlspecialchars($record['room_cost']); ?> บาท</td>
                            </tr>
                            <tr>
                                <td>ค่าใช้จ่ายทั้งหมด</td>
                                <td><?php echo htmlspecialchars($record['total_cost']); ?> บาท</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- ใช้ page-break หลังแต่ละใบเสร็จ -->
                <div class="page-break"></div>
            <?php endforeach; ?>
        </div>
        <div class="print-button">
            <button onclick="printReceipt()">พิมพ์ใบเสร็จทั้งหมด</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($bill_details)): ?>
        <div class="receipt">
            <h2>ใบเสร็จรับเงิน</h2>
            <div class="receipt-info">
                <p><strong>หมายเลขห้อง:</strong> <?php echo htmlspecialchars($bill_details['Room_number']); ?></p>
                <p><strong>ชื่อ:</strong> <?php echo htmlspecialchars($bill_details['First_name']); ?> <?php echo htmlspecialchars($bill_details['Last_name']); ?></p>
                <p><strong>เดือน/ปี:</strong> <?php echo monthInThai((int)$bill_details['month']); ?> <?php echo yearInBuddhistEraText(); ?></p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>รายละเอียด</th>
                        <th>ค่าใช้จ่าย</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>ค่าไฟฟ้า</td>
                        <td><?php echo htmlspecialchars($bill_details['electric_cost']); ?> บาท</td>
                    </tr>
                    <tr>
                        <td>ค่าน้ำ</td>
                        <td><?php echo htmlspecialchars($bill_details['water_cost_display']); ?> บาท</td>
                    </tr>
                    <tr>
                        <td>ค่าห้อง</td>
                        <td><?php echo htmlspecialchars($bill_details['room_cost']); ?> บาท</td>
                    </tr>
                    <tr>
                        <td>ค่าใช้จ่ายทั้งหมด</td>
                        <td><?php echo htmlspecialchars($bill_details['total_cost']); ?> บาท</td>
                    </tr>
                </tbody>
            </table>
            <div class="total">
                <strong>ยอดรวม: <?php echo htmlspecialchars($bill_details['total_cost']); ?> บาท</strong>
            </div>
            <div class="print-button">
                <button onclick="window.print()">พิมพ์ใบเสร็จ</button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function printReceipt() {
    var printWindow = window.open('', '_blank');
    var printContent = document.getElementById('printArea').innerHTML;

    printWindow.document.open();
    printWindow.document.write('<html><head><title>พิมพ์ใบเสร็จทั้งหมด</title>');
    printWindow.document.write('<link rel="stylesheet" type="text/css" href="pl.css">');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}
</script>
</body>
</html>
