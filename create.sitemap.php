#!/usr/bin/php
<?php
/**
Creates a sitemap.xml which can be processed by a search engine. The file should not be placed in the web space, but in a directory next to it. The sitemap is generated again and again, the lastmod attribute uses the timestamp of the respective file. If no changes have been made, the sitemap is regenerated with the same content. 

It is recommended to run the script as a CronJob.


# For example, you can run a backup of all your user accounts
# at 5 a.m every week with:
# 0 5 * * 1 tar -zcf /var/backups/home.tgz /home/
#
# For more information see the manual pages of crontab(5) and cron(8)
#
# m     h       dom     mon     dow     command
15      *       *       *       *       /opt/scripts/bash/fileutils/create.sitemap.php /opt/prod/boredoc-web-static/ https://boredoc.eu /opt/prod/boredoc-web-static/sitemap.xml

*/
function createSitemap($dir, $domain, $outputFile) {
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;

    $urlset = $xml->createElement('urlset');
    $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $urlset->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $urlset->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
     
    $xml->appendChild($urlset);

    $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($allFiles as $file) {
        if ($file->isFile()) {
            $url = $xml->createElement('url');
	    $fName = $file->getPathname();
	    if (!isSupported($fName))
		continue;

	    $urlStr = toUrl($domain, $dir, $fName);
            $loc = $xml->createElement('loc', $urlStr);
            $url->appendChild($loc);
            
            $lastmod = $xml->createElement('lastmod', date('c', $file->getMTime()));
            $url->appendChild($lastmod);
            
            $urlset->appendChild($url);
        }
    }

    $xml->save($outputFile);
}
function isSupported($file){
    $file = strtolower($file);
	$fname = basename($file);
    if (str_starts_with($fname, 'yandex_'))return false;
    if (str_starts_with($fname, 'Bing'))return false;
    if (str_starts_with($fname, 'google'))return false;
    if (str_ends_with($file, 'php'))return true;
    if (str_ends_with($file, 'html'))return true;
    if (str_ends_with($file, 'htm'))return true;
    if (str_ends_with($file, 'svg'))return true;
    if (str_ends_with($file, 'png'))return true;
    if (str_ends_with($file, 'jpg'))return true;
    if (str_ends_with($file, 'jpeg'))return true;
    if (str_ends_with($file, 'pdf'))return true;
    return false;

}
function toUrl($domain, $dir, $file){
    $dir = rtrim($dir, '/');
    $url = rtrim($domain, '/');
    $path = substr($file, strlen($dir));
    $path = str_replace(' ', '%20', $path);
    $url .= $path;

//    echo "Dir: $dir\tFile: $file\tDomain: $domain\tPath: $path\tUrl: $url\n";
    return $url;
}

// Obtain directory via command line parameters
if ($argc < 3) {
    echo "Please enter the directory, the domain and the output path for the sitemap as command line parameters.\n";
    exit(1);
}

$directory = $argv[1];
$domain = $argv[2];
$outputFile = $argv[3];


// Sitemap erstellen
createSitemap($directory, $domain, $outputFile);

#echo "Sitemap was successfully created: $outputFile\n";
?>
