<!DOCTYPE html>
<html lang='en'>
	<head>
		<title>Blog | <?php echo $pageTitle; ?></title>
		<meta charset='utf-8'>
		<meta name='descirption' content='<?php echo $pageDescription; ?>'>
		<link rel="stylesheet" href="style.css">
	</head>
	<body>
		<header>
			<div class="logo">Blog</div>
			<nav>
				<ul>
					<li><a href="index.php">Home</a></li>
					<li><a href="about.php">About</a></li>
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
		<main>
