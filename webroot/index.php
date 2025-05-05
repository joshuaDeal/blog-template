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
?>

<!DOCTYPE html>
<html lang='en'>
	<head>
		<title>Blog | Index</title>
		<meta charset='utf-8'>
		<meta name='descirption' content='Html page template for blog'>
		<link rel="stylesheet" href="style.css">
	</head>
	<body>
		<header>
			<div class="logo">Blog</div>
			<nav>
				<ul>
					<li><a href="index.php">Home</a></li>
					<li><a href="about.html">About</a></li>
					<li><a href="rss.xml">RSS Feed</a></li>
				</ul>
			</nav>
			<div class="search-container">
				<form action="search.php" method="get">
					<input type="text" placeholder="Search..." aria-label="Search" name="terms">
					<button type="submit">🔍</button>
				</form>
			</div>
		</header>
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
		<footer>
			<p>¯\_(ツ)_/¯</p>
		</footer>
	</body>
</html>
