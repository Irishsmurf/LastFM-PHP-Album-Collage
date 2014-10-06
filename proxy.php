<?php
function save_image($img,$fullpath)
{
	$ch = curl_init ($img);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
	curl_setopt($ch, CURLOPT_PROXY, "proxy.dcu.ie");
	curl_setopt($ch, CURLOPT_PROXYPORT, 8080); 
	$rawdata=curl_exec($ch);
	
	curl_close ($ch);
	if(file_exists($fullpath)){
		unlink($fullpath);
	}
	$fp = fopen($fullpath,'x');
	fwrite($fp, $rawdata);
	fclose($fp);
}
			
function proxy_url($proxy_url)
{
	$proxy_name = 'proxy.dcu.ie';
	$proxy_port = 8080;
	$proxy_cont = '';
	$proxy_fp = fsockopen($proxy_name, $proxy_port);
	if (!$proxy_fp)
	{
		return false;
	}
	fputs($proxy_fp, "GET $proxy_url HTTP/1.0\r\nHost: $proxy_name\r\n\r\n");
	while(!feof($proxy_fp)) 
	{
		$proxy_cont .= fread($proxy_fp,4096);
	}
	fclose($proxy_fp);
	$proxy_cont = substr($proxy_cont, strpos($proxy_cont,"\r\n\r\n")+4);
	return $proxy_cont;
} 
?>
