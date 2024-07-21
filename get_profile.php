<?php
include 'config.php';

$pro_id = isset($_GET['pro_id']) ? $_GET['pro_id'] : '';

// สร้าง SQL query สำหรับดึงข้อมูลโปรไฟล์
$sql = "SELECT * FROM profile WHERE pro_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $pro_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // ส่งข้อมูลในรูปแบบ JSON
    echo json_encode($result->fetch_assoc());
} else {
    echo json_encode(array("message" => "No profile found"));
}

$stmt->close();
$conn->close();
?>
