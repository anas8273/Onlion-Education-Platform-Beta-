<?php
require_once '../components/connect.php';

// Function to generate a random reset code
function generateResetCode() {
    return mt_rand(100000, 999999);
}

// Function to send reset password email
function sendResetEmail($email, $resetCode) {
    require 'mail.php';
    $mail->addAddress($email);
    $mail->Subject = 'Reset password';
    $mail->Body = 'Use the following code to reset your password: '.$resetCode;
    return $mail->send();
}

if(isset($_POST['send_code'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Check if the email exists in the database
    $select_tutor = $conn->prepare("SELECT id FROM tutors WHERE EMAIL = :email");
    $select_tutor->execute([':email' => $email]);
    $tutor = $select_tutor->fetch(PDO::FETCH_ASSOC);

    if($tutor) {
        // Generate a reset code and update it in the database
        $resetCode = generateResetCode();
        $updateCode = $conn->prepare("UPDATE tutors SET RESET_CODE = :reset_code WHERE EMAIL = :email");
        $updateCode->execute([':reset_code' => $resetCode, ':email' => $email]);

        // Send reset email
        if(sendResetEmail($email, $resetCode)) {
            $success_message = 'Reset code sent successfully. Check your email.';
        } else {
            $error_message = 'Failed to send reset email. Please try again later.';
        }
    } else {
        $error_message = 'Invalid email address.';
    }
} elseif(isset($_POST['submit'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $resetCode = $_POST['reset_code'];

    // Check if the reset code matches the one stored in the database
    $select_tutor = $conn->prepare("SELECT id FROM tutors WHERE EMAIL = :email AND RESET_CODE = :reset_code");
    $select_tutor->execute([':email' => $email, ':reset_code' => $resetCode]);
    $tutor = $select_tutor->fetch(PDO::FETCH_ASSOC);

    if($tutor) {
        // Redirect the user to the password reset page
        header('Location: reset_password.php?email='.$email);
        exit;
    } else {
        $error_message = 'Invalid reset code.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Reset Password</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">

</head>
<body style="padding-left: 0;">
<?php if(isset($error_message)): ?>
    <div class="message form">
        <span><?php echo $error_message; ?></span>
        <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
    </div>
<?php elseif(isset($success_message)): ?>
    <div class="message form">
        <span><?php echo $success_message; ?></span>
        <i class="fas fa-check" onclick="this.parentElement.remove();"></i>
    </div>
<?php endif; ?>

<section class="form-container" style="background-image: url('http://localhost/project/images/back.gif');">
   
   <form action="" method="post" enctype="multipart/form-data" class="login">
      <!-- register section starts  -->
   <a href="login.php" class="logo" style="display: block; text-align: center;">
      <img src="http://localhost/project/images/upg2.svg" alt="Upgrade Logo" width="150" height="110" loading="lazy" style="display: inline-block;">
   </a>  
      <h3>Reset Your Password</h3>
      <p>Your Email <span>*</span></p>
      <input type="email" name="email" placeholder="Enter your email" maxlength="70" required class="box">
      <input type="submit" name="send_code" value="Send Code" class="btn" style="width: 100%; padding: 1.4rem; background-color: #8e44ad; color: #fff; border: none; cursor: pointer; font-size: 14px; transition: background-color 0.3s ease; display: inline-block;">
      <p>Reset Code</p>
      <input type="text" name="reset_code" placeholder="Enter reset code" maxlength="6"  class="box">
      <input type="submit" name="submit" value="Verify and Reset Password" class="btn" style="width: 100%; padding: 1.4rem; background-color: #8e44ad; color: #fff; border: none; cursor: pointer; font-size: 14px; transition: background-color 0.3s ease; display: inline-block;">
      <a href="../home.php" class="w-100 mb-2 btn btn-lg rounded-3 btn-danger" onclick="return confirmCancel();">Back</a>
      <br><h1>Anas Alyousifi!</h1>
   </form>

</section>

<!-- registe section ends -->

<!--icon sociol midia-->

<script>
    let darkMode = localStorage.getItem('dark-mode');
        let body = document.body;

        const enableDarkMode = () => {
            body.classList.add('dark');
            localStorage.setItem('dark-mode', 'enabled');
        }

        const disableDarkMode = () => {
            body.classList.remove('dark');
            localStorage.setItem('dark-mode', 'disabled');
        }

        if (darkMode === 'enabled') {
            enableDarkMode();
        } else {
            disableDarkMode();
        }
</script>
</body>
</html>
