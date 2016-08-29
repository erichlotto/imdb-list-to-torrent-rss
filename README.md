# IMDb list to torrent RSS
This script fetches all movies from a public list on IMDb, search each one on Yify databases and generate a RSS containing the available download links.

Installation:

1. Simply upload index.php, database_helper.php and default.html to your server.
2. Edit database_helper.php and fill $dbname, $username and $password.
3. Open index.php on your web browser (this will create the required table on the database).
4. Refresh the page, insert your IMDb's list id and click on "Generate URL".
5. Use your custom URL on your torrent client to automate things and be happy :)

If you don't have a server and just want to create your own RSS, you can use: http://api.erichlotto.com/imdb-yify/
