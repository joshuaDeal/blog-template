<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$name = false;
$bio = false;
$image = false;

// Load database configuration
$databaseFile = '/opt/blog/blog.db';

// Connect to the SQLite database
try {
	$db = new PDO("sqlite:$databaseFile");
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	die("Could not connect to the database: ".$e->getMessage());
}

// Get the author from the URL
if (isset($_GET['author'])) {
	$author = $_GET['author'];

	// Prepare and execute the SQL query
	$stmt = $db->prepare("SELECT name, bio, image FROM authors WHERE name = :author");
	$stmt->bindValue(':author', $author);
	$stmt->execute();

	// Fetch the data from the database
	$data = $stmt->fetch(PDO::FETCH_ASSOC);


	if ($data) {
		// Get the file path
		$name = $data['name'];

		// Get article id.
		$bio = $data['bio'];

		// Get author.
		$image = $data['image'];
	}

}

if (!$name || !$bio || !$image) {
	echo "<!DOCTYPE html>\n";
	echo "<html lang='en'>\n";
	echo "	<head>\n";
	echo "		<title>Blog | Submit Comment</title>\n";
	echo "		<meta charset='utf-8'>\n";
	echo "		<link rel='stylesheet' href='style.css'>\n";
	echo "	</head>\n";
	echo "	<body>\n";
	echo "		<p class='message'>An error has occured.</p>\n";
	echo "	</body>\n";
	echo "</html>\n";
	exit;

}

function fetchArticleByAuthor($db, $author) {
	$sql = "SELECT * FROM articles WHERE author = :author ORDER BY date DESC";
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':author', $author);
	$stmt->execute();
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$articles = fetchArticleByAuthor($db, $author);

$pageTitle = $name;
$pageDescription = "about author $name";

include '/opt/blog/header.php';
?>
		<article>
			<div id="author">
				<h1><?php echo $name?></h1>
				<img src='<?php echo $image;?>' alt="<?php echo $name;?>'s profile picture">
				<p><?php echo $bio;?></p>
			</div>
			<section class='catagory'>
				<h2>Articles by <?php echo $author; ?></h2>
				<?php if ($articles): ?>
					<ul>
						<?php foreach ($articles as $article): ?>
							<li>
								<h3>
									<a href="/article.php?article=<?php echo htmlspecialchars($article['name']); ?>">
										<?php echo htmlspecialchars($article['title']); ?>
									</a>
								</h3>
								<p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($article['date'])); ?></p>
								<p><?php echo htmlspecialchars($article['description']); ?></p>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else: ?>
					<p>No articles found.</p>
				<?php endif; ?>
			</section>
		</article>
<?php include '/opt/blog/footer.html'; ?>
