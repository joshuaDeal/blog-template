<?php

$maxLength = 500;

$ans = trim(filter_input(INPUT_POST, 'ans', FILTER_SANITIZE_STRING));
$user_ans = trim(filter_input(INPUT_POST, 'user_ans', FILTER_SANITIZE_STRING));

if ($user_ans == $ans) {
	// Database configuration
	$dbFile = '/opt/blog/blog.db';
	
	try {
		// Create (connect to) SQLite database in file
		$pdo = new PDO("sqlite:$dbFile");
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
		// Get and sanitize input
		$name = trim(filter_input(INPUT_POST, 'name'));
		$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
		$comment = trim(filter_input(INPUT_POST, 'comment'));
		$context_id = trim(filter_input(INPUT_POST, 'context_id', FILTER_SANITIZE_STRING));

		// Check that comment length is not to large.
		if (strlen($comment) > $maxLength) {
			die("Error: Your comment exceeds the maximum allowed length.");
		}
	
		// Validate required fields
		if (empty($name) || empty($comment) || empty($context_id)) {
			die("Name, comment, and content id fields are required.");
		}
	
		// Generate a unique comment_id
		do {
			$comment_id = 'c-' . random_int(1000000000, 9999999999);
	
			// Check if the generated comment_id already exists
			$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE comment_id = :comment_id");
			$stmt->execute([':comment_id' => $comment_id]);
			$exists = $stmt->fetchColumn();
		} while ($exists > 0); // Regenerate if it exists
	
		// Get the user's IP address
		$ip_address = $_SERVER['REMOTE_ADDR'];
	
		// Prepare and execute the insert query
		$stmt = $pdo->prepare("INSERT INTO comments (comment_id, context_id, name, email, comment, ip_address, visible) VALUES (:comment_id, :context_id, :name, :email, :comment, :ip_address, 'false')");
	
		$stmt->execute([
			':comment_id' => $comment_id,
			':context_id' => $context_id,
			':name' => $name,
			':email' => $email,
			':comment' => $comment,
			':ip_address' => $ip_address,
		]);
	
		// Redirect or output success page
		$message = "Comment submitted sucessfully.";
	
	} catch (PDOException $e) {
		echo "Database error: " . $e->getMessage();
	}
} else {
	$message = "Captcha incorrect.";
}

// Redirect or output success page
echo "<!DOCTYPE html>\n";
echo "<html lang='en'>\n";
echo "	<head>\n";
echo "		<title>Blog | Submit Comment</title>\n";
echo "		<meta charset='utf-8'>\n";
echo "		<link rel='stylesheet' href='style.css'>\n";
echo "	</head>\n";
echo "	<body>\n";
echo "		<p class='message'>" . htmlspecialchars($message) . "</p>\n";
echo "	</body>\n";
echo "</html>\n";
?>
