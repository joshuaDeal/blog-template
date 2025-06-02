<?php
// Load database configuration
$databaseFile = '/opt/blog/blog.db';

// Connect to the SQLite database
try {
	$db = new PDO("sqlite:$databaseFile");
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	die("Could not connect to the database: ".$e->getMessage());
}

// Get the article ID from the URL
if (isset($_GET['article'])) {
	$articleName = $_GET['article'];

	// Prepare and execute the SQL query
	$stmt = $db->prepare("SELECT article_id, title, author, date, description, file FROM articles WHERE name = :articleName");
	$stmt->bindValue(':articleName', $articleName);
	$stmt->execute();

	// Fetch the article from the database
	$article = $stmt->fetch(PDO::FETCH_ASSOC);


	if ($article) {
		// Get the file path
		$filePath = $article['file'];

		// Get article id.
		$articleId = $article['article_id'];

		// Fetch comments related to article
		$comments = [];
		if ($articleId) {
			$commentStmt = $db->prepare("SELECT name, comment, created_at FROM comments WHERE context_id = :contextId AND visible = 'true' ORDER BY created_at ASC");
			$commentStmt->bindValue(':contextId', "$articleId");
			$commentStmt->execute();
			$comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
		}

		// Get author.
		$pageAuthor = $article['author'];

		// Get date.
		$date = date('F j, Y', strtotime($article['date']));

		//Set the page title and description.
		$pageTitle = htmlspecialchars($article['title']);
		$pageDescription = htmlspecialchars($article['description']);

		// Check if the file exists
		if (file_exists($filePath)) {
			// Output the contents of the HTML file
			header('Content-Type: text/html');
			ob_start();
			readfile($filePath);
			$content = ob_get_clean();
		} else {
			$content = "<p>Error: The article file does not exist.</p>\n";
		}
	} else {
		$pageTitle = "Article Not Found";
		$pageDescription = "The requested article could not be found.";
		$content = "<p>Error: Article not found.</p>\n";
	}
} else {
	$pageTitle = "No Article Specified";
	$pageDescription = "Please specify an article to view.";
	$content = "<p>Error: No article name specified.</p>\n";
}

include '/opt/blog/header.php';
?>
		<article>
			<h1><?php echo $pageTitle?></h1>
			<p class="author">by <a href="author.php?author=<?php echo $pageAuthor?>"><?php echo $pageAuthor?></a></p>
			<p class="date"><?php echo $date;?></p>
			<?php echo $content; ?>
		</article>
		<div id=comments>
			<h3>Comments</h3>
			<p>(Comments must be approved by a moderator.)</p>
				<form id="create-comment" method="post" action="submit-comment.php">
					<label for="name">Name:</label><br>
					<input type="text" id="name" name="name" required><br>
				
					<label for="email">Email (Optional, in case you want to subscirbe to this comments thread.):</label><br>
					<input type="email" id="email" name="email"><br>
					
					<label for="comment">Comment:</label><br>
					<textarea id="comment" name="comment" rows="4" cols="50" maxlength="500" required></textarea><br>

					<input type="hidden" name="context_id" value="<?php echo $articleId?>">

					<div class="captcha">
						<?php
							# Start a session.
							session_start();

							# Generate captcha text.
							$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
							$length = rand(4, 6);
							$ans = '';
							for ($i = 0; $i < $length; $i++) {
								$ans .= $chars[rand(0, strlen($chars) - 1)];
							}

							# Store the captcha answer in the session.
							$_SESSION['captcha_ans'] = $ans;

							# Generate random file name.
							$file = '';
							for ($i = 0; $i < 16; $i++) {
								$file .= mt_rand(0, 9);
							}
							$file .= '.png';

							# Generate captcha image.
							$cmd = "/opt/blog/generate-captcha.py -t $ans -f /var/www/testing/img/captcha/$file";
							exec($cmd);

							echo "<img src='/img/captcha/$file' alt='captcha image'>\n";
							echo "<br>";

							echo "<label for='captcha'>Please type the text above:</label><br>";
							echo "<input type='text' id='captcha' name='user_ans'><br>";
						?>
					</div>

					
					<input type="submit" value="Submit">
				</form>
				<div class="comments-list">
					<?php if (!empty($comments)): ?>
						<ul>
							<?php foreach ($comments as $comment): ?>
								<li>
									<strong><?php echo htmlspecialchars($comment['name']); ?></strong> <em>(<?php echo htmlspecialchars($comment['created_at']); ?>)</em>
									<p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else: ?>
						<p>No comments yet. Be the first to leave a comment!</p>
					<?php endif; ?>
				</div>
		</div>
<?php include '/opt/blog/footer.html'?>
