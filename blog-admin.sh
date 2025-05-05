#!/bin/bash

# Administrative tools for blog.
# Add and remove articles and authors. Browse, approve and delete comments. See pending comments. Update rss feed.

DATABASE="/opt/blog/blog.db"

newArticleMode=false
removeArticleMode=false
removeCommentMode=false
listCommentsMode=false
approveCommentMode=false
listPendingCommentsMode=false
updateRssMode=false
addAuthorMode=false
removeAuthorMode=false

# Print help message
printHelp() {
	echo "Usage: $0 [OPTIONS]"
	echo
	echo "Administrative tools for blog."
	echo "Add and remove articles and authors. Browse, approve and delete comments. See pending comments. Update rss feed."
	echo
	echo "Options:"
	echo "  -h, --help				Display this help message."
	echo "  -n, --name <name>			Name of the article to add."
	echo "  -T, --title <title>			Title of the article to add."
	echo "  -a, --author <author>			Author of the article."
	echo "  -d, --description <description>	Description of the article."
	echo "  -f, --file <filepath>			File path of the article."
	echo "  -t, --tags <tags>			Comma-separated list of tags for the article."
	echo "  -i, --image <image-path>		Path to the image associated with the article."
	echo "  -ra, --remove-article <name>		Name of the article to remove."
	echo "  -lc, --list-comments <name>		List comments for the specific article."
	echo "  -rc, --remove-comment <id>		ID of the comment to remove."
	echo "  -ac, --approve <id>			ID of the comment to approve or disapprove."
	echo "  -lp, --list-pending			List all pending comments."
	echo "  -ur, --update-rss			Update the RSS feed."
	echo "  -aa, --add-author <name>		Name of author to add."
	echo "  -ab, --author-bio <bio> 		Bio of author to add."
	echo "  -ai, --author-image <filepath> 	Image for author to add."
	echo "  -rA, --remove-author <name>		Name of author to remove."
}

# Parse arguments.
evalArgs() {
	arguments=("$@")

	for ((i = 0; i < ${#arguments[@]}; i++)); do
		# Print help message.
		if [ "${arguments[i]}" == "-h" ] || [ "${arguments[i]}" == "--help" ]; then
			printHelp
			exit
		fi
	
		# Get article name.
		if [ "${arguments[i]}" == "-n" ] || [ "${arguments[i]}" == "--name" ]; then
			articleName=${arguments[i + 1]}
			newArticleMode=true
		fi

		# Get article title.
		if [ "${arguments[i]}" == "-T" ] || [ "${arguments[i]}" == "--title" ]; then
			articleTitle=${arguments[i + 1]}
			newArticleMode=true
		fi

		# Get article author.
		if [ "${arguments[i]}" == "-a" ] || [ "${arguments[i]}" == "--author" ]; then
			author=${arguments[i + 1]}
			newArticleMode=true
		fi

		# Get article description.
		if [ "${arguments[i]}" == "-d" ] || [ "${arguments[i]}" == "--description" ]; then
			description=${arguments[i + 1]}
			newArticleMode=true
		fi

		# Get article file path.
		if [ "${arguments[i]}" == "-f" ] || [ "${arguments[i]}" == "--file" ]; then
			articleFile=${arguments[i + 1]}
			newArticleMode=true
		fi

		# Get article tags.
		if [ "${arguments[i]}" == "-t" ] || [ "${arguments[i]}" == "--tags" ]; then
			articleTags=${arguments[i + 1]}
			newArticleMode=true
		fi

		# Get article image path.
		if [ "${arguments[i]}" == "-i" ] || [ "${arguments[i]}" == "--image" ]; then
			articleImage=${arguments[i + 1]}
			newArticleMode=true
		fi

		# Remove article.
		if [ "${arguments[i]}" == "-ra" ] || [ "${arguments[i]}" == "--remove-article" ]; then
			articleToRemove=${arguments[i + 1]}
			removeArticleMode=true
		fi

		# List comments for specific article.
		if [ "${arguments[i]}" == "-lc" ] || [ "${arguments[i]}" == "--list-comments" ]; then
			article=${arguments[i + 1]}
			listCommentsMode=true
		fi

		# Remove comment.
		if [ "${arguments[i]}" == "-rc" ] || [ "${arguments[i]}" == "--remove-comment" ]; then
			commentToRemove=${arguments[i + 1]}
			removeCommentMode=true
		fi

		# Toggle comment approval.
		if [ "${arguments[i]}" == "-ac" ] || [ "${arguments[i]}" == "--approve" ]; then
			commentToApprove=${arguments[i + 1]}
			approveCommentMode=true
		fi

		# List pending comments.
		if [ "${arguments[i]}" == "-lp" ] || [ "${arguments[i]}" == "--list-pending" ]; then
			listPendingCommentsMode=true
		fi

		# List pending comments.
		if [ "${arguments[i]}" == "-ur" ] || [ "${arguments[i]}" == "--update-rss" ]; then
			updateRssMode=true
		fi

		# Add author.
		if [ "${arguments[i]}" == "-aa" ] || [ "${arguments[i]}" == "--add-author" ]; then
			authorName=${arguments[i + 1]}
			addAuthorMode=true
		fi

		# Get author bio.
		if [ "${arguments[i]}" == "-ab" ] || [ "${arguments[i]}" == "--author-bio" ]; then
			authorBio=${arguments[i + 1]}
			addAuthorMode=true
		fi

		# Get author image.
		if [ "${arguments[i]}" == "-ai" ] || [ "${arguments[i]}" == "--author-image" ]; then
			authorImage=${arguments[i + 1]}
			addAuthorMode=true
		fi

		# remove author.
		if [ "${arguments[i]}" == "-rA" ] || [ "${arguments[i]}" == "--remove-author" ]; then
			authorName=${arguments[i + 1]}
			removeAuthorMode=true
		fi
	done
}

# Make sure only one mode is active.
verifyMode() {
	modeCount=0

	if $removeArticleMode; then
		((modeCount++))
	fi

	if $newArticleMode; then
		((modeCount++))
	fi

	if $listCommentsMode; then
		((modeCount++))
	fi

	if $removeCommentMode; then
		((modeCount++))
	fi

	if $approveCommentMode; then
		((modeCount++))
	fi

	if $listPendingCommentsMode; then
		((modeCount++))
	fi

	if $updateRssMode; then
		((modeCount++))
	fi

	if $addAuthorMode; then
		((modeCount++))
	fi

	if $removeAuthorMode; then
		((modeCount++))
	fi

	if [ "$modeCount" -ge 2 ]; then
		printf "Error: Too many operations at once.\n" >&2
		exit
	fi
}

# Add new article to sqlite articles table.
addNewArticle() {
	# Check that required variables are set.
	missingVars=false
	if [ -z "$articleName" ]; then
		printf "Error: No article name provided.\n" >&2
		missingVars=true
	fi

	if [ -z "$articleTitle" ]; then
		printf "Error: No article title provided.\n" >&2
		missingVars=true
	fi

	if [ -z "$author" ]; then
		printf "Error: No author provided.\n" >&2
		missingVars=true
	fi

	if [ -z "$description" ]; then
		printf "Error: No article description provided.\n" >&2
		missingVars=true
	fi

	if [ -z "$articleFile" ]; then
		printf "Error: No article file path provided.\n" >&2
		missingVars=true
	fi

	if [ -z "$articleTags" ]; then
		printf "Error: No article tags provided.\n" >&2
		missingVars=true
	fi

	if [ -z "$articleImage" ]; then
		printf "Error: No article image provided.\n" >&2
		missingVars=true
	fi

	if [ "$missingVars" == true ]; then
		exit
	fi

	# Generate unique article id.
	exists=1
	while [ "$exists" -ne 0 ]; do
		articleId="a-"
	
		for i in {1..10}; do
			digit=$(( RANDOM % 10 ))
			articleId+="$digit"
		done
	
		# Check that article id is unique.
		exists=$(sqlite3 "$DATABASE" "SELECT COUNT(*) FROM articles WHERE article_id = '$articleId';")
	done

	# Check that article name is unique.
	exists=$(sqlite3 "$DATABASE" "SELECT COUNT(*) FROM articles WHERE name = '$articleName';")

	if [ "$exists" -ne 0 ]; then
		printf "Error: Article Name already in use.\n" >&2
		exit
	fi

	# Add article to table.
	sqlite3 "$DATABASE" "INSERT INTO articles (article_id, title, name, author, description, file, tags, image) VALUES ('$articleId', '$articleTitle', '$articleName', '$author', '$description', '$articleFile', '$articleTags', '$articleImage')"
	printf "Added '$articleTitle' to database.\nNow is probably a good time to go online and check if it's working.\nAfter that, consider updating the RSS feed.\n"
}

removeArticle() {
	# Check if article exists.
	exists=$(sqlite3 "$DATABASE" "SELECT COUNT(*) FROM articles WHERE name = '$articleToRemove';")
	if [ $exists -ne 1 ]; then
		printf "Error: Article dose not exist.\n" >&2
		exit
	fi

	# Remove article.
	sqlite3 "$DATABASE" "DELETE FROM articles WHERE name = '$articleToRemove'"
	echo "Removed article '$articleToRemove'"
}

# List comments from a specific article.
listComments() {
	# Check if article exists.
	exists=$(sqlite3 "$DATABASE" "SELECT COUNT(*) FROM articles WHERE name = '$article';")
	if [ $exists -ne 1 ]; then
		printf "Error: Article dose not exist.\n" >&2
		exit
	fi

	# Find article id from name.
	articleId=$(sqlite3 "$DATABASE" "SELECT article_id FROM articles WHERE name = '$article';")

	# List comments with matching context id.
	output=$(sqlite3 "$DATABASE" "SELECT comment_id, name, email, comment, ip_address, visible, created_at FROM comments WHERE context_id = '$articleId';")

	# Print output.
	echo "comment-id|name|email|comment|ip|approved|date"
	echo "$output"
}

removeComment() {
	# Check if comment exists.
	exists=$(sqlite3 "$DATABASE" "SELECT COUNT(*) FROM comments WHERE comment_id = '$commentToRemove';")
	if [ $exists -ne 1 ]; then
		printf "Error: Comment dose not exist.\n" >&2
		exit
	fi

	# Remove comment.
	sqlite3 "$DATABASE" "DELETE FROM comments WHERE comment_id = '$commentToRemove'"
	echo "Removed comment '$commentToRemove'"
}

toggleCommentApproval() {
	# Check if comment exists.
	exists=$(sqlite3 "$DATABASE" "SELECT COUNT(*) FROM comments WHERE comment_id = '$commentToApprove';")
	if [ $exists -ne 1 ]; then
		printf "Error: Comment dose not exist.\n" >&2
		exit
	fi

	# Check comment visibility.
	visible=$(sqlite3 "$DATABASE" "SELECT visible FROM comments WHERE comment_id = '$commentToApprove'")

	# Change comment visibility.
	if [ "$visible" == true ]; then
		sqlite3 "$DATABASE" "UPDATE comments SET visible = 'false' WHERE comment_id = '$commentToApprove';"
		echo "$commentToApprove hidden."
	elif [ "$visible" == false ]; then
		sqlite3 "$DATABASE" "UPDATE comments SET visible = 'true' WHERE comment_id = '$commentToApprove';"
		echo "$commentToApprove visible."

	fi
}

listPendingComments() {
	# List comments with visibility set to false.
	output=$(sqlite3 "$DATABASE" "SELECT context_id, comment_id, name, email, comment, ip_address, created_at FROM comments WHERE visible = 'false' ORDER BY created_at ASC;")

	echo "comment-id|article|name|email|comment|ip|date"

	while IFS= read -r line; do
		contextId=$(echo $line | cut -d '|' -f 1)

		# Resolve context_id to article name.
		articleName=$(sqlite3 "$DATABASE" "SELECT name FROM articles WHERE article_id = '$contextId'")

		# Print output
		echo "$line" | awk -v article="$articleName" 'BEGIN{FS=OFS="|"} {print $2, article, $3, $4, $5, $6, $7}'
	done <<< $output
}

updateRss() {
	# RSS feed headers
	printf "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
	printf "<rss version=\"2.0\">\n"
	printf "\t<channel>\n"
	printf "\t\t<title>Blog</title>\n"
	printf "\t\t<link>http://test.example.com</link>\n"
	printf "\t\t<description>RSS feed for blog.</description>\n"
	printf "\t\t<language>en-us</language>\n"

	# Fetch articles from the SQLite database
	output=$(sqlite3 "$DATABASE" "SELECT title, name, description, author, date FROM articles ORDER BY date DESC")

	echo "$output" | while IFS='|' read -r title name description author date; do
		# Print each article in RSS format
		printf "\t\t<item>\n"
		printf "\t\t\t<title><![CDATA[$title]]></title>\n"
		printf "\t\t\t<link>http://test.example.com/article.php?article=$name</link>\n"
		printf "\t\t\t<description><![CDATA[$description]]></description>\n"
		printf "\t\t\t<author>$author</author>\n"
		printf "\t\t\t<pubDate>$(date -u -d "$date" +"%a, %d %b %Y %H:%M:%S +0000")</pubDate>\n"
		printf "\t\t</item>\n"
	done

	# Close channel
	printf "\t</channel>\n"
	printf "</rss>\n"
}

addAuthor() {
	# Check that required variables are set.
	missingVars=false
	if [ -z "$authorName" ]; then
		printf "Error: No author name provided.\n" >&2
		missingVars=true
	fi

	if [ -z "$authorBio" ]; then
		printf "Error: No author bio provided.\n" >&2
		missingVars=true
	fi

	if [ -z "$authorImage" ]; then
		printf "Error: No author image provided.\n" >&2
		missingVars=true
	fi

	if [ "$missingVars" == true ]; then
		exit
	fi

	# Add new author.
	sqlite3 "$DATABASE" "INSERT INTO authors (name, bio, image) VALUES ('$authorName', '$authorBio', '$authorImage')"
	echo "Added author '$authorName'"
}

removeAuthor() {
	# Check if author exists.
	exists=$(sqlite3 "$DATABASE" "SELECT COUNT(*) FROM authors WHERE name =  '$authorName';")
	if [ $exists -ne 1 ]; then
		printf "Error: Author dose not exist.\n" >&2
		exit
	fi

	# Remove comment.
	sqlite3 "$DATABASE" "DELETE FROM authors WHERE name = '$authorName';"
	echo "Removed author '$authorName'"
}

evalArgs "$@"
verifyMode
if [ "$newArticleMode" == true ]; then
	addNewArticle
fi

if [ "$removeArticleMode" == true ]; then
	removeArticle
fi

if [ "$listCommentsMode" == true ]; then
	listComments
fi

if [ "$removeCommentMode" == true ]; then
	removeComment
fi

if [ "$approveCommentMode" == true ]; then
	toggleCommentApproval
fi

if [ "$listPendingCommentsMode" == true ]; then
	listPendingComments
fi

if [ "$updateRssMode" == true ]; then
	updateRss
fi

if [ "$addAuthorMode" == true ]; then
	addAuthor
fi

if [ "$removeAuthorMode" == true ]; then
	removeAuthor
fi
