<html>
	<head>
		<title>ANSI to HTML Example</title>
		<link rel="stylesheet" href="ansi_singletag.css" />
		<style type="text/css">
			body {background-color: black;}
			.mush {color: white;}
		</style>
	</head>
	<body>

<?php
	require 'ansi_to_html.php';
	$teststr  = "nothing \033[33myellow \033[1mwith hilite \033[31mred hilite \033[0m\033[31mred \033[33myellow \033[4mwith underline\033[0m plain";
	$teststr .= "\n\r<br />XTerm: \033[38;5;214mOrange\033[0m";
	echo ansi_string_to_html($teststr);
?>

	</body>
</html>
