<?php

function calculate_hours($seconds) {
    return floor($seconds / 3600);
}

date_default_timezone_set("Asia/Tashkent");

// Database connection details
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "work_off_tracker";

// Connect to the database using PDO
$pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, 1234);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["arrived_at"]) && !empty($_POST["leaved_at"])) {

        $arrivedAt = new DateTime($_POST["arrived_at"]);
        $leavedAt = new DateTime($_POST["leaved_at"]);

        $workOffTimeSum = 0;
        $entitledTimeSum = 0;

        $arrivedAtFormatted = $arrivedAt->format("Y-m-d H:i:s");
        $leavedAtFormatted = $leavedAt->format("Y-m-d H:i:s");

        $interval = $arrivedAt->diff($leavedAt);
        $workingDurationSeconds = ($interval->h * 3600) + ($interval->i * 60) + $interval->s;

        $constWorkTime = 32400; // 9 hours in seconds

        if ($workingDurationSeconds > $constWorkTime) {
            $excessTime = $workingDurationSeconds - $constWorkTime;
            $requiredWorkOffTime = calculate_hours($excessTime);
            $workOffTimeSum += $requiredWorkOffTime;
        } else {
            $shortfallTime = $constWorkTime - $workingDurationSeconds;
            $entitledTime = calculate_hours($shortfallTime);
            $entitledTimeSum += $entitledTime;
        }

        $newWorkOffTimeSum = max(0, $workOffTimeSum - $entitledTimeSum);
        $newEntitledTimeSum = max(0, $entitledTimeSum - $workOffTimeSum);

        // Retrieve existing sums from the database
        $query = $pdo->query("SELECT * FROM daily")->fetchAll();

        foreach ($query as $row) {
            $newEntitledTimeSum += $row['entitled_time_sum'];
            $newWorkOffTimeSum += $row['req_work_off_time_sum'];
        }

        $workingDurationHours = calculate_hours($workingDurationSeconds);

        // Insert new record into the database
        $sql = "INSERT INTO daily (arrived_at, leaved_at, working_duration, req_work_off_time, entitled, req_work_off_time_sum, entitled_time_sum)
                VALUES (:arrived_at, :leaved_at, :working_duration, :req_work_off_time, :entitled, :req_work_off_time_sum, :entitled_time_sum)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':arrived_at', $arrivedAtFormatted);
        $stmt->bindParam(':leaved_at', $leavedAtFormatted);
        $stmt->bindParam(':working_duration', $workingDurationHours);
        $stmt->bindParam(':req_work_off_time', $requiredWorkOffTime);
        $stmt->bindParam(':entitled', $entitledTime);
        $stmt->bindParam(':req_work_off_time_sum', $newWorkOffTimeSum);
        $stmt->bindParam(':entitled_time_sum', $newEntitledTimeSum);
        $stmt->execute();
    } else {
        echo "Please fill in all the fields!";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Off Tracker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        input[type="datetime-local"], button {
            padding: 10px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
        }
        button {
            background-color: #5cb85c;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #4cae4c;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        li {
            padding: 10px;
            background-color: #fafafa;
            border-bottom: 1px solid #ddd;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Work Off Tracker</h1>
    <form action="index.php" method="post">
        Arrived At: <input type="datetime-local" name="arrived_at"><br>
        Leaved At: <input type="datetime-local" name="leaved_at"><br>
        <button type="submit">Submit</button>
    </form>
    <h2>Records</h2>
    <ul>
        <?php
        // Retrieve and display records from the database
        $query = $pdo->query("SELECT * FROM daily")->fetchAll();
        foreach ($query as $row) {
            echo "<li> | {$row['id']}: {$row['arrived_at']} | {$row['leaved_at']} | {$row['working_duration']} Hours worked | {$row['req_work_off_time']} Required work off time | {$row['entitled']} Entitled time | {$row['req_work_off_time_sum']} Required work off time sum | {$row['entitled_time_sum']} Entitled time sum | </li><br>";
        }
        ?>
    </ul>
</div>
</body>
</html>

