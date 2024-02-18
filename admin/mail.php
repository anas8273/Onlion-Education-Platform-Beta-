<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require __DIR__ . '/../mailer/autoload.php';

//Create an instance; passing `true` enables exceptions
$mail = new PHPMailer();
    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      
    $mail->isSMTP();                                           
    $mail->Host       = 'smtp.gmail.com';                    
    $mail->SMTPAuth   = true;                                  
    $mail->Username   = 'example@gmail.com';                    
    $mail->Password   = '';                              
    $mail->SMTPSecure = 'ssl';            
    $mail->Port       = 465;                                    

//Content
$mail->isHTML(true); 
$mail->CharSet="UTF-8";