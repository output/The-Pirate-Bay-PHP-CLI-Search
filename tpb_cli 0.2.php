<?php

/*
 * The Pirate Bay PHP CLI Search v0.2
 * 
 * 0.2 (2013-11-01)
 * - Support multiple search pages.
 * - Fastest scan from previous version.
 *
 * 0.1 (2012-07-11)
 * - Initial release.
 *
 */

echo "\nTPB_CLI v0.2 GFY Public License (c) 2012 Phibe Optik @ Mohd Shahril 2013-11-01\n";

$open = new thepiratebay;

if(isset($argv[1])){
	if($argv[1] == "--search"){
		if(isset($argv[2])){
			$searchresult = $open->search($argv[2]);
			if($searchresult != false){
				echo "\nGet ".count($searchresult)." Results \n\n";
				foreach($searchresult as $pecah){
					$a = explode("/", $pecah);
					$source = $open->getsource($a[0]);
					echo $a[1]."\n";
					echo "ID : ".$a[0]."\n";
					echo "Seed/Leech : ".$open->getsele($source)."\n";
					echo "Author : ".$open->getauthor($source)."\n\n";
				}
			}else{
				echo "\n Can't find anything ! \n";
			}
		}else{
			echo "\n Please type what you want to search\n";
		}
	}elseif($argv[1] == "--magnet"){
		if(isset($argv[2])){
			echo "\n".$open->getmagnet($argv[2])."\n";
		}
	}else{
		echo "\n Error with argument \n";
	}
}else{
	echo "
TPB_CLI can be use to search content of ThePirateBay using PHP Cli.
It's also can extract magnet link with provide ID of page content.

 php ".$argv[0]." --search <search> : Search content in ThePirateBay
 php ".$argv[0]." --magnet <id> : Extract MAGNET link with provide ID
	
  example:
   php ".$argv[0]." --search \"metallica\"
   php ".$argv[0]." --magnet \"7712336\"
	
	";
}

class thepiratebay{
	
	public function search($string){ //data will return in array [ID/FILENAME]
		$search = str_replace(" ", "%20", $string);
		$dataget = $this->getdata("http://thepiratebay.se/search/".$search."/0/99/0", "", "");
		if(strpos($dataget, "No hits")){ 
			return false; 
		}elseif(strpos($dataget, '>2<')){
			echo "\nPlease wait while this PHP parsing data from multiple pages..\n";
			if(strpos($dataget, '>10</a>&nbs')){ echo "*Note : Multiple pages more than 10, may take long time for searching :(\n"; }
			$simpan = array();
			$caripage = explode('<div align="center">', $dataget);
			$caripage = explode('alt="Next"', $caripage[1]);
			$caripage = explode('&nbsp;', $caripage[0]);
			$caripage = array_splice($caripage, 1);
				
			for($i = 0;$i < count($caripage);$i++){
				$loopget = $this->getdata("http://thepiratebay.se/search/".$search."/".$i."/99/0/", "", "");
				$data_pecah = explode('<a href="/torrent/', $loopget);
					
				// Find ID and FileName
				foreach($data_pecah as $pecah){
					$a = explode('" class="detLink"', $pecah);
					$simpan[] = $a[0];
				}
				$simpan = array_unique($simpan);
			}
		}else{
			$simpan = array();
			$data_pecah = explode('<a href="/torrent/', $dataget);
			
			// Find ID and FileName
			foreach($data_pecah as $pecah){
				$a = explode('" class="detLink"', $pecah);
				$simpan[] = $a[0];
			}
		}
		return array_splice($simpan, 1);
	}
	
	public function getsource($id){
		return $this->getdata("http://thepiratebay.se/torrent/".$id."/", "", "");
	}
	
	// Find Seeders/Leechers 
	public function getsele($data){
		$dataget = explode("<dt>Seeders:</dt>", $data);
		$seeders = explode("<dt>Leechers:</dt>", $dataget[1]);
		$leechers = explode("<dt>Comments</dt>", $seeders[1]);
		return trim($this->removedd($seeders[0]))."/".trim($this->removedd($leechers[0]));
	}
	
	// Get author name
	public function getauthor($data){
		if(strpos($data, "Anonymous")){
			return "Anonymous";
		}else{
			$dataget = explode('<a href="/user/', $data);
			$author = explode('">', $dataget[1]);
			$author = explode('</a>', $author[1]);
			return $author[0];
		}
	}
	
	// Get magnet link
	public function getmagnet($id){
		$data = $this->getdata("http://thepiratebay.se/torrent/".$id."/", "", "");
		$dataget = explode('xt=urn', $data);
		$dataget = explode('" title=', $dataget[1]);
		return "xt=urn".$dataget[0];
	}
	
	private function removedd($string){
		$data = str_replace("<dd>", "", $string);
		return str_replace("</dd>", "", $data);
	}
	
	private function getdata($url, $cookies, $post){
		$ch = @curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		if ($cookies) curl_setopt($ch, CURLOPT_COOKIE, $cookies);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($post){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 20);
		$page = curl_exec( $ch);
		curl_close($ch); 
		return $page;
	}
}

?>