<?php

$db = new PDO('sqlite:database/database.sqlite');

echo "=== CARTES RFID DISPONIBLES ===" . PHP_EOL;
$result = $db->query('SELECT card_number, student_id FROM rfid_cards LIMIT 3');
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "Card: " . $row['card_number'] . " (Student ID: " . $row['student_id'] . ")" . PHP_EOL;
}

echo "\n=== ÉTUDIANTS ===" . PHP_EOL;
$result = $db->query('SELECT id, first_name, last_name FROM users WHERE role = "student" LIMIT 3');
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo $row['first_name'] . " " . $row['last_name'] . PHP_EOL;
}

echo "\n=== COURS ===" . PHP_EOL;
$result = $db->query('SELECT code, name FROM courses LIMIT 3');
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo $row['code'] . ": " . $row['name'] . PHP_EOL;
}

echo "\n=== SESSIONS DISPONIBLES ===" . PHP_EOL;
$result = $db->query('SELECT DISTINCT day_of_week, start_time, end_time FROM timetable_entries LIMIT 5');
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo $row['day_of_week'] . " " . $row['start_time'] . "-" . $row['end_time'] . PHP_EOL;
}
