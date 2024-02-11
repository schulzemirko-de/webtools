<?php

/**
Starts the main programme loop. Iterates over all configured locally hosted websites. A separate API key should be used for each page.
Each individual file is read and saved in settings.json. 
If a file has been changed, the file is resent to the search engine. 
If files have been deleted, deleted files can be sent to the search engine again so that they are removed from the search index.

The call can be integrated as a cron job, for example.
*/

runIndexNow();




function runIndexNow(){
    $cfg_file  = 'indexnow.settings.json';
    $cfg_data = readData($cfg_file);
    $engines = $cfg_data->{'engines'};
    $sites = $cfg_data->{'sites'};
    $last_run = $cfg_data->{'last_run'};


    for($siteIdx=0;$siteIdx<count($sites);$siteIdx++){
        $s = $sites[$siteIdx];
        $api_key = $s->{'key'};
        $basepath = $s->{'basepath'};
        $last_files = $s->{'last_files'};
        $all_files = files($basepath);

        $keyfile = $basepath.'/'.$api_key . '.txt';
        file_put_contents($keyfile,$api_key);

        foreach($engines as $e){
            $name = $e->{'name'};
            $include = $e->{'include'};
            $exclude = $e->{'exclude'};

//            echo "Engine:\t $name \n";

            $files_mod = array();
            $files_rel = array();
            foreach($all_files as $f){
                if (!str_contains_any($f, $include))
                    continue ;
                if (str_contains_any($f, $exclude))
                    continue ;
                $mtime = filemtime($f);
                $fname = str_remove_first($f, $basepath);
                $files_rel[] = $fname;
                if ($mtime >= $last_run)
                    $files_mod[] = $fname;
            }
            $files_del = array_diff($last_files, $files_rel);
            $files_to_be_transmitted = array_merge($files_mod, $files_del);
            transfer($api_key, $e, $s, $files_to_be_transmitted);
        }
        $s->{'last_files'}= $files_rel;
    }
    $cfg_data->{'last_run'} = time();
    writeData($cfg_file, $cfg_data);
}

function transfer(string $api_key, object $engine, object $site, array $files){
    $prot = 'https://';
    $website_url = $site->{'url'};
    $base_url = $prot.$website_url;
    $eUrl = $engine->{'url'};
    $eName = $engine->{'name'};

    if (empty($api_key)) {
        return;
    }

    $urls = str_prepend($base_url, $files);
    foreach($urls as $changed_url){
        $url = str_replace('url-changed',$changed_url, $eUrl);
        $url = str_replace('your-key',$api_key, $url);
//        echo "URL: $url \n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_exec($ch);
        if (!curl_errno($ch)) {
            switch ($httpcode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE)) {
                case 200:
                case 202:
//                    echo "Sending indexnow data to $eName ($url) was successfully sent\n";
                    break;
                default:
                    echo "Sending indexnow data to $eName ($url) was not sent. HTTP ErrorCode: $httpcode \n";
            }
        }
        curl_close($ch);
    }
    return;
}

function readData($cfg_file){
    if(file_exists($cfg_file)) {
        $json_str = file_get_contents($cfg_file); //data read from json file
        $json_obj = json_decode($json_str);  //decode a data as object
        if ($json_obj == null){
            echo "\nSettings invalid!\n";
            debug($json_str);
            return null;
        }
        return $json_obj;
    }

}

function writeData($cfg_file, $data){
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);  //decode a data
    file_put_contents($cfg_file, $json); //data read from json file
}

function debug($object){
    $debug = true;
    if ($debug){
        print_r($object);
    }
}

function files($dir, ?array $retval = null){
    if ($retval == null) $retval = array();
    $ffs = scandir($dir);
    unset($ffs[array_search('.', $ffs, true)]);
    unset($ffs[array_search('..', $ffs, true)]);
    if (count($ffs) < 1)
        return;
    foreach($ffs as $ff){
        $retval[] = $dir.'/'.$ff;
        if(is_dir($dir.'/'.$ff))
            $retval = array_merge($retval, files($dir.'/'.$ff));
    }
    return $retval;
}

function str_contains_any(string $haystack, array $needles): bool {
    return array_reduce($needles, fn($a, $n) => $a || str_contains($haystack, $n), false);
}

function str_contains_all(string $haystack, array $needles): bool {
    return array_reduce($needles, fn($a, $n) => $a && str_contains($haystack, $n), true);
}

function str_remove_first(string $input, string $needle) : string {
    $pos = strpos($input, $needle);
        if ($pos !== false) {
            return substr_replace($input, '', $pos, strlen($needle));
    }
    return $input;
}

function str_prepend(string $prefix, array $files): array{
    for($i=0, $l = count($files);$i<$l;$i++){
        $files[$i] = $prefix . $files[$i];
    }
    return $files;
}
