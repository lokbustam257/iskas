<?php
require_once "db_connect.php";

if (isset($_POST['submit'])) {

    $username = strtolower(trim($_POST['nmusername']));
    $email = $_POST["nmemail"];
    $email = ($email === '') ? NULL : $email;
    $password = password_hash($_POST['nmpassword'], PASSWORD_DEFAULT);
    $msg_reg_success = "Registration successful!";
    $msg_reg_error = "Registration failed!";

    // Default profile picture
    $profile_pic = 'default.png';

    if (!empty($_FILES['nmprofile_picture']['name'])) {

        $originalName = $_FILES['nmprofile_picture']['name'];
        $tmp_pic = $_FILES['nmprofile_picture']['tmp_name'];

        $profile_pic = uniqid('pfp_') . '_' . basename($originalName);
        $target = 'img_pfp/' . $profile_pic;

        move_uploaded_file($tmp_pic, $target);
    }

    $stmt = mysqli_prepare(
        $db_connect,
        "INSERT INTO users (username, email, password, profile_picture)
         VALUES (?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $username,
        $email,
        $password,
        $profile_pic
    );

    if (mysqli_stmt_execute($stmt)) {
        echo $msg_reg_success;
    } else {
        echo $msg_reg_error;
    }
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
    <form method="post" enctype="multipart/form-data">
        <table>
            <a href="index.php"><button type="button">Back to index.php</button></a>
            <tr>
                <td><label for="username">Username</label></td>
                <td><input required type="text" name="nmusername" id="username"></td>
            </tr>
            <tr>
                <td><label for="password">Password</label></td>
                <td><input required type="password" name="nmpassword" id="password"></td>
            </tr>
                <td><label for="email">Email</label></td>
                <td><input type="text" name="nmemail" id="email"></td>
            </tr>
            <tr>
                <td><label for="profile_picture">Profile Picture (Optional)</label></td>
                <td><input type="file" name="nmprofile_picture" id="profile_picture"></td>
                <td><i style="color: gray;">If left empty, the default one would be used instead</i></td>
            </tr>
            <tr>
                <td><button type="submit" name="submit">Registrate here nigga</button></td>
            </tr>
        </table>
    </form>
</body>

</html>