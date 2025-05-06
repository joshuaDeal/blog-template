<?php
// Get search terms from url.
if (isset($_GET['terms'])) {
	$searchString = $_GET['terms'];
} else {
	$searchString = "";
}

function getResults($searchString) {
	define("TITLE_POINTS", 9);
	define("DESC_POINTS", 6);
	define("TAG_POINTS", 6);

	// SQLite DB path
	$dbPath = '/opt/blog/blog.db';

	// Create db connection
	$conn = new SQLite3($dbPath);

	// Check connection
	if (!$conn) {
		die("Connection failed: " . $conn->lastErrorMsg());
	}

	// Tokenize the search string
	$cleanString = preg_replace("/[^a-zA-Z0-9\s]+/", "", $searchString);
	$cleanArray = explode(' ', $cleanString);
	$searchTokens = [];
	foreach ($cleanArray as $token) {
		$token = " " . $token . " ";
		$searchTokens[] = strtolower($token);
	}

	// Calculate the TF for each token
	$searchTermFrequency = array_count_values($searchTokens);

	// Calculate the IDF for each token
	$idf = array();
	foreach ($searchTokens as $token) {
		$sql = "SELECT COUNT(DISTINCT article_id) AS document_count FROM articles WHERE LOWER(title) LIKE LOWER('%$token%') OR LOWER(description) LIKE LOWER('%$token%') OR LOWER(tags) LIKE LOWER('%$token%')";
		$result = $conn->query($sql);
		$row = $result->fetchArray(SQLITE3_ASSOC);
		$idf[$token] = $row['document_count'];
	}

	// Calculate the TF-IDF for each document
	$tfidf = array();
	$sql = "SELECT title, description, date, author, name, tags FROM articles ORDER BY date DESC";
	$result = $conn->query($sql);

	while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
		$tfidfScore = 0;
		foreach ($searchTokens as $token) {
			$tf = substr_count(strtolower($row['title'] . ' ' . $row['description']), strtolower($token));
			$idfValue = $idf[strtolower($token)]; // Use IDF value calculated previously

			// Calculate TF-IDF score.
			if ($idfValue != 0) {
				$tfidfScore += $tf * log((count($tfidf) + 1) / $idfValue);
			} else {
				$tfidfScore = 1;
			}

			// Give extra points if the token appears in the title
			if (stripos(strtolower($row['title']), strtolower(trim($token))) !== false) {
				$tfidfScore += TITLE_POINTS; // Extra points
			}

			// Give extra points if the token appears in the description.
			if (stripos(strtolower($row['description']), strtolower(trim($token))) !== false) {
				$tfidfScore += DESC_POINTS; // Extra points
			}

			// Give extra points if the token appears in the tags.
			if (stripos(strtolower($row['tags']), strtolower(trim($token))) !== false) {
				$tfidfScore += TAG_POINTS; // Extra points
			}
		}
		$tfidf[$row['name']] = $tfidfScore;
	}

	// Sort documents by TF-IDF
	arsort($tfidf);

	// Output the results
	foreach ($tfidf as $name => $score) {
		if ($score > 1) {
			$sql = "SELECT title, description, author, date FROM articles WHERE name = '$name'";
			$result = $conn->query($sql);
			$row = $result->fetchArray(SQLITE3_ASSOC);

			echo "<li>";
			echo "<div id='result'>\n";
			echo "	<h3><a href='article.php?article=$name'>{$row['title']}</a></h3>\n";
			echo "	<p><strong>Author:</strong> {$row['author']}";
			$date = date('F j, Y', strtotime($row['date']));
			echo "	<p><strong>Date:</strong> $date</p>";
			echo "  <p>{$row['description']}</p>\n";
			echo "</div>\n";
			echo "</li>";
		}
	}

	// Close connection
	$conn->close();
}

$pageTitle = 'Search';
$pageDescription = 'Search for articles.';

include '/opt/blog/header.php';
?>
		<section class="search-results">
			<?php
				//$searchString = "The";
				echo "<h2>Results for \"$searchString\"</h2>";
				echo "<ul>";
				getResults($searchString);
				echo "</ul>";
			?>
		</section>
<?php include '/opt/blog/footer.html'; ?>
