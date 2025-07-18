<?php
$db_host = "db";
$db_user = "db";
$db_pass = "db";
$db_name = "db";

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name); // Change DB credentials accordingly.

if ($mysqli->connect_errno) {
    die("Failed to connect: " . $mysqli->connect_error);
}

// Helper functions for comment classification
function classify_comment($comment)
{

    // Keywords before the feedback
    //     if (strpos($lc, 'candy') !== false || strpos($lc, 'smarties') !== false || strpos($lc, 'tootsie') !== false) {
    //         return 'candy';
    //     } elseif (preg_match('/\bcall me\b|\bdon\'t call\b|\bdo not call\b|\bplease call\b/i', $comment)) {
    //         return 'call';
    //     } elseif (strpos($lc, 'referred') !== false || strpos($lc, 'referred by') !== false) {
    //         return 'referred';
    //     } elseif (strpos($lc, 'no signature') !== false || strpos($lc, 'signature required') !== false) {
    //         return 'signature';
    //     } else {
    //         return 'misc';
    //     }

    /**
     * 
     *  1. Found taffy/Taffys as a sneaky ones. (Candy had 13 comments before and now it has 15 comments).
     *  2. New Category found is "Delivery" and it has 24 comments.
     * 
     */

    // Candy-related keywords (positive or negative)
    if (preg_match('/\b(candy|smarties|tootsie|taffy|taffys|chocolate)\b/i', $comment)) {
        return 'candy';
    }

    // Call / Do not call related phrases
    if (preg_match('/\b(call me|don\'t call|do not call|please call|no calls?)\b/i', $comment)) {
        return 'call';
    }

    // Referral / recommendation based comments
    if (preg_match('/\b(referred|referral|told me about|recommended by)\b/i', $comment)) {
        return 'referred';
    }

    // Signature requirements (presence or waiver)
    if (preg_match('/\b(signature (required|not required|waived|needed)|no signature|required signature|without signature)\b/i', $comment)) {
        return 'signature';
    }

    // Delivery-specific instructions
    if (preg_match('/\b(leave at|leave on|porch|back door|front door|entryway|mailroom|garage|reception|deliver to|delivery note|hold shipping)\b/i', $comment)) {
        return 'delivery';
    }

    // Everything else
    return 'misc';
}


// Task 1: Categorizing and displaying customer's comments.
$result = $mysqli->query("SELECT orderid, comments FROM sweetwater_test");

$grouped = [
    'candy' => [],
    'call' => [],
    'referred' => [],
    'signature' => [],
    'delivery' => [],
    'misc' => [],
];

while ($row = $result->fetch_assoc()) {
    $category = classify_comment($row['comments']);
    $grouped[$category][] = $row;
}

// Task 2: Extract ship date and update `shipdate_expected` column in db
$result = $mysqli->query("SELECT orderid, comments FROM sweetwater_test WHERE shipdate_expected = '0000-00-00 00:00:00'");
$updatedCount = 0;

while ($row = $result->fetch_assoc()) {
    if (preg_match('/Expected Ship Date:\s*(\d{2}\/\d{2}\/\d{2})/', $row['comments'], $matches)) {
        $date = DateTime::createFromFormat('m/d/y', $matches[1]);
        if ($date) {
            $formattedDate = $date->format('Y-m-d');
            $updateQuery = "UPDATE sweetwater_test SET shipdate_expected = '$formattedDate' WHERE orderid = {$row['orderid']}";
            $mysqli->query($updateQuery);
            $updatedCount++;
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Order Comments Report</title>
    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }

        h2 {
            margin-top: 40px;
        }

        ul {
            list-style-type: none;
            padding-left: 0;
        }

        li {
            margin-bottom: 10px;
        }

        .category {
            background-color: #f0f0f0;
            padding: 10px;
        }
    </style>
</head>

<body>
    <h1>Order Comments Categorized</h1>

    <?php foreach ($grouped as $category => $entries): ?>
        <div class="category">
            <h2>
                <?= ucfirst($category)=="Call" ? "Call/ Don't Call" : ucfirst($category) ?> Comments (<?= count($entries) ?>)
            </h2>
            <ul>
                <?php foreach ($entries as $entry): ?>
                    <li><strong>Order #<?= $entry['orderid'] ?>:</strong> <?= nl2br(htmlspecialchars($entry['comments'])) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>

    <hr>
    <p><strong>Task 2:</strong> Ship dates (shipdate_expected column) updated in <?= $updatedCount ?> rows.</p>
</body>

</html>
