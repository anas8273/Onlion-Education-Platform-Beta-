<?php
include '../components/connect.php';

// Check if user is logged in
$tutor_id = isset($_COOKIE['tutor_id']) ? $_COOKIE['tutor_id'] : '';
if (!$tutor_id) {
    header('location:login.php');
    exit();
}

// Handle form submission
if(isset($_POST['submit'])){
    $message = uploadContent($_POST, $_FILES, $conn, $tutor_id);
}

function uploadContent($formData, $fileData, $conn, $tutor_id) {
    $message = [];

    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    // Handle thumbnail image upload
    if(isset($fileData['image']) && $fileData['image']['error'] === UPLOAD_ERR_OK) {
        $thumb_tmp_name = $fileData['image']['tmp_name'];
        $thumb_name = $fileData['image']['name'];
        $thumb_ext = strtolower(pathinfo($thumb_name, PATHINFO_EXTENSION));
        $rename_thumb = unique_id().'.'.$thumb_ext;
        $thumb_folder = '../uploaded_files/'.$rename_thumb;

        $image_info = getimagesize($thumb_tmp_name);
        $allowed_image_extensions = ['jpeg', 'jpg', 'bmp', 'gif', 'png', 'svg'];
        $max_image_size = 2 * 1024 * 1024; // 2 MB

        if(!$image_info || $fileData['image']['size'] > $max_image_size || !in_array($thumb_ext, $allowed_image_extensions)) {
            $message[] = 'Please select a valid image (JPEG, JPG, BMP, GIF, PNG, SVG) with size not exceeding 2 MB.';
        } else {
            try {
                $id = unique_id();
                $add_playlist = $conn->prepare("INSERT INTO `playlist`(id, tutor_id, title, description, thumb, status) VALUES(?,?,?,?,?,?)");
                $add_playlist->execute([$id, $tutor_id, $title, $description, $rename_thumb, $status]);

                move_uploaded_file($thumb_tmp_name, $thumb_folder);
                $message[] = 'New Playlist Created!';
            } catch (PDOException $e) {
                $message[] = 'Error uploading content: ' . $e->getMessage();
            }
        }
    } else {
        $message[] = 'Please select a thumbnail image.';
    }

    return $message;
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Add Playlist</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">

</head>
<body>

<?php include '../components/admin_header.php'; ?>
   
<section class="playlist-form">

   <h1 class="heading">create playlist</h1>

   <form action="" method="post" enctype="multipart/form-data">
      <p>playlist status <span>*</span></p>
      <select name="status" class="box" required>
         <option value="" selected disabled>-- select status</option>
         <option value="active">active</option>
         <option value="deactive">deactive</option>
      </select>
      <p>playlist title <span>*</span></p>
      <input type="text" name="title" maxlength="100" required placeholder="enter playlist title" class="box">
      <p>playlist description <span>*</span></p>
      <textarea name="description" class="box" required placeholder="write description" maxlength="1000" cols="30" rows="10"></textarea>
      <p>playlist thumbnail <span>*</span></p>
      <input type="file" name="image" accept="image/*" required class="box">
      <label for="thumb" style="color: red; font-size: 13px;">Allowed formats: jpg, png, svg, jpeg, gif </label>
      <input type="submit" value="create playlist" name="submit" class="btn">
   </form>

</section>


<?php include '../components/footer.php'; ?>

<script src="../js/admin_script.js"></script>

</body>
</html>