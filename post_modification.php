<?php 

class PostModification
{
protected $prefix = 'siteplan_to_wp_converter';
public $remove_just_tags = array ('div','span','td','tr','tbody','table','&nbsp;','center');//tags that will be striped but content remain -- (will be editble from plugin later)
public $atrr_to_remove = array ('id','class','style');//attrubes that will be striped -- (will be editble from plugin later)
public $remove_entire_tag = array ('comment','script','style');//tags whose content will also  be removed -- (will be editble from plugin later)

private function load_options(){
		$this->legacy_page_options = get_option('legacy_page');
		$this->legacy_post_options = get_option('legacy_post');
		$this->new_url_list =  get_option('new_url'); 
		$this->old_url_list =  get_option('old_url');
}


private function special_html_rules_before($html)//runs before deafult content clean still has everything including attrs and junk tags
{
	foreach($html->find('.rss_items') as $x)
	{
		$x->outertext ='';
	}
	
	foreach($html->find('h4') as $y)
	{
		$y->outertext ='';
	}
	
	return $html;
}
		
	
private function special_html_rules_after($html)//runs after deafult content clean
{
	
	return $html;
}
		
		
		
		
public function UpdateLegacyContent($post_data)
{
	
	/////------------------------------------------------------------- first loop page creation --------------------------------------------////				
	$this->load_options();//load option for acess 
	$pagesheet_array=$post_data['page_sheet'];
	$page_update = 0;

							array_shift($pagesheet_array);
							foreach ($pagesheet_array as $value) { 
							if($value[$this->legacy_page_options['es_PageTitle']] == "" || $value[$this->legacy_page_options['es_PageTitle']] == NULL)
							{
								continue;//skips if no slug
							}
							
							
								 $added_page = get_page_by_title($value[$this->legacy_page_options['es_PageTitle'] ]);//gets the object for existing page
								 
								 
										if ($added_page!=NULL){
											$page_update++;
							 				if ($this->legacy_page_options['es_PageContent'] !=""){// use substring repalce to alter all urls at once
												 $content  = $value[$this->legacy_page_options['es_PageContent'] ]; 
												
										
												 //////thesea re used for clean content can be turn to public vars later//////////	
												$old_post_id = $added_page->ID;//gets id used in functions
												$oldurl = $value[$this->legacy_page_options['es_PageOldUrl']];
												
												$cleanedcontent = $this->clean_post_html($content,$old_post_id,$oldurl);
												/////////////////////////////////////////
												
												$content =  str_replace($this->old_url_list, $this->new_url_list, $cleanedcontent);
											 }
											 
											 
												if ($this->legacy_page_options['es_PageH1'] != ""){ // add h1 to content
													$h1 = "<h1>".$value[$this->legacy_page_options['es_PageH1']]."</h1>";
													$content = $h1 . $content;
												}
												
												
											
												
											global $wpdb; $parent_postID;
											 if ($this->legacy_page_options['es_parentPage'] != ""){ // Get Parent URL path: after-hours-and-weekend-appointments			
												 $parent_url = parse_url($value[$this->legacy_page_options['es_parentPage']]);
												 
												 $parenturl_Slug = trim($parent_url['path'],'/');
											
												 $parent_postID = $wpdb->get_var("SELECT post_id FROM $wpdb->postmeta WHERE meta_value='$parenturl_Slug'" );
										
											 } 
											 
											$my_post = array(
													  'ID'			  => $added_page->ID,
													  'post_title'    => $value[$this->legacy_page_options['es_PageTitle']],
													  'post_content'  => $content,
													  'post_parent'   => $parent_postID,
													);
											$post_id = wp_update_post( $my_post );
											if (!update_post_meta ($post_id, 'OldURL', $value[$this->legacy_page_options['es_PageOldUrl']] ) ) 
												 		add_post_meta( $post_id, 'OldURL', $value[$this->legacy_page_options['es_PageOldUrl']],true );
											
										}
							} //endforeach
							
							
							return $page_update;
}//end of UpdateLegacyContent()


public	function clean_post_html($content,$old_post_id,$oldurl) {//usehtml parser to clean html
		    $html = str_get_html($content);
			
			//$html=$this->special_html_rules_before($html);
		
			foreach($html->find('a') as $e)//chamge urls
			{
				$url = $e->href;
				$finalurl=$this->relative_link_test($url,$oldurl);
				$e->href = $finalurl;
			}
		
			foreach($this->remove_just_tags as $tag)//this go though saved tags to remove
			{
				foreach($html->find($tag) as $e)
				{
				$e->outertext = $e->innertext;
				}
			}
	
			foreach($this->atrr_to_remove as $atrr)//this go though saved attriubes to remove
			{
				foreach($html->find('['.$atrr.']') as $x)
				{
					$x->$atr = null;
				}
			}
			
			foreach($this->remove_entire_tag as $tagplus)//this go though saved attriubes to remove
			{
				foreach($html->find($tagplus) as $z)
				{
					$z->outertext = "";
				}
			}
	
			foreach($html->find('img') as $e)
			{
					$url = $e->src;
					$finalurl=$this->relative_link_test($url,$oldurl);
					
					$newurl = $this->media_upload_image_sp($finalurl, $old_post_id, $desc = null);
					
					$e->src = $newurl;
			}
	
		
			
			$ret = $html->save();
		
			// clean up memory
			$html->clear();
			unset($html);
		
			return $ret;
}
		
		
		
		
///post image handling////////////////////////

		///image uploading
private function media_upload_image_sp($file, $old_post_id, $desc = null) {
			if (!empty($file) ) {
				// Download file to temp location
				$tmp = download_url( $file );
		
				// Set variables for storage
				// fix file filename for query strings
				preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches);
				$file_array['name'] = basename($matches[0]);
				$file_array['name'] = str_replace('%20', '-', $file_array['name']);
				$file_array['tmp_name'] = $tmp;
		
				// If error storing temporarily, unlink
				if ( is_wp_error( $tmp ) ) {
					@unlink($file_array['tmp_name']);
					$file_array['tmp_name'] = '';
				}
				
				
				//testing already upload check
				global $wpdb;
				$image_src = $wp_upload_dir['baseurl'] . '/' . _wp_relative_upload_path($file_array['name']);
				//$query = "SELECT * FROM {$wpdb->posts} WHERE guid='$image_src'";
				$name = pathinfo($file_array['name']);
				$name = $name['filename'];
				
				$query = "SELECT `ID` FROM {$wpdb->posts} WHERE `post_title` = '$name' AND `post_type` = 'attachment'";
				$testid = $wpdb->get_var($query);
				echo "match ".$name . " - id: ". $testid ."</br>";
								
				$id = media_handle_sideload( $file_array, $old_post_id, $desc);
				// do the validation and storage stuff
				// If error storing permanently, unlink
				if ( is_wp_error($id) ) {
					@unlink($file_array['tmp_name']);
					 $error_string = $id->get_error_message();
		   			 echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
				}
		
				return $src = wp_get_attachment_url( $id );
			}
		}	

///realtive linkhandling////////////////////////
private function relative_link_test($url,$oldurl) {
					$urltest = parse_url($url);// test to see if found image link is realtive
					if($urltest['host'] == "" || $urltest['host'] == NULL)//if this trips is relatve links
					{
						$getoldhost = parse_url($oldurl);//grabs host from old url in legcay page sheet
						$fullurl = $getoldhost['scheme']."://".$getoldhost['host'] ."/". $url;//combinez old host and relative link for full url
					}
								
					else
					{
						$fullurl = $url;//if already full just asgin it here
					}
					
					return $fullurl;
			}//end relative link fix 
}//end of class


?>