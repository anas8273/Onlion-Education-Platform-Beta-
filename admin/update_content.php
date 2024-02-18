<?php

include '../components/connect.php';

// التحقق من تواجد معرّف المدرّس في الكوكيز، وإعادة التوجيه إلى صفحة تسجيل الدخول إذا لم يكن موجودًا
$tutor_id = isset($_COOKIE['tutor_id']) ? $_COOKIE['tutor_id'] : '';
if (!$tutor_id) {
   header('location:login.php');
   exit();
}

// التحقق من تواجد معرّف الفيديو في الطلب، وإعادة التوجيه إلى لوحة التحكم إذا لم يكن موجودًا
$get_id = isset($_GET['get_id']) ? $_GET['get_id'] : '';
if (!$get_id) {
   header('location:dashboard.php');
   exit();
}

if(isset($_POST['update'])){
   try {
      // استخراج البيانات من الاستمارة وتنقيتها
      $video_id = filter_input(INPUT_POST, 'video_id', FILTER_SANITIZE_STRING);
      $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
      $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
      $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
      $playlist = filter_input(INPUT_POST, 'playlist', FILTER_SANITIZE_STRING);

      // تحديث بيانات الفيديو في قاعدة البيانات
      $update_content = $conn->prepare("UPDATE `content` SET title = ?, description = ?, status = ?, playlist_id = ? WHERE id = ?");
      $update_content->execute([$title, $description, $status, $playlist, $video_id]);

      // التحقق من تنسيق الصورة المصغرة وحجمها
      if (!empty($_FILES['thumb']['name'])) {
         $thumb_tmp_name = $_FILES['thumb']['tmp_name'];
         $thumb_ext = pathinfo($_FILES['thumb']['name'], PATHINFO_EXTENSION);
         $thumb_folder = '../uploaded_files/'.unique_id().'.'.$thumb_ext;
         $thumb_size = $_FILES['thumb']['size'];
         $allowed_image_extensions = ['jpeg', 'jpg', 'bmp', 'gif', 'png', 'svg'];
         $max_image_size = 2 * 1024 * 1024; // 2 MB

         if (!in_array($thumb_ext, $allowed_image_extensions) || $thumb_size > $max_image_size) {
            throw new Exception('Please select a valid image (JPEG, JPG, BMP, GIF, PNG, SVG) with size not exceeding 2 MB.');
         }

         // تحديث اسم الصورة المصغرة في قاعدة البيانات
         $update_thumb = $conn->prepare("UPDATE `content` SET thumb = ? WHERE id = ?");
         $update_thumb->execute([$thumb_folder, $video_id]);
         
         // نقل الصورة المصغرة إلى المجلد المطلوب
         move_uploaded_file($thumb_tmp_name, $thumb_folder);
      }

      // التحقق من تنسيق الفيديو وحجمه
      if (!empty($_FILES['video']['name'])) {
         $video_tmp_name = $_FILES['video']['tmp_name'];
         $video_ext = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
         $video_folder = '../uploaded_files/'.unique_id().'.'.$video_ext;
         $video_size = $_FILES['video']['size'];
         $allowed_video_mime_types = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/flv', 'video/mkv'];
         $max_video_size = 50 * 1024 * 1024; // 50 MB

         if (!in_array(mime_content_type($video_tmp_name), $allowed_video_mime_types) || $video_size > $max_video_size) {
            throw new Exception('Please select a valid video (MP4, AVI, MOV, WMV, FLV, MKV) with size not exceeding 50 MB.');
         }

         // تحديث اسم الفيديو في قاعدة البيانات
         $update_video = $conn->prepare("UPDATE `content` SET video = ? WHERE id = ?");
         $update_video->execute([$video_folder, $video_id]);
         
         // نقل الفيديو إلى المجلد المطلوب
         move_uploaded_file($video_tmp_name, $video_folder);
      }

      $message[] = 'Content updated!';
   } catch (Exception $e) {
      $message[] = 'Error updating content: ' . $e->getMessage();
   }
}

if(isset($_POST['delete_video'])){
   try {
      $delete_id = filter_input(INPUT_POST, 'video_id', FILTER_SANITIZE_STRING);

      // استعادة اسم الصورة المصغرة للفيديو وحذفها
      $delete_video_thumb = $conn->prepare("SELECT thumb FROM `content` WHERE id = ? LIMIT 1");
      $delete_video_thumb->execute([$delete_id]);
      $fetch_thumb = $delete_video_thumb->fetch(PDO::FETCH_ASSOC);
      unlink('../uploaded_files/'.$fetch_thumb['thumb']);

      // استعادة اسم الفيديو وحذفه
      $delete_video = $conn->prepare("SELECT video FROM `content` WHERE id = ? LIMIT 1");
      $delete_video->execute([$delete_id]);
      $fetch_video = $delete_video->fetch(PDO::FETCH_ASSOC);
      unlink('../uploaded_files/'.$fetch_video['video']);

      // حذف الإعجابات والتعليقات المرتبطة بالفيديو
      $delete_likes = $conn->prepare("DELETE FROM `likes` WHERE content_id = ?");
      $delete_likes->execute([$delete_id]);
      $delete_comments = $conn->prepare("DELETE FROM `comments` WHERE content_id = ?");
      $delete_comments->execute([$delete_id]);

      // حذف الفيديو من قاعدة البيانات
      $delete_content = $conn->prepare("DELETE FROM `content` WHERE id = ?");
      $delete_content->execute([$delete_id]);

      header('location:contents.php');
   } catch (Exception $e) {
      $message[] = 'Error deleting content: ' . $e->getMessage();
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Update video</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">

</head>
<body>

<?php include '../components/admin_header.php'; ?>
   
<section class="video-form">

   <h1 class="heading">update content</h1>

   <?php
      $select_videos = $conn->prepare("SELECT * FROM `content` WHERE id = ? AND tutor_id = ?");
      $select_videos->execute([$get_id, $tutor_id]);
      if($select_videos->rowCount() > 0){
         while($fecth_videos = $select_videos->fetch(PDO::FETCH_ASSOC)){ 
            $video_id = $fecth_videos['id'];
   ?>
   <form action="" method="post" enctype="multipart/form-data">
      <input type="hidden" name="video_id" value="<?= $fecth_videos['id']; ?>">
      <input type="hidden" name="old_thumb" value="<?= $fecth_videos['thumb']; ?>">
      <input type="hidden" name="old_video" value="<?= $fecth_videos['video']; ?>">
      <p>update status <span>*</span></p>
      <select name="status" class="box" required>
         <option value="<?= $fecth_videos['status']; ?>" selected><?= $fecth_videos['status']; ?></option>
         <option value="active">active</option>
         <option value="deactive">deactive</option>
      </select>
      <p>update title <span>*</span></p>
      <input type="text" name="title" maxlength="100" required placeholder="enter video title" class="box" value="<?= $fecth_videos['title']; ?>">
      <p>update description <span>*</span></p>
      <textarea name="description" class="box" required placeholder="write description" maxlength="1000" cols="30" rows="10"><?= $fecth_videos['description']; ?></textarea>
      <p>update playlist</p>
      <select name="playlist" class="box">
         <option value="<?= $fecth_videos['playlist_id']; ?>" selected>--select playlist</option>
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
      <img src="../uploaded_files/<?= $fecth_videos['thumb']; ?>" alt="">
      <p>update thumbnail</p>
      <input type="file" name="thumb" accept="image/*" class="box">
      <label for="thumb" style="color: red; font-size: 13px;">Allowed formats: jpg, png, svg, jpeg, gif </label>
      <video src="../uploaded_files/<?= $fecth_videos['video']; ?>" controls></video>
      
      <p>update video</p>
      <input type="file" name="video" accept="video/*" class="box">
      <label for="upload" style="color: red; font-size: 13px;">Allowed formats: mp4, avi, wmv, mov</label>
      <input type="submit" value="update content" name="update" class="btn">
      <div class="flex-btn">
         <a href="view_content.php?get_id=<?= $video_id; ?>" class="option-btn">view content</a>
         <input type="submit" value="delete content" name="delete_video" class="delete-btn">
      </div>
   </form>
   <?php
         }
      }else{
         echo '<p class="empty">video not found! <a href="add_content.php" class="btn" style="margin-top: 1.5rem;">add videos</a></p>';
      }
   ?>

</section>















<?php include '../components/footer.php'; ?>

<script src="../js/admin_script.js"></script>

</body>
</html>