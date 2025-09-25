<?php
require_once 'db_connect.php';

$result = $conn->query("DESCRIBE quiz_questions");
echo "Quiz Questions Table Structure:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>