<?php
// Initialize the session
session_start();

// Check if the user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if username is empty
    if(empty(trim($_POST["username"]))) {
        $username_err = "Wprowadź nazwę użytkownika.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))) {
        $password_err = "Wprowadź hasło.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT id, username, password FROM users WHERE username = :username";
        
        if($stmt = $pdo->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()) {
                // Check if username exists, if yes then verify password
                if($stmt->rowCount() == 1) {
                    if($row = $stmt->fetch()) {
                        $id = $row["id"];
                        $username = $row["username"];
                        $hashed_password = $row["password"];
                        if(password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            
                            // Redirect user to dashboard
                            header("location: dashboard.php");
                            exit;
                        } else {
                            // Password is not valid
                            $login_err = "Nieprawidłowa nazwa użytkownika lub hasło.";
                        }
                    }
                } else {
                    // Username doesn't exist
                    $login_err = "Nieprawidłowa nazwa użytkownika lub hasło.";
                }
            } else {
                $login_err = "Coś poszło nie tak. Spróbuj ponownie później.";
            }

            // Close statement
            unset($stmt);
        }
    }
    
    // Close connection
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - TOP SERWIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="text-center mb-8">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 200" class="w-48 h-auto mx-auto">
                <path d="M50,20 L750,20 C765,20 780,35 780,50 L780,150 C780,165 765,180 750,180 L50,180 C35,180 20,165 20,150 L20,50 C20,35 35,20 50,20 Z" fill="#FF1493"/>
                <path d="M150,40 L600,40 L600,160 L150,160 Z" fill="#87CEEB" transform="rotate(-15 375 100)"/>
                <path d="M250,40 L700,40 L700,160 L250,160 Z" fill="#FFD700" transform="rotate(-15 475 100)"/>
                <text x="200" y="120" font-family="Arial Black" font-size="72" fill="black" style="font-weight: 900;">TOP SERWIS</text>
            </svg>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Panel logowania
            </h2>
        </div>

        <div class="bg-white py-8 px-4 shadow rounded-lg sm:px-10">
            <?php if(!empty($login_err)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <?php echo $login_err; ?>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">
                        Nazwa użytkownika
                    </label>
                    <div class="mt-1">
                        <input id="username" name="username" type="text" required 
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm <?php echo (!empty($username_err)) ? 'border-red-500' : ''; ?>"
                               value="<?php echo $username; ?>">
                        <?php if(!empty($username_err)): ?>
                            <p class="mt-2 text-sm text-red-600"><?php echo $username_err; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Hasło
                    </label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" required
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>">
                        <?php if(!empty($password_err)): ?>
                            <p class="mt-2 text-sm text-red-600"><?php echo $password_err; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                        Zaloguj się
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>