#!/usr/bin/php
<?php
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

if ($argc < 3) {
    echo "Please enter the directory, the domain and the full output path for the sitemap as command line parameters.\n";
    exit(1);
}

$directory = $argv[1];
$domain = $argv[2];
$outputFile = $argv[3];


// Sitemap erstellen
createSitemap($directory, $domain, $outputFile);

#echo "Sitemap was successfully created: $outputFile\n";
?>
