<?php
class DriveHandler
{

public function __construct() {
	require_once plugin_dir_path( __FILE__ ) .'Classes/google-api-php-client/src/Google_Client.php';
	require_once plugin_dir_path( __FILE__ ) . 'Classes/google-api-php-client/src/contrib/Google_DriveService.php';
	require_once plugin_dir_path( __FILE__ ) . 'Classes/google-api-php-client/src/contrib/Google_DriveService.php';
	}

public function setaccesstoken(){
	
	if(!$_COOKIE['access_token'])
	{
		return new WP_Error('notloggedin', __("No access token found"));
	}
		$this->client = new Google_Client();
		$this->client->setUseObjects(true);
		$this->client->setAccessToken(urldecode($_COOKIE['access_token']));
		$this->service = new Google_DriveService($this->client);
}

public function gethtmllink($url){
		preg_match("/[0-9a-zA-Z\-_]{44}/",$url,$id);
		
		if(!$id[0]){return  new WP_Error('noid', __("No Id in url"));}
		
		$file = $this->service->files->get($id[0]);
		$downloadUrl = $file->getExportLinks();
		$htmllink = $downloadUrl['text/html'];
		return $htmllink;
		
		
	}//end of gethtmllink
	
	public function gethtml($url){
			$htmllink = $this->gethtmllink($url);
			$request = new Google_HttpRequest($htmllink, 'GET', null, null);
			$httpRequest = Google_Client::$io->authenticatedRequest($request);
			
			if ($httpRequest->getResponseHttpCode() == 200) 
			{
			  $fulldoc = $httpRequest->getResponseBody();
			  return $fulldoc;
			} 
			else {
		  		 return new WP_Error('drivefail', __("No response genrated by id sent"));
			}
	}//end of gethtmllink
	
	public function getcleanhtml($url){
			$htmllink = $this->gethtmllink($url);
			$html = $this->gethtml($htmllink);
			$fulldoc = str_get_html($fulldoc);
			$remove = array ('title,meta,style');
			
			foreach($html->find('title,meta,style') as $z)
			{
			 $z->outertext = "";
			}
			
			foreach($html->find('p span') as $e)
			{
			 $e->outertext = $e->innertext;
			}
			
			foreach($html->find('li p') as $y)
			{
			 $y->outertext = $y->innertext;
			}
			
			
			foreach($html->find('[class]') as $x)
			{
				$x->class = null;
			}
		
			
			foreach($html->find('p,a') as $x)
			{
				if($x->innertext == "")
				$x->outertext = "";
			}
		
		
			$ret = $html->save();
		
			// clean up memory
			$html->clear();
			unset($html);
		
			return $ret;
	}//end of gethtmllink
	
}//end of class
?>