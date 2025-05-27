<?php
// these path should be changed according to the path of the file in pc
$pythonPath = "C:\\Users\\acer\\AppData\\Local\\Programs\\Python\\Python311\\python.exe";
$scriptPath = "read_rfid_and_save.py";

// Build command string
$command = "start /B \"\" \"$pythonPath\" \"$scriptPath\"";

// Log output for debugging 
file_put_contents("rfid_log.txt", "Command: $command\n", FILE_APPEND);

// Execute command
exec($command);

echo json_encode(["status" => "started"]);
