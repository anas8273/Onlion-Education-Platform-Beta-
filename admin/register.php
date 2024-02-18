<?php
include '../components/connect.php';

$formSubmitted = false;
$errors = [];

if (isset($_POST['submit'])) {
    $formSubmitted = true;

    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $profession = filter_input(INPUT_POST, 'profession', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $pass = filter_input(INPUT_POST, 'pass', FILTER_SANITIZE_STRING);
    $cpass = filter_input(INPUT_POST, 'cpass', FILTER_SANITIZE_STRING);

    $image = $_FILES['image'];
    $image_name = $image['name'];
    $image_tmp_name = $image['tmp_name'];
    $image_size = $image['size'];

    // Validate email format
    if (!$email) {
        $errors['email'] = 'Invalid email format.';
    } else {
        // Check if email already exists
        $select_tutor = $conn->prepare("SELECT * FROM `tutors` WHERE email = ?");
        $select_tutor->execute([$email]);
        if ($select_tutor->rowCount() > 0) {
            $errors['email'] = 'Email already taken!';
        }
    }

    // Check if passwords match and validate image file
    if ($pass !== $cpass) {
        $errors['password'] = 'Password confirmation does not match!';
    } else {
        // Validate image file
        $image_info = getimagesize($image_tmp_name);
        if (!$image_info || $image_size > (2 * 1024 * 1024) || !in_array(pathinfo($image_name, PATHINFO_EXTENSION), ['jpeg', 'jpg', 'bmp', 'gif', 'png', 'svg'])) {
            $errors['image'] = 'Please select a valid image (JPEG, JPG, BMP, GIF, PNG, SVG) with size not exceeding 2 MB.';
        }
    }

    if (empty($errors)) {
        $id = unique_id();
        $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
        $rename = unique_id() . '.' . pathinfo($image_name, PATHINFO_EXTENSION);
        $image_folder = '../uploaded_files/' . $rename;

        // Insert user data into database
        try {
            $insert_tutor = $conn->prepare("INSERT INTO `tutors`(id, name, profession, email, password, image) VALUES(?,?,?,?,?,?)");
            $insert_tutor->execute([$id, $name, $profession, $email, $hashedPass, $rename]);

            // Move uploaded image to destination folder
            move_uploaded_file($image_tmp_name, $image_folder);

            // Show success message
            $successMessage = 'Registration successful. You can now login.';

            // Clear form data
            $name = '';
            $profession = '';
            $email = '';
        } catch (Exception $e) {
            $errorMessage = 'An error occurred while registering the tutor. Please try again later.';
            // Log the error for further investigation
            error_log($e->getMessage());
        }
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>

    <!-- Font Awesome CDN link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

    <!-- Custom CSS file link -->
    <link rel="stylesheet" href="../css/admin_style.css">

    <style>
        body {
            padding-left: 0;
        }
    </style>
</head>

<body>
    <?php if ($formSubmitted && empty($errors) && isset($successMessage)) : ?>
        <div class="message form success">
            <span><?= $successMessage ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
        </div>
    <?php endif; ?>

    <!-- Display error messages -->
    <?php if ($formSubmitted && !empty($errors)) : ?>
        <?php foreach ($errors as $error) : ?>
            <div class="message form error">
                <span><?= $error ?></span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Register section starts -->
    <section class="form-container">
        <form id="registerForm" class="register" action="" method="post" enctype="multipart/form-data">
            <h3>Register New</h3>
            <div class="flex">
                <div class="col">
                    <p>Your Name <span>*</span></p>
                    <input type="text" name="name" placeholder="Enter your name" maxlength="50" required class="box" pattern="[A-Za-z\u0600-\u06FF\s]+(\s[A-Za-z\u0600-\u06FF]+)?" title="Name must contain Arabic and English letters only" value="<?= $name ?? '' ?>">
                    <p>Your Profession <span>*</span></p>
                    <select name="profession" class="box" required>
                        <option value="" disabled selected>-- Select your profession</option>
                        <option value="developer" <?= ($profession ?? '') === 'developer' ? 'selected' : '' ?>>Developer</option>
                        <option value="designer" <?= ($profession ?? '') === 'designer' ? 'selected' : '' ?>>Designer</option>
                        <option value="musician" <?= ($profession ?? '') === 'musician' ? 'selected' : '' ?>>Musician</option>
                        <option value="biologist" <?= ($profession ?? '') === 'biologist' ? 'selected' : '' ?>>Biologist</option>
                        <option value="teacher" <?= ($profession ?? '') === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                        <option value="engineer" <?= ($profession ?? '') === 'engineer' ? 'selected' : '' ?>>Engineer</option>
                        <option value="lawyer" <?= ($profession ?? '') === 'lawyer' ? 'selected' : '' ?>>Lawyer</option>
                        <option value="accountant" <?= ($profession ?? '') === 'accountant' ? 'selected' : '' ?>>Accountant</option>
                        <option value="doctor" <?= ($profession ?? '') === 'doctor' ? 'selected' : '' ?>>Doctor</option>
                        <option value="journalist" <?= ($profession ?? '') === 'journalist' ? 'selected' : '' ?>>Journalist</option>
                        <option value="photographer" <?= ($profession ?? '') === 'photographer' ? 'selected' : '' ?>>Photographer</option>
                    </select>
                    <p>Your Email <span>*</span></p>
                    <input type="email" name="email" placeholder="Enter your email" maxlength="50" required class="box" value="<?= $email ?? '' ?>">
                </div>
                <div class="col">
                    <p>Your Password <span>*</span></p>
                    <input type="password" name="pass" placeholder="Enter your password" maxlength="20" required class="box" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,12}" title="Password must contain at least one uppercase letter, one lowercase letter, one number, and be 8-12 characters long">
                    <p>Confirm Password <span>*</span></p>
                    <input type="password" name="cpass" placeholder="Confirm your password" maxlength="20" required class="box">
                    <p>Select Pic <span>*</span></p>
                    <input type="file" name="image" accept=".jpeg, .jpg, .bmp, .gif, .png, .svg" required class="box">
                </div>
            </div>
            <p class="link">Already have an account? <a href="login.php">Login now</a></p>
            <input type="submit" name="submit" value="Register now" class="btn">
            <a href="../home.php" class="w-100 mb-2 btn btn-lg rounded-3 btn-danger" onclick="return confirmCancel();">Back</a>
        </form>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.register');
            const cancelButton = document.querySelector('.btn-danger');

            // Add event listener for form submission
            form.addEventListener('submit', function(event) {
                const emailInput = document.querySelector('input[name="email"]');
                const email = emailInput.value;
                const emailRegex = /^[a-zA-Z0-9._%+-]+@(gmail|yahoo|outlook)\.(com|org|net|info)$/i;
                if (!emailRegex.test(email.trim())) {
                    alert('Invalid email format. Please enter a valid email address (e.g., example@gmail.com, example@yahoo.com)');
                    emailInput.focus();
                    event.preventDefault(); // Prevent form submission
                }
            });

            // Warn user before leaving page if there are unsaved changes
            window.addEventListener('beforeunload', function(event) {
                const form = document.querySelector('.register');
                if (form.classList.contains('changed')) {
                    const confirmationMessage = 'Are you sure you want to leave the page? Your changes will be lost.';
                    event.returnValue = confirmationMessage;
                    return

 confirmationMessage;
                }
            });

            // Confirm before canceling
            function confirmCancel() {
                const form = document.querySelector('.register');
                if (form.classList.contains('changed') && !confirm('Are you sure you want to cancel registration?')) {
                    return false;
                } else if (form.classList.contains('changed')) {
                    if (!confirm('Your data will be lost!')) {
                        return false;
                    }
                }
                return true;
            }

            // Confirm before canceling when clicking cancel button
            cancelButton.addEventListener('click', function(event) {
                if (!confirmCancel()) {
                    event.preventDefault();
                }
            });

            // Confirm before leaving page when refreshing or closing
            window.addEventListener('beforeunload', function(event) {
                if (!confirmCancel()) {
                    event.preventDefault();
                }
            });

        });

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
