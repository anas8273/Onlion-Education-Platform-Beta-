<?php

include '../components/connect.php';

if(isset($_POST['submit'])){

   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $pass = sha1($_POST['pass']);
   $pass = filter_var($pass, FILTER_SANITIZE_STRING);

   $select_tutor = $conn->prepare("SELECT * FROM `tutors` WHERE email = ? AND password = ? LIMIT 1");
   $select_tutor->execute([$email, $pass]);
   $row = $select_tutor->fetch(PDO::FETCH_ASSOC);
   
   if($select_tutor->rowCount() > 0){
     setcookie('tutor_id', $row['id'], time() + 60*60*24*30, '/');
     header('location:dashboard.php');
   }else{
      $message[] = 'incorrect email or password!';
   }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Login</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">

</head>
<body style="padding-left: 0;">
<?php
if(isset($message) && is_array($message)){
   foreach($message as $msg){
       echo '
       <div class="message form">
           <span>'.$msg.'</span>
           <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
       </div>
       ';
   }
}


?>



<section class="form-container" style="background-image: url('http://localhost/project/images/back.gif');">
   
   <form action="" method="post" enctype="multipart/form-data" class="login">
      <!-- register section starts  -->
   <a href="login.php" class="logo" style="display: block; text-align: center;">
      <img src="http://localhost/project/images/upg2.svg" alt="Upgrade Logo" width="150" height="110" loading="lazy" style="display: inline-block;">
   </a>  
      <h3>welcome back!</h3>
      <p>your email <span>*</span></p>
      <input type="email" name="email" placeholder="enter your email" maxlength="70" required class="box">
      <p>your password <span>*</span></p>
      <input type="password" name="pass" placeholder="enter your password" maxlength="20" required class="box">
      <p class="css-exwg7f"> <a href="verification_password.php">Forgot Password?</a></p>
      <input type="submit" name="submit" value="login now" class="btn">
      <a href="../home.php" class="w-100 mb-2 btn btn-lg rounded-3 btn-danger" onclick="return confirmCancel();">Back</a>
      <p class="link">don't have an account? <a href="register.php">register new</a></p>
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
<?php
ob_end_flush();
?>