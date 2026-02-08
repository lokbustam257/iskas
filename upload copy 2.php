<?php
    if (isset($_POST['upload'])) {
        $filename = $_FILES["uploadimage"]["name"];
        $tempname = $_FILES["uploadimage"]["temp_name"];
        $img_dir = "./img_dir" . $filename;
        $msg_success = "Upload successful! Your image is now in index.php";
        $msg_error = "Upload failed! It's either you uploaded nothing or something else";

        $sql = "INSERT INTO (filename) VALUES ('$filename')";
        $db_connect = mysqli_connect("localhost", "root", "", "iska_webgallerydb");
        mysqli_query($db_connect, $sql);
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <table>
        <tr>
            <td>
                <div class="form-group">
                    <label for="uploadimage">Upload an image. Max is 2MB.</label><br>
                    <input type="file" accept="image/*" name="uploadimage"><br>
                    <button type="submit" id="submit" name="upload">Upload image</button>
                    <!-- <input class="form-control" accept="image/*" type="file" name="uploadfile" value="" /> -->
                </div>
            </td>
        </tr>
    </table>
</body>

</html>