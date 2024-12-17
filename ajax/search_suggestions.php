<?php
include "../db/config.php";

$query = isset($_GET['query']) ? $_GET['query'] : '';
$query = "%$query%";

$sql = $conn->prepare("SELECT title FROM tasks WHERE title LIKE ?");
$sql->bind_param("s", $query);
$sql->execute();
$result = $sql->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = $row;
}

header('Content-Type: application/json');
echo json_encode($suggestions);
$sql->close();
$conn->close();
?>