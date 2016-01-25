#!/usr/bin/php
<?php
	if (count($argv) != 2) die('Skrypt wymaga jednego parametru - nazwy projektu');
	$project = $argv[1];

	$vhost		= '/etc/httpd/vhosts/' . $project . '.conf';
	$syncDir	= '/home/forseti/Sync/Projekty/' . $project;
	$projDir	= '/home/forseti/Dokumenty/Kod/PHP/' . $project;
	$docRoot	= '/var/www/html/' . $project;
	$hosts		= '/etc/hosts.d/' . $project . 'conf';

	// Sprawdzamy
	$checks = array(
		['makeVhost', $vhost, 'Virtual host '],
		['makeSyncDir', $syncDir, 'Katalog synchronizowany '],
		['makeProjDir', $projDir, 'Katalog projektu '],
		['makeDocRoot', $docRoot, 'Link do Apache documentRoot '],
		['makeHostFile', $hosts, 'Plik hosts dla ']
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
		AllowOverride All
		Order allow,deny
		allow from all
	</Directory>

	ErrorLog /var/www/html/$project/app/log/error.log
    CustomLog /var/www/html/$project/app/log/access.log combined

    php_flag log_errors on
    php_flag display_errors on
    php_value error_reporting 2147483647
    php_value error_log /var/www/html/$project/app/log/php.error.log

</VirtualHost>
EOT
	);
}

function makeSyncDir() {
global $syncDir;

	// Stwórz katalog projektu w /home/user
	mkdir($syncDir, 0755);
	exec("cp -r new_php/* $syncDir");
	system("chown -R forseti:apache $syncDir");
	system("chmod 0755 $syncDir/app/log");
	system('semanage fcontext -a -t httpd_sys_rw_content_t "'. $syncDir .'/app/log(/.*)?"');
	system("restorecon -R $syncDir/app/log");
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

function makeHostFile() {
global $project;

	$data = '';
	foreach ([11,12] as $host) {
		$data .= "192.168.8.$host    $project.forseti.home\n";
	}
	
	file_put_contents("/etc/hosts.d/$project.conf", $data );
	exec('/etc/hosts.d/make_hosts.sh');
}

function checkDomain($project) {

	// zresetuj Apache'a
	exec('systemctl restart httpd.service');

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
