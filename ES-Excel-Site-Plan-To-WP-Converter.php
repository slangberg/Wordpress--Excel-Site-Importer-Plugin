<?php
/**
 * Plugin Name: EverSpark Excel Site Plan To WP Converter
 * Plugin URI: 
 * Description: This WordPress plugin generates pages with proper meta info and url from an Everspark Excel Site Plan.
 * Version: 0.2
 * Author: By Sam Langberg and Mohammad Usama Masood
 * Author URI: http://samlangberg.com
 * License: GPL2
 */

/**
* Setting Session		  
 */				
add_action('init','register_session');
		function register_session(){		
			session_start();
			wp_enqueue_script("jquery");
		}

		
/**
 * Class for WP Post Generator
 */
//error_reporting(E_ALL);
class WP_Post_Generator {
	protected $prefix = 'siteplan_to_wp_converter';
	public $site_plan_options ='';
	public $legacy_page_options ='';
	public $legacy_post_options ='';
	public $url_map_options ='';
	public $new_url_list ='';
	public $old_url_list ='';
	public $postcreator ='';
	public $excelhandler ='';
	public $postmodifer ='';
	

	/**
	 * Initializes WP_Dummy_Post_Generator
	 */
	public function __construct() {
		add_action('admin_menu', array(&$this, 'admin_register_submenu'));
		//add_action("wp_ajax_{$this->prefix}ajax", array(&$this, 'insert_post_ajax'));
		include( plugin_dir_path( __FILE__ ) . 'excel_handling.php');
		include( plugin_dir_path( __FILE__ ) . 'Classes/PHPExcel.php');
		include( plugin_dir_path( __FILE__ ) . 'Classes/simple_html_dom.php');
		include( plugin_dir_path( __FILE__ ) . 'post_modification.php');
		include( plugin_dir_path( __FILE__ ) . 'post_creation.php');
		include( plugin_dir_path( __FILE__ ) . 'drive_handler.php');
	}
	
	
	private function load_options(){//loads options
		$this->site_plan_options = get_option('site_plan');
		$this->legacy_page_options = get_option('legacy_page');
		$this->legacy_post_options = get_option('legacy_post');
		$this->url_map_options  = get_option('url_map');
		$this->new_url_list =  get_option('new_url'); 
		$this->old_url_list =  get_option('old_url');
		$this->postcreator = new PostCreation();//insatiantes child classes to run key functions
		$this->excelhandler = new ExcelHandler();//insatiantes child classes to run key functions
		$this->postmodifer = new PostModification();//insatiantes child classes to run key functions
	}



	public function admin_register_submenu() {
		$hook = add_submenu_page('tools.php', 'Siteplan to WP converter', 'Siteplan to WP converter', 'manage_options', "{$this->prefix}.php", array(&$this, 'admin_panel'));
	}
	

	/**
	 * Shows the WP Post Generator Panel
	 */
	public function admin_panel() {
		$this->load_options(); //loads options///gets options from db and saves them as public varible in class
	
		
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		if (isset($_POST['_wpnonce'])) {
			if (!check_admin_referer($this->prefix."form")) { ?>
				<div class="error"><p>Your session has timed out. Please try again.</p></div><?php
			} else {
				$operation = $this->process_action(@$_POST[$this->prefix."action"]);
				if (is_wp_error($operation)) { ?>
					<div class="error"><p><strong>An error occurred: <?php echo $operation->get_error_message(); ?></strong></p></div><?php
				} else {  
					if ($_SESSION['pp_generate'] === true){
						?>
						<div class="updated"><p><strong><?php echo "Pages Created: ".$_SESSION['page'] ."</br> Content Added To ". $_SESSION['page_update'].' Pages</br>'.$_SESSION['post']." post created"; ?></strong></p></div>
						<?php $_SESSION['page']="";	$_SESSION['post']=""; }else{?>
						<div class="updated"><p><strong><?php echo (is_string($operation) ? $operation : 'Option Update Successful.'); ?></strong></p></div>
					<?php	}
				}
			}
		}
		
		
		
	

		?>
        <div class="overlay">
        	<div class="loader">
            </div>
        </div>		
		<div class="wrap"><div id="icon-tools" class="icon32"><br /></div>
		<h2>Siteplan to WP converter</h2>
		<p>The tools on this page can be used to generate page data for WordPress loop.</p>
        <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
		
       	<!-- main content -->
        <div id="post-body-content">
        	<div class="meta-box-sortables ui-sortable">
         
		 <?php if ($_SESSION['pp_generate'] === true){ ?>
		 <div class="postbox">
        	<h3><span>Site Plan Option</span></h3>
        	<div class="inside" style="width: 840px; height: 300px; overflow: scroll;">
					<?php
                    $this->postcreator->PostShow(); 
					$_SESSION['pp_generate'] = false;?>
            </div><!-- .inside -->  
         </div> <!-- .postbox -->
		 <?php } ?>
          	
         <form method="post" name="<?php echo $this->prefix."form"; ?>" enctype="multipart/form-data">
		<?php wp_nonce_field("{$this->prefix}form"); ?>	
        
 		<fieldset id="<?php echo $this->prefix."excel_option"; ?>">
        <div class="postbox">
        	<h3><span>Site Plan Option</span></h3>
        	<div class="inside">
            <table id="siteplanmap" class="widefat">
				<tbody>
                <tr>
                    <td class="row-title"><label for="tablecell">Site Plan Worksheet Name:</label></td>
                    <td><input type="text" name="<?php echo $this->prefix."_sitePlan"; ?>" value="<?php echo $this->site_plan_options['es_sitePlan'];?>" size="80"/></td>
                </tr>
                <tr>
                    <td class="row-title"><label for="tablecell">SitePlan Slug:</label></td>
                    <td><input type="text" name="<?php echo $this->prefix."_SitePlan_Title"; ?>" value="<?php echo $this->site_plan_options['es_SitePlan_Title']; ?>" size="1" /></td>
                </tr>
                <tr>
                    <td class="row-title"><label for="tablecell">SitePlan SEO Title:</label></td>
                    <td><input type="text" name="<?php echo $this->prefix."_SitePlan_Seotitle"; ?>" value="<?php echo $this->site_plan_options['es_SitePlan_Seotitle']; ?>" size="1"/></td>
                </tr>
                <tr>
                    <td class="row-title"><label for="tablecell">SitePlan Description:</label></td>
                    <td><input type="text" name="<?php echo $this->prefix."_SitePlan_metadesc"; ?>" value="<?php echo $this->site_plan_options['es_SitePlan_metadesc']; ?>" size="1"/></td>
                </tr>
                <tr>
                    <td class="row-title"><label for="tablecell">SitePlan URL/Adress:</label></td>
                    <td><input type="text" name="<?php echo $this->prefix."_SitePlan_URL"; ?>" value="<?php echo $this->site_plan_options['es_SitePlan_URL']; ?>" size="1"/> </td>
                </tr>
                
                <tr>
                    <td class="row-title"><label for="tablecell">New Content URL/Adress:</label></td>
                    <td><input type="text" name="<?php echo $this->prefix."_SitePlan_ContentURL"; ?>" value="<?php echo $this->site_plan_options['es_SitePlan_ContentURL']; ?>" size="1"/> </td>
                </tr>
                
                </tbody>
                </table>
           <p><button type="submit" class="button-secondary" name="<?php echo $this->prefix."action"; ?>" value="es_option">Update Options</button></p>
       		</div><!-- .inside -->
        </div> <!-- .postbox -->
        
        <div class="postbox">
            <h3><span>Page Content Option</span></h3>
            <div class="inside">
            <table id="pagecontent" class="widefat">
            <tbody>		 
             <tr><input type="hidden" name="<?php echo $this->prefix."_Pagetype"; ?>" value="page" />
                <td class="row-title"><label for="tablecell">Leg Page Worksheet Name:</label></td>
                <td><input type="text" name="<?php echo $this->prefix."_legacyPage"; ?>" value="<?php echo $this->legacy_page_options['es_legacyPage']; ?>" size="80"/></td>
             </tr>
             <tr>
                <td class="row-title"><label for="tablecell">Page Slug:</label></td>
                <td><input type="text" name="<?php echo $this->prefix."_PageTitle"; ?>" value="<?php echo $this->legacy_page_options['es_PageTitle']; ?>" size="1" /></td>
             </tr>
              <tr>
                <td class="row-title"><label for="tablecell">Page H1:</label></td>
                <td><input type="text" name="<?php echo $this->prefix."_PageH1"; ?>" value="<?php echo $this->legacy_page_options['es_PageH1']; ?>" size="1" /></td>
             </tr>
             <tr>
                <td class="row-title"><label for="tablecell">Page Content:</label></td>
                <td><input type="text" name="<?php echo $this->prefix."_PageContent"; ?>" value="<?php echo $this->legacy_page_options['es_PageContent']; ?>" size="1"/> </td>
            </tr>
             <tr>
                    <td class="row-title"><label for="tablecell">Page Parent URL:</label></td>
                    <td><input type="text" name="<?php echo $this->prefix."_parentPage"; ?>" value="<?php echo $this->legacy_page_options['es_parentPage']; ?>" size="1"/> </td>
                </tr>
            <tr>
                <td class="row-title"><label for="tablecell">Page Old Url:</label></td>
                <td><input type="text" name="<?php echo $this->prefix."_PageOldUrl"; ?>" value="<?php echo $this->legacy_page_options['es_PageOldUrl']; ?>" size="1"/> </td>
            </tr>
            </tbody>
            </table>
           <p><button type="submit" class="button-secondary" name="<?php echo $this->prefix."action"; ?>" value="es_option">Update Options</button></p>
			</div><!-- .inside -->
         </div> <!-- .postbox -->
        <div class="postbox">
    		<h3><span>Post Content Option</span></h3>
            <div class="inside">
    		<table id="postcontent" class="widefat">
           	<tbody>
            		  <input type="hidden" name="<?php echo $this->prefix."_Posttype"; ?>" value="post" />
             <tr>
                  <td class="row-title"><label for="tablecell">Leg Post Worksheet Name:</label></td>
                  <td><input type="text" name="<?php echo $this->prefix."_legacyPost"; ?>" value="<?php echo $this->legacy_post_options['es_legacyPost']; ?>" size="80"/></td>
             </tr>
             <tr>
                <td class="row-title"><label for="tablecell">Post Title:</label></td>
                <td><input type="text" name="<?php echo $this->prefix."_PostTitle"; ?>" value="<?php echo $this->legacy_post_options['es_PostTitle']; ?>" size="1" /></td>
             </tr> 
            <tr>
                <td class="row-title"><label for="tablecell">Post Date:</label></td>
                <td><input type="text" name="<?php echo $this->prefix."_PostDate"; ?>" value="<?php echo $this->legacy_post_options['es_PostDate']; ?>" size="1"/></td>
			</tr>
             <tr>
                <td class="row-title"><label for="tablecell">Post Cotent:</label></td>
                <td><input type="text" name="<?php echo $this->prefix."_PostContent"; ?>" value="<?php echo $this->legacy_post_options['es_PostContent']; ?>" size="1"/> </td>
            </tr>
            <tr>
                 <td class="row-title"><label for="tablecell">Post Category:</label></td>
                 <td><input type="text" name="<?php echo $this->prefix."_PostCategory"; ?>" value="<?php echo $this->legacy_post_options['es_PostCategory']; ?>" size="1"/></td>
             </tr>
             <tr>
                 <td class="row-title"><label for="tablecell">Post Author:</label></td>
                 <td><input type="text" name="<?php echo $this->prefix."_PostAuthor"; ?>" value="<?php echo $this->legacy_post_options['es_PostAuthor']; ?>" size="1"/></td>
             </tr>
            <tr>
                <td class="row-title"><label for="tablecell">Post Old Url:</label></td>
                <td><input type="text" name="<?php echo $this->prefix."_PostOldUrl"; ?>" value="<?php echo $this->legacy_post_options['es_PostOldUrl']; ?>" size="1"/> </td>
            </tr>
            </tbody>
            </table>
           <p><button type="submit" class="button-secondary" name="<?php echo $this->prefix."action"; ?>" value="es_option">Update Options</button></p>
           	</div><!-- .inside -->
        </div> <!-- .postbox -->
        <div class="postbox">
           	<h3><span>URL Mapping Option</span></h3>
           	<div class="inside">
            <table id="urlmap" class="widefat">
            <tbody>
            <tr>
            	<td class="row-title"><label for="tablecell">URL Map Worksheet Name: </label></td>
            	<td><input type="text" name="<?php echo $this->prefix."_urlMap"; ?>" value="<?php echo $this->url_map_options['es_urlMap'];  ?>" size="80"/></td>
            </tr>
            <tr>
            	<td class="row-title"><label for="tablecell">Old URL:</label></td>
            	<td><input type="text" name="<?php echo $this->prefix."_Oldurl"; ?>" value="<?php echo $this->url_map_options['es_Oldurl'];  ?>" size="1"/></td>
            </tr>
            <tr>
            	<td class="row-title"><label for="tablecell">New URL:</label></td>
            	<td><input type="text" name="<?php echo $this->prefix."_Newurl"; ?>" value="<?php echo $this->url_map_options['es_Newurl'];  ?>" size="1"/></td>
            </tr>
      		</tbody>
        </table>
       	   <p><button type="submit" class="button-secondary" name="<?php echo $this->prefix."action"; ?>" value="es_option">Update Options</button></p>
        	</div> <!-- .inside -->
        </div> <!-- .postbox -->
        
        </fieldset>
		
         <div class="postbox">
           	<h3><span>Link Drive Account</span></h3>
           	<div class="inside">
            <?php if(!$_COOKIE['access_token']) { ?>
			<p><button type="button" class="button-primary" name="<?php echo $this->prefix."action"; ?>" id="drivelogin" value="login_drive"  onclick="login();">Login Google Drive</button></p>
            <?php }elseif($_COOKIE['access_token'] && !get_option('driveclient')) {?>
           <p><button type="submit" class="button-secondary" name="<?php echo $this->prefix."action"; ?>" value="link_drive">Link Google Drive</button></p>
           <?php } else { ?>
           <p><button type="submit" class="button-secondary" name="<?php echo $this->prefix."action"; ?>" value="logout_drive">Logout Google Drive</button></p>
           <? }?>
        	</div> <!-- .inside -->
            <script>
			function login()
			{
					var test = window.open("http://esidev1.info/dev/googleapi/index.php", "windowname1", 'width=900, height=600');
			}
			
		
			</script>
        </div> <!-- .postbox -->
		
        
         <div class="postbox">
           	<h3><span>Create WP Post After Setting Columns</span></h3>
           	<div class="inside">
           <!-- <table class="widefat">
		<tr valign="top">
			<th scope="row">Create WP Post</th>
			<td>-->
			<fieldset id="<?php echo $this->prefix."ajax_insert_post"; ?>">
				
                <!--<input type="file" name="<?php echo $this->prefix . "_import_file"; ?>" /> -->
				<p><button type="submit" class="button-primary" name="<?php echo $this->prefix."action"; ?>" value="generate_post">Generate Posts</button></p> 
                <span id="<?php echo $this->prefix."ajax_response"; ?>"></span>

			</fieldset>
			<!--</td>
		</tr>
		</table>-->
       	  
        	</div> <!-- .inside -->
        </div> <!-- .postbox -->
		
		
        			
         	</form>		 

        	</div> <!-- .meta-box-sortables .ui-sortable -->
        </div><!-- post-body-content -->
       
		<br class="clear">
		</div> <!-- #poststuff -->
        </div><!-- .wrap -->
		<?php
	}
	
	 /* /// on form submit, ethier saves settings or runs loops
	 * 
	 * @param $action is the thing to do (set_setting, delete_post, etc.)
	 */
	protected function process_action($action) {//coms from form submit check with usama
		switch ($action) {
		case "link_drive":
			$this->link_drive();
			break;
		case "es_option":
			$this->excelhandler->excel_option();// calls the public method for saving options
			break;
		case "generate_post":
			$this->Run_Post_Loops(); // calls the public method for creating posts
			break;
		default:
			return false;
		}
	}
	
	
	/**
	 * Helper function that inserts posts into batches of categories
	 */
	public function Run_Post_Loops() {
		
		$post_data = $this->excelhandler->import_file();// uses the class declared in  init function
		
		if ( is_wp_error( $post_data ) ) {
		   $error_string = $post_data->get_error_message();
		   echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
		}


						
	if ( is_wp_error($post_data) === false)
			{
					if($this->site_plan_options['es_sitePlan'] != "" || $this->site_plan_options['es_sitePlan'] != NULL){
						$page = $this->postcreator->CreatePages($post_data);// run first page creation loop in post_creation
					}
						
					if($this->legacy_page_options['es_legacyPage'] != "" || $this->legacy_page_options['es_legacyPage'] != NULL){
						$page_update = $this->postmodifer->UpdateLegacyContent($post_data);
					}
			
				  // Legacy Post Section
				  if($this->legacy_post_options['es_legacyPost'] != "" || $this->legacy_post_options['es_legacyPost'] != NULL){
						$post = $this->postcreator->CreateBlogPosts($post_data);
					  }
						$_SESSION['pp_generate'] = true;
						$_SESSION['page_update'] = $page_update;
						$_SESSION['page'] = $page;
						$_SESSION['post'] = $post;
						//exit;
					
		 }
		 else 
		 {
			 //If file empty then show error message
			 return new WP_Error('fileupload', __("Post generation requires Excel File. Please make sure file uploaded correctly."));
		 }
				
	}//run post loops
	
	public function link_drive() 
	{
		$this->dhandler = new DriveHandler();//insatiantes child classes to run key functions
		$error = $this->dhandler->setaccesstoken();
		$this->dhandler->gethtmllink($url);
	}


}//end of class

if (is_admin()) {
	ini_set('max_execution_time','2400');
	new WP_Post_Generator();
} ?>
