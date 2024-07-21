<?php
include 'config.php';


$pro_id = isset($_POST['pro_id']) ? $_POST['pro_id'] : '';
$pro_name = isset($_POST['pro_name']) ? $_POST['pro_name'] : '';
$pro_username = isset($_POST['pro_username']) ? $_POST['pro_username'] : '';
$pro_brief = isset($_POST['pro_brief']) ? $_POST['pro_brief'] : '';

// สร้าง SQL query สำหรับอัพเดตข้อมูล
$sql = "UPDATE profile SET pro_name = ?, pro_username = ?, pro_brief = ? WHERE pro_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $pro_name, $pro_username, $pro_brief, $pro_id);

if ($stmt->execute()) {
    echo json_encode(array("message" => "Profile updated successfully"));
} else {
    echo json_encode(array("message" => "Failed to update profile"));
}

$stmt->close();
$conn->close();
?>