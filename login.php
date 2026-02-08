<?php
session_start();
require_once "db_connect.php";

if (isset($_POST['nmlogin'])) {
    $username = trim($_POST['nmusername']);
    $password = ($_POST['nmpassword']);

    // tell the sql this is how it's gonna look, but don't actually send anything yet
    $stmt = mysqli_prepare($db_connect, "SELECT id, password, role_id FROM users WHERE username = ?");
    // replace the ? with $username the "s" means string, "i" -> integer, "d" ->double, "b" -> blob
    mysqli_stmt_bind_param($stmt, "s", $username);
    // execute the statement
    mysqli_stmt_execute($stmt);

    //get the result of the execution above
    $result = mysqli_stmt_get_result($stmt);
    // fetches one row, returns it as an associative array
    $user = mysqli_fetch_assoc($result);

    //check password
    if ($user && password_verify($password, $user['password'])) {
        //create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = (int)$user['role_id'];
        header("Location: index.php");
        exit;
    } else {
        echo "Invalid username or password";
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
    <form method="POST" action="login.php">
        <table>
            <a href="index.php"><button type="button">Back to index.php</button></a>
            <tr>
                <td><label for="username">Username</label></td>
                <td><input type="text" name="nmusername" id="username" required></td>
            </tr>
            <tr>
                <td><label for="password">Password</label></td>
                <td><input type="password" name="nmpassword" id="password" required></td>
            </tr>
            <tr>
                <td><button type="submit" name="nmlogin">Login</button></td>
            </tr>
        </table>
    </form>
</body>

</html>