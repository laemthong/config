<?php
header("Content-Type: application/json; charset=UTF-8");

include 'config.php';

// ใช้เส้นทางที่แน่นอนในการอ้างอิงไฟล์
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'การเชื่อมต่อล้มเหลว: ' . $conn->connect_error]);
        exit;
    }

    // อ่านข้อมูลจากคำขอ POST
    $data = json_decode(file_get_contents("php://input"), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('รูปแบบ JSON ไม่ถูกต้อง');
    }

    if (isset($data['email'])) {
        $email = $data['email'];

        // เตรียมและดำเนินการคำสั่ง SQL
        $stmt = $conn->prepare("SELECT * FROM user_information WHERE user_email = ?");
        if ($stmt === false) {
            throw new Exception('การเตรียมคำสั่ง SQL ล้มเหลว: ' . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // ดึงข้อมูลผู้ใช้
            $user = $result->fetch_assoc();
            $resetToken = bin2hex(random_bytes(16)); // สร้างโทเค็นรีเซ็ตรหัสผ่าน

            // บันทึกโทเค็นรีเซ็ตและเวลาที่หมดอายุลงในฐานข้อมูล
            $stmt = $conn->prepare("UPDATE user_information SET reset_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE user_email = ?");
            if ($stmt === false) {
                throw new Exception('การเตรียมคำสั่ง SQL ล้มเหลว: ' . $conn->error);
            }
            $stmt->bind_param("ss", $resetToken, $email);
            $stmt->execute();

            // ตั้งค่า PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // หรือ smtp.your-email-provider.com ตามผู้ให้บริการของคุณ
            $mail->SMTPAuth = true;
            $mail->Username = 'your-email@gmail.com'; // อีเมลของคุณ
            $mail->Password = 'your-email-password'; // รหัสผ่านของคุณ
            $mail->SMTPSecure = 'tls'; // หรือ 'ssl' ขึ้นอยู่กับการตั้งค่าของผู้ให้บริการ
            $mail->Port = 587; // หรือ 465 สำหรับการเชื่อมต่อแบบ SSL

            // ผู้ส่ง
            $mail->setFrom('your-email@gmail.com', 'Your App Name');
            // ผู้รับ
            $mail->addAddress($email); // อีเมลของผู้รับ

            // เนื้อหาของอีเมล
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = 'กรุณาคลิกลิ้งก์ต่อไปนี้เพื่อรีเซ็ตรหัสผ่านของคุณ: <a href="http://yourserver.com/reset_password.php?token=' . $resetToken . '">Reset Password</a>';

            try {
                $mail->send();
                echo json_encode(['status' => 'success', 'message' => 'Password reset email sent']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Email not found']);
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Caught exception: ' . $e->getMessage()]);
}

$conn->close();
?>
