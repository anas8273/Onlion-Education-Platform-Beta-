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
    
    // Retrieve and sanitize form data
    $status = filter_var($formData['status'], FILTER_SANITIZE_STRING);
    $title = filter_var($formData['title'], FILTER_SANITIZE_STRING);
    $description = filter_var($formData['description'], FILTER_SANITIZE_STRING);
    $playlist = filter_var($formData['playlist'], FILTER_SANITIZE_STRING);

    // Handle thumbnail image upload
    $thumb_tmp_name = $fileData['thumb']['tmp_name'];
    $thumb_ext = strtolower(pathinfo($fileData['thumb']['name'], PATHINFO_EXTENSION));
    $rename_thumb = unique_id().'.'.$thumb_ext;
    $thumb_folder = '../uploaded_files/'.$rename_thumb;

    // Handle video upload
    $video_tmp_name = $fileData['video']['tmp_name'];
    $video_ext = strtolower(pathinfo($fileData['video']['name'], PATHINFO_EXTENSION));
    $rename_video = unique_id().'.'.$video_ext;
    $video_folder = '../uploaded_files/'.$rename_video;

    // Validation for thumbnail and video
    $image_info = getimagesize($thumb_tmp_name);
    $allowed_image_extensions = ['jpeg', 'jpg', 'bmp', 'gif', 'png', 'svg'];
    $video_info = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $video_tmp_name);
    $allowed_video_mime_types = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/flv', 'video/mkv'];
    $max_image_size = 2 * 1024 * 1024; // 2 MB
    $max_video_size = 50 * 1024 * 1024; // 50 MB

    if(!$image_info || $fileData['thumb']['size'] > $max_image_size || !in_array($thumb_ext, $allowed_image_extensions)) {
        $message[] = 'Please select a valid image (JPEG, JPG, BMP, GIF, PNG, SVG) with size not exceeding 2 MB.';
    } elseif (!$video_info || $fileData['video']['size'] > $max_video_size || !in_array($video_info, $allowed_video_mime_types)) {
        $message[] = 'Please select a valid video (MP4, AVI, MOV, WMV, FLV, MKV) with size not exceeding 50 MB.';
    } else {
        // Insert content data into the database
        try {
            $id = unique_id();
            $add_content = $conn->prepare("INSERT INTO `content`(id, tutor_id, playlist_id, title, description, video, thumb, status) VALUES(?,?,?,?,?,?,?,?)");
            $add_content->execute([$id, $tutor_id, $playlist, $title, $description, $rename_video, $rename_thumb, $status]);

            // Move uploaded files to destination folder
            move_uploaded_file($thumb_tmp_name, $thumb_folder);
            move_uploaded_file($video_tmp_name, $video_folder);

            $message[] = 'New content uploaded successfully!';
        } catch (PDOException $e) {
            $message[] = 'Error uploading content: ' . $e->getMessage();
        }
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
   <title>Dashboard</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">
   
</head>
<body>

<?php include '../components/admin_header.php'; ?>
   
<section class="video-form">

   <h1 class="heading">upload content</h1>

   

   <form action="" method="post" enctype="multipart/form-data">
      <p>video status <span>*</span></p>
      <select name="status" class="box" required>
         <option value="" selected disabled>-- select status</option>
         <option value="active">active</option>
         <option value="deactive">deactive</option>
      </select>
      <p>video title <span>*</span></p>
      <input type="text" name="title" maxlength="100" required placeholder="enter video title" class="box">
      <p>video description <span>*</span></p>
      <textarea name="description" class="box" required placeholder="write description" maxlength="1000" cols="30" rows="10"></textarea>
      <p>video playlist <span>*</span></p>
      <select name="playlist" class="box" required>
         <option value="" disabled selected>--select playlist</option>
         <?php
         $select_playlists = $conn->prepare("SELECT * FROM `playlist` WHERE tutor_id = ?");
         $select_playlists->execute([$tutor_id]);
         if($select_playlists->rowCount() > 0){
            while($fetch_playlist = $select_playlists->fetch(PDO::FETCH_ASSOC)){
         ?>
         <option value="<?= $fetch_playlist['id']; ?>"><?= $fetch_playlist['title']; ?></option>
         <?php
            }
         ?>
         <?php
         }else{
            echo '<option value="" disabled>no playlist created yet!</option>';
         }
         ?>
      </select>
      <p>Select Thumbnail <span>*</span></p>
      <input type="file" name="thumb" accept="image/*" required class="box">
      <label for="thumb" style="color: red; font-size: 13px;">Allowed formats: jpg, png, svg, jpeg, gif </label>

        <p>Upload Video <span>*</span></p>
        <input type="file" name="video" accept="video/mp4, video/avi, video/wmv, video/mov" required class="box">
        <label for="upload" style="color: red; font-size: 13px;">Allowed formats: mp4, avi, wmv, mov</label>
        <input type="submit" name="submit" value="Upload Video" class="btn">
    </form>
</section>

<?php include '../components/footer.php'; ?>

<script src="../js/admin_script.js"></script>

</body>
</html>



