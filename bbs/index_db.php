<?php

$pdo = new PDO(
    "mysql:host=localhost;dbname=SAMPLE01;charset=utf8mb4",
    "sampleuser",
    "password"
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "SELECT * FROM bbs";

$stmt = $pdo->query($sql);

foreach ($stmt as $row) {
    echo $row["id"] . " ";
    echo $row["name"];
    echo "<br>";
}

?>