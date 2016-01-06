#!/usr/bin/php
<?php
	$ret = 0;
	$project = system('yad --width 400 --entry --title "Nowy projekt PHP" --button="gtk-ok:0" --button="gtk-cancel:1" --text "Podaj nazwę projektu"', $ret);
	if ($ret != 0) {
		echo $ret."\n";
		exit;
	}

	$vhost		= '/etc/httpd/vhosts/' . $project . '.conf';
	//$vhostLink	= str_replace('available','enabled',$vhost);
	$syncDir	= '/home/forseti/Sync/Projekty/' . $project;
	$projDir	= '/home/forseti/Dokumenty/Kod/PHP/' . $project;
	$docRoot	= '/var/www/html/' . $project;

	// Sprawdzamy
	$checks = array(
		['makeVhost', $vhost, 'Virtual host '],
		/* ['makeVhostLink', $vhostLink, 'Link do vhosta '], */
		['makeSyncDir', $syncDir, 'Katalog synchronizowany '],
		['makeProjDir', $projDir, 'Katalog projektu '],
		['makeDocRoot', $docRoot, 'Link do Apache documentRoot ']
		);

	foreach($checks as list($proc,$path,$msg)) {
		if (! file_exists($path)) {
			$proc();
			echo $msg  . $project . " utworzony\n";
		} else {
			echo $msg  . $project . " już istnieje\n";
		}
	}

	checkDomain($project);
	exit (0);

	// Stwórz nowego virtualhosta Apache'a
function makeVhost() {
	global $project, $vhost, $docRoot;

	file_put_contents($vhost,
		<<<EOT
<VirtualHost *:80>
	ServerName $project.forseti.home

	ServerAdmin webmaster@localhost
	DocumentRoot $docRoot/web

	<Directory $docRoot/web>
		Options Indexes FollowSymlinks Multiviews
		AllowOverride None
		Order allow,deny
		allow from all
	</Directory>

	ErrorLog \${APACHE_LOG_DIR}/error.log
	CustomLog \${APACHE_LOG_DIR}/access.log combined

	#Include conf-available/serve-cgi-bin.conf
</VirtualHost>
EOT
	);
}

function makeVhostLink() {
global $vhost, $vhostLink;

	symlink($vhost, $vhostLink);
}

function makeSyncDir() {
global $syncDir;

	// Stwórz katalog projektu w /home/user
	mkdir($syncDir . '/web', 0755, true);
	copy('new_php/index.php', $syncDir . '/web/index.php');
	system("chown -R forseti:forseti $syncDir");
}

function makeProjDir() {
global $syncDir, $projDir;

	// Stwórz symlinka z /var/www/html/ do katalogu projektu
	symlink($syncDir, $projDir);
}

function makeDocRoot() {
global $vhost, $vhostLink, $syncDir, $projDir, $docRoot;

	symlink($syncDir, $docRoot);
}

function checkDomain($project) {

	// dopisz do /etc/hosts nową domenę dla vhosta
	file_put_contents('/etc/hosts', '192.168.8.11    ' . $project . ".forseti.home\n", FILE_APPEND);

	// zresetuj Apache'a
	exec('service apache2 restart');

	// sprawdzamy
	$cH = curl_init('http://'. $project .'.forseti.home');
	curl_setopt($cH,  CURLOPT_RETURNTRANSFER, true);
	curl_exec($cH);
	$httpCode = curl_getinfo($cH, CURLINFO_HTTP_CODE);
	if ($httpCode != 200) {
		echo 'Błąd ' . $httpCode;
		exit (127);
	}
	curl_close($cH);
}

?>
