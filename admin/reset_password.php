<?php
include '../components/connect.php';

if(isset($_POST['submit'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $reset_code = $_POST['reset_code'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // التحقق من صحة الرمز الأمني المُدخل
    $select_tutor = $conn->prepare("SELECT id FROM tutors WHERE EMAIL = :email AND RESET_CODE = :reset_code");
    $select_tutor->execute([':email' => $email, ':reset_code' => $reset_code]);
    $tutor = $select_tutor->fetch(PDO::FETCH_ASSOC);

    if($tutor && $password === $confirm_password) {
        // تحديث كلمة المرور في قاعدة البيانات
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_password = $conn->prepare("UPDATE tutors SET PASSWORD = :password, RESET_CODE = NULL WHERE EMAIL = :email");
        $update_password->execute([':password' => $hashed_password, ':email' => $email]);

        // توجيه المستخدم إلى صفحة تسجيل الدخول بعد تغيير كلمة المرور بنجاح
        header('Location: login.php');
        exit;
    } else {
        $error_message = 'Invalid reset code or passwords do not match.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Create New Password</title>

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
<?php endif; ?>

<section class="form-container" style="background-image: url('http://localhost/project/images/back.gif');">
   
   <form action="" method="post" enctype="multipart/form-data" class="login">
      <!-- register section starts  -->
   <a href="login.php" class="logo" style="display: block; text-align: center;">
      <img src="http://localhost/project/images/upg2.svg" alt="Upgrade Logo" width="150" height="110" loading="lazy" style="display: inline-block;">
   </a>  
      <h3>Create New Password</h3>
      <input type="hidden" name="email" value="<?php echo $_GET['email']; ?>">
      <p>Reset Code <span>*</span></p>
      <input type="text" name="reset_code" placeholder="Enter reset code" required class="box">
      <p>New Password <span>*</span></p>
      <input type="password" name="password" placeholder="Enter new password" required class="box">
      <p>Confirm Password <span>*</span></p>
      <input type="password" name="confirm_password" placeholder="Confirm new password" required class="box">
      <input type="submit" name="submit" value="Reset Password" class="btn">
   </form>

</section>

<!-- registe section ends -->

<!--icon sociol midia-->

<script src="../js/admin_script.js"></script>

</body>
</html>
