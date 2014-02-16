<?php

	class ExcelHandler
	{
////////execl file handling 
	 /////Saving Option for Excel File Columns
	protected $prefix = 'siteplan_to_wp_converter';
	public $site_plan_options ='';
	public $legacy_page_options ='';
	public $legacy_post_options ='';
	public $url_map_options ='';
	public $new_url_list ='';
	public $old_url_list =''; 
	 
	/*
	  @arg $form_option, $title_option 
	 */
	private function option($title_option,$form_option){
	
		if (get_option($title_option) === false){
				add_option($title_option , $form_option );
					}
			else{update_option($title_option , $form_option ); }
		}
	
	private function load_options(){//loadoptions
		$this->site_plan_options = get_option('site_plan');
		$this->legacy_page_options = get_option('legacy_page');
		$this->legacy_post_options = get_option('legacy_post');
		$this->url_map_options  = get_option('url_map');
	}
	
	public function excel_option() {//grabs data from admin panel
		
		//////////////////////////////
		$siteplan_array = array(
		'es_sitePlan' => $_POST[$this->prefix . '_sitePlan'],
		'es_SitePlan_Title' => $_POST[$this->prefix . '_SitePlan_Title'],
		'es_SitePlan_Seotitle' => $_POST[$this->prefix . '_SitePlan_Seotitle'],
		'es_SitePlan_metadesc' => $_POST[$this->prefix . '_SitePlan_metadesc'],
		'es_SitePlan_URL' => $_POST[$this->prefix . '_SitePlan_URL'],
		'es_SitePlan_ContentURL' => $_POST[$this->prefix . '_SitePlan_ContentURL']
		);
		
		///////////////////////////////
		$legacyPage_array = array(
		'es_legacyPage' => $_POST[$this->prefix . '_legacyPage'],
		'es_Pagetype' => $_POST[$this->prefix . '_Pagetype'],
		'es_PageTitle' => $_POST[$this->prefix . '_PageTitle'],
		'es_PageH1' => $_POST[$this->prefix . '_PageH1'],
		'es_PageContent' => $_POST[$this->prefix . '_PageContent'],
		'es_parentPage' => $_POST[$this->prefix . '_parentPage'],
		'es_PageOldUrl' => $_POST[$this->prefix . '_PageOldUrl'],
		);
		
		////////////////////////////////
		$legacyPost_array = array(
		'es_legacyPost' => $_POST[$this->prefix . '_legacyPost'],
		'es_Posttype' => $_POST[$this->prefix . '_Posttype'],
		'es_PostTitle' => $_POST[$this->prefix . '_PostTitle'],
		'es_PostDate' => $_POST[$this->prefix . '_PostDate'],
		'es_PostContent' => $_POST[$this->prefix . '_PostContent'],
		'es_PostCategory' => $_POST[$this->prefix . '_PostCategory'],
		'es_PostAuthor' => $_POST[$this->prefix . '_PostAuthor'],
		'es_PostOldUrl' => $_POST[$this->prefix . '_PostOldUrl'],
		);
		////////////////////////////////
		$urlMap_array = array(
		'es_urlMap' => $_POST[$this->prefix . '_urlMap'],
		'es_Oldurl' => $_POST[$this->prefix . '_Oldurl'],
		'es_Newurl' => $_POST[$this->prefix . '_Newurl'],
		);
		
		//////////////////////////////////
		$this->option('site_plan',$siteplan_array);
		$this->site_plan_options=$siteplan_array;// set the public variblef rom the parent class to be used by other classes in the plug in 
		$this->option('legacy_page',$legacyPage_array);
		$this->legacy_page_options=$legacyPage_array;// set the public variblef rom the parent class to be used by other classes in the plug in 
		$this->option('legacy_post',$legacyPost_array);
		$this->legacy_post_options=$legacyPost_array;
		$this->option('url_map',$urlMap_array);
		$this->url_map_options=$urlMap_array;// set the public variblef rom the parent class to be used by other classes in the plug in 
	}
	
	/**
	 * Loads Excel File
	 *
	 * @arg $filepath is the path 
	 */
	public function import_file() {
		
		
		$this->load_options(); //loads options
		
		// Hardcode path for file
		$file_path = scandir( plugin_dir_path( __FILE__ ) .'upload');
		if(count($file_path) <= 3){
			$file = plugin_dir_path( __FILE__ ) . "upload/".$file_path[2];
			$file_extension = pathinfo($file);
				if ($file_extension['extension'] != "xlsx")
				{
					return new WP_Error('Extension Error', __('It looks like the file upload failed due file extension. Please try again.'));
				}
		}else{
			return new WP_Error('File Process Failed', __('It looks like the file process failed due more then one file. Please try again.'));
		}
		

		
			  
			  $sheetData = array();
		///////////////////////////////////////
		// Convert the Excel File to array 
		$objReader = new PHPExcel_Reader_Excel2007();
	
		// For Active Sheet	
		$objPHPExcel = $objReader->load($file);	
		if($this->site_plan_options['es_sitePlan'] != "" || $this->site_plan_options['es_sitePlan'] != NULL){
			$siteplan_sheet = $objPHPExcel->getSheetByName($this->site_plan_options['es_sitePlan'])->toArray(null,true,true,true);
			}else{ $siteplan_sheet="";}
		if($this->legacy_page_options['es_legacyPage'] != "" || $this->legacy_page_options['es_legacyPage'] != NULL){   
			$page_sheet = $objPHPExcel->getSheetByName($this->legacy_page_options['es_legacyPage'])->toArray(null,true,true,true); 
			}else{ $page_sheet="";}
		if($this->legacy_post_options['es_legacyPost'] != "" || $this->legacy_post_options['es_legacyPost'] != NULL){ 
			$post_sheet = $objPHPExcel->getSheetByName($this->legacy_post_options['es_legacyPost'])->toArray(null,true,true,true);  
			}else{ $post_sheet="";}
		if($this->url_map_options['es_urlMap'] != "" || $this->url_map_options['es_urlMap'] != NULL){ 
			$url_sheet = $objPHPExcel->getSheetByName($this->url_map_options['es_urlMap'])->toArray(null,true,true,true);
			}else{ $url_sheet="";}
			
			
		$sheetData = array(
		'siteplan_sheet'=>$siteplan_sheet,
		'page_sheet' =>$page_sheet,
		'post_sheet' =>$post_sheet,
		);

		
		if ($this->url_map_options['es_urlMap'] != "" || $this->url_map_options['es_urlMap'] != NULL){
			$old = array();
			$new = array();
			
			foreach ($url_sheet as $value){
					$old[] = $value[$this->url_map_options['es_Oldurl']];
					$new[] = $value[$this->url_map_options['es_Newurl']];
				}
			
				array_shift($old);array_shift($new);
				if (get_option('old_url') === false || get_option('new_url') === false){
						add_option('old_url' , $old );
						add_option('new_url' , $new );
					}
					else{
						update_option('old_url' ,$old );
						update_option('new_url' ,$new );
					}
		}
		return $sheetData;
		
	}	
	}
?>