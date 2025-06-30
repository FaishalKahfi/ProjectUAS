<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

function send_email($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // sesuaikan
        $mail->SMTPAuth = true;
        $mail->Username = 'kampusmerdeka@gmail.com';
        $mail->Password = 'yvco skgn rdjq ozes';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('kampusmerdeka@gmail.com', 'Kampus Merdeka');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
    } catch (Exception $e) {
        // error handling (log / tampilkan)
    }
}
?>
