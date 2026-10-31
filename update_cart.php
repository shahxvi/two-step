<?php
session_start();

if (isset($_GET ["action"]) && isset($_GET["shoe_id"])) {
    $action = $_GET["action"];
    $shoe_id = $_GET["shoe_id"];

if (isset($_SESSION["cart"])) {
    if ($action == "add") {
        $_SESSION["cart"] []= $shoe_id;
}
elseif ($action == "minus") {
    $key = array_search($shoe_id, $_SESSION["cart"]);
    if ($key !== false) {
        unset($_SESSION["cart"][$key]);
        $_SESSION["cart"] = array_values($_SESSION["cart"]); // Susun balik indeks array
    }
}

elseif($action == "remove") {
    $_SESSION["cart"] = array_diff($_SESSION["cart"], [$shoe_id]);
    $_SESSION["cart"] = array_values($_SESSION["cart"]);
        }
    }   
}

header("Location: cart.php");
exit();
?>