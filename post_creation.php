<?php
class PostCreation
{
	
	protected $prefix = 'siteplan_to_wp_converter';
///post cretion funbctions 

private function load_options($client){
		$this->site_plan_options = get_option('site_plan');
		$this->legacy_post_options = get_option('legacy_post');
		$this->dhandler = new DriveHandler();//insatiantes child classes to run key functions
}


/// 301 redrect loop/////////////////////////////////////////////////////////
	
public function PostShow(){
			$posts = get_posts( array ( 'post_type' => array(  'post','page' ) , 'nopaging' => true ) );
			echo htmlentities("<IfModule mod_rewrite.c>")."<br>";
			echo htmlentities("RewriteEngine On")."<br>";
			foreach ( $posts as $post ): setup_postdata( $post );
					//the_author_meta();
				  $oldurl = get_post_meta( $post->ID, 'OldURL', true  );
					  $url = parse_url( $oldurl);
					  $url_Slug = ltrim($url['path'],'/');
				  $new = get_permalink( $post->ID );
				  echo "RewriteRule ^".$url_Slug."$ ".$new."</br>"; 
			 
			 endforeach; 
			 echo htmlentities("</IfModule>");wp_reset_postdata();
			}
	
/////////////////////////////////////////////////////////////////////////

public function CreatePages($post_data)
{
	/////------------------------------------------------------------- first loop page creation --------------------------------------------////
		
	$siteplan_array = $post_data['siteplan_sheet'];
	
	$this->load_options();//load option for acess 



	$this->dhandler->setaccesstoken();
	// values for end of loop creation and update counbts
	$page=0;
				
				array_shift($siteplan_array);//skips the first row for colum headings
				
				foreach ($siteplan_array as $value) {
				
					if($value[$this->site_plan_options['es_SitePlan_Title']] == "" || $value[$this->site_plan_options['es_SitePlan_Title']] == NULL)
					{
						continue;//skips if no slug
					}
					
					if($value[$this->site_plan_options['es_SitePlan_ContentURL']] != "" || $value[$this->site_plan_options['es_SitePlan_ContentURL']] != NULL)
					{
						$contenturl = $value[$this->site_plan_options['es_SitePlan_ContentURL']];
						echo $contenturl;
						//$content = $this->dhandler->getcleanhtml($contenturl);
					}
					
						 $added_page = get_page_by_title($value[$this->site_plan_options['es_SitePlan_Title']]);// cehcks to see if a page is alrady there
						 
						if ($added_page===NULL){
							 
								$my_post = array(
								  'post_title'    => $value[$this->site_plan_options['es_SitePlan_Title'] ],
								  'post_status'   => 'publish',
								  'post_author'   => 1,//--------------------------<<< changed this to logged in user
								  'post_type' 	  => 'page',
								  //'post_content'  => $content
								);
								
								
								
									// Insert the post into the database
									$post_id = wp_insert_post($my_post);
									
									if ($value[$this->site_plan_options['es_SitePlan_Seotitle']] !=""){//update the Seotitle
										  if (!update_post_meta ($post_id, '_yoast_wpseo_title', $value[$this->site_plan_options['es_SitePlan_Seotitle'] ] ) )//update the post meta
											add_post_meta( $post_id, '_yoast_wpseo_title', $value[$this->site_plan_options['es_SitePlan_Seotitle'] ],true ); //add post meta
										  }
										  
									if ( $value[$this->site_plan_options['es_SitePlan_metadesc'] ]!=""){//update the metadesc
										if (!update_post_meta ($post_id, '_yoast_wpseo_metadesc', $value[$this->site_plan_options['es_SitePlan_metadesc']] ) ) //update the post meta
											add_post_meta( $post_id, '_yoast_wpseo_metadesc', $value[$this->site_plan_options['es_SitePlan_metadesc']],true );	//update the post meta
										}
										
									if ( $value[$this->site_plan_options['es_SitePlan_URL']] !=""){//update the URL
										if ( ! update_post_meta ($post_id, 'custom_permalink', $url_Slug ) ) //update the siteplan
										$stripedurl = parse_url($value[$this->site_plan_options['es_SitePlan_URL']]);
 										$url_Slug = ltrim($url['path'],'/');/// strips the first slash off path grneated by php 
										add_post_meta( $post_id, 'custom_permalink', $url_Slug,true );	//update the siteplan
									}
									
							}//end addd page null
									
									
							else{
								
									if ( $value[$this->site_plan_options['es_SitePlan_Seotitle'] ] !=""){
										if ( ! update_post_meta ($added_page->ID, '_yoast_wpseo_title', $value[$this->site_plan_options['es_SitePlan_Seotitle'] ] ) ) 
										add_post_meta( $added_page->ID, '_yoast_wpseo_title', $value[$this->site_plan_options['es_SitePlan_Seotitle'] ],true );
									 
									}
									if ( $value[$this->site_plan_options['es_SitePlan_metadesc'] ]!=""){
										if ( ! update_post_meta ($added_page->ID, '_yoast_wpseo_metadesc', $value[$this->site_plan_options['es_SitePlan_metadesc']] ) ) 
										add_post_meta( $added_page->ID, '_yoast_wpseo_metadesc', $value[$this->site_plan_options['es_SitePlan_metadesc']],true );	
									}
									
									if ( $value[$this->site_plan_options['es_SitePlan_URL'] ] !=""){//update the URL
										if ( ! update_post_meta ($post_id, 'custom_permalink', $url_Slug ) ) //update the siteplan
										add_post_meta( $post_id, 'custom_permalink', $url_Slug,true );	//update the siteplan
									}
									
									$my_post = array(
									  'post_content'  => $content,
									);
									
									$added_page->ID = wp_update_post( $my_post );
							} ///end of added page not null
							
							$page++;
				}//end of for each 
				
				return $page;
}// end of CreatePages


public function CreateBlogPosts($post_data)
{
	echo"<b>CreateBlogPosts Start</b></br>";
	$postsheet_array = $post_data['post_sheet'];
	
	$this->load_options();//load option for acess 
	
	
	array_shift($postsheet_array);
	
	
	$post = 0;
	foreach ($postsheet_array as $value) { 
	if($value[ $this->legacy_post_options['es_PostTitle']] == "" || $value[ $this->legacy_post_options['es_PostTitle']] == NULL)
	{
		echo"skiped post create</br>";
		continue;//skips if no slug
	}
							
	
	$post++;
	$postdate;
	$category_id;
	$userID;

		 
			
		  if ($this->legacy_post_options['es_PostCategory'] !=""){//gets catgorey and their ads it or asgins it
				$category_id = get_cat_ID($value[$this->legacy_post_options['es_PostCategory']]);//test to see if cat exists
				if($category_id === 0){
					$my_cat = array('cat_name' => $value[ $this->legacy_post_options['es_PostCategory']], 'taxonomy' => 'category');
					$category_id = wp_insert_category($my_cat);
					}
		  }
		  
		  if ( $this->legacy_post_options['es_PostAuthor'] !=""){//gets post author and creates or assings wordpress user as post author
			  $value[$this->legacy_post_options['es_PostAuthor']] = str_replace(',','',$value[$this->legacy_post_options['es_PostAuthor']]);
			  $user = get_user_by( 'login', $value[ $this->legacy_post_options['es_PostAuthor']]);
			  $userID = $user->ID; 
			  if ($userID  ===  NULL)
			  {			
					$userID = wp_create_user($value[ $this->legacy_post_options['es_PostAuthor'] ], "abc", "abc@gmail.com" );
			  }
		  }
		  
		
		 if ( $this->legacy_post_options['es_PostDate'] !=""){//gets and formats post date
			  $date_string = $value[$this->legacy_post_options['es_PostDate'] ];
			  $date_string = str_replace('"','',$date_string);
			  $date_stamp = strtotime($date_string);
			  $postdate = date("Y-m-d H:i:s", $date_stamp);
		  }
		  
		  
		  $added_page = get_page_by_title($value[ $this->legacy_post_options['es_PostTitle']], 'OBJECT', 'post');//test to see if psot exists
		  
			if ($added_page == NULL){//if not post exists it adds it
			error_log("get to added page null -- > ");
				$my_post = array(
						  'post_title'    => $value[ $this->legacy_post_options['es_PostTitle'] ],
						  'post_content'  => $content,
						  'post_status'   => 'publish',
						  'post_category'	=>array( $category_id ),
						  'post_date'      => $postdate, 
						  'post_author'    => $userID,
						  'post_author'   => 1,//--------------------------<<< changed this to logged in user
						  'post_type' => 'post',
						);
						// Insert the post into the database
				  $post_id = wp_insert_post($my_post);
				 	error_log("interted post -- > ");
				 
				  if ($this->legacy_post_options['es_PostContent'] !=""){// use substring repalce to alter all urls at once
			 		$content=$this->clean_post($value,$post_id);// to add images a  post id is needed so the post needs to be created first 
					
					error_log("get past clean_post function -- > ");
					$postwithcontent =  array(//recreates post with images added
						  'ID'=> $post_id,
						  'post_content'  => $content
						);
						
					$newpost_id = wp_update_post($postwithcontent);
		 		  }

		}
		
		else{
			    $content=$this->clean_post($value,$added_page->ID);
			
				$my_post = array(
						  'ID'			  	=> $added_page->ID,
						  'post_title'    	=> $value[ $this->legacy_post_options['es_PostTitle'] ],
						  'post_content'  	=> $content,
						 // 'post_parent'   => $parent_postID,
						  'post_date'      	=> $postdate,
						  'post_author'    	=> $userID, 
						  'post_category'	=>$category_id,
						  'post_type' 		=> 'post',
						);
						// Insert the post into the database
				 $newpost_id = wp_update_post($my_post);
		}//end else update existing post
		
		
	   if ($this->legacy_post_options['es_PostContent'] !=""){// use substring repalce to alter all urls at once
			 
		 }
				 
			 if ( ! update_post_meta ($newpost_id, 'OldURL', $value[ $this->legacy_post_options['es_PostOldUrl'] ] )) 
				add_post_meta( $newpost_id, 'OldURL', $value[ $this->legacy_post_options['es_PostOldUrl'] ],true );
		
				$post++;
				
			} //endforeach
			
			return $post;
	}//CreateBlogPosts
		
		
private function clean_post($value,$post_id)//clean psot content
	{
			$postmodifer = new PostModification();//insatiantes child classes to run key functions
			error_log("get to clean_post function -- > ");
			$content  = $value[$this->legacy_post_options['es_PostContent'] ]; 
		
			 //////thesea re used for clean content can be turn to public vars later//////////	
			$oldurl = $value[$this->legacy_post_options['es_PostOldUrl']];
			
			$cleanedcontent = $postmodifer->clean_post_html($content,$post_id,$oldurl);//run post modifer in post modfer class
			
			$content =  str_replace($this->old_url_list, $this->new_url_list, $cleanedcontent);//replace the old urls
			
			return $content;
	}//end clean post
}
?>