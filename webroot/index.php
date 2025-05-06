<?php
// Load database configuration
$databaseFile = '/opt/blog/blog.db';

// Connect to the SQLite database
try {
	$db = new PDO("sqlite:$databaseFile");
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	die("Could not connect to the database: " .$e->getMessage());
}

// Fetch articles
$sql = "SELECT * FROM articles ORDER BY date DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Index';
$pageDescription = 'Index page for blog';

include '/opt/blog/header.php';
?>

		<article>
			<section class='catagory'>
				<?php if ($articles): ?>
					<ul>
						<?php foreach ($articles as $article): ?>
							<li>
								<h3>
									<a href='/article.php?article=<?php echo htmlspecialchars($article['name']); ?>'>
										<?php echo htmlspecialchars($article['title']); ?>
									</a>
								</h3>
								<p><strong>Date:</strong> <?php echo date('F j, y', strtotime($article['date'])); ?></p>
								<p><?php echo htmlspecialchars($article['description']); ?></p>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else: ?>
					<p>No articles found.</p>
				<?php endif; ?>
			</section>
		</article>
<?php include '/opt/blog/footer.html';?>
