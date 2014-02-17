##Wordpress--Excel-Site-Importer-Plugin##
Contributors: By Sam Langberg and Mohammad Usama Masood
Tags:Excel, Converter, Import , Html import
Requires at least: 3.1
Tested up to: 3.6
Stable tag: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html



##Project Summary##
This plugin generates Wordpress pages based off of rows in an uploaded excel docs, each page inherits images, html content, images and meta info based the contents column in a row. 
This plugin is still in early beta, it works but not at 100% and some functions might not work. This plugin was also developed with a set of preexisting requirements which means many of the options and functions are hard coded. The next version will work to my option more dynamic and fix all functionality  

**Page Data This Plugin Imports**
- Wordpress Page Title
- Page Content
- Parent Page
- Page H1
- Image Media Attachments 
- Page's Old Url
- SEO Page Title - Requires [Yoast](https://github.com/Yoast/wordpress-seo/) 
- SEO Meta Description - Requires [Yoast](https://github.com/Yoast/wordpress-seo/) 
- Custom Permalink - Requires [Custom Permalinks](http://wordpress.org/plugins/custom-permalinks/) 

**Post Data This Plugin Imports**
- Wordpress Post Title
- Post Content
- Post Media Attachments
- Post Author
- Post Categories 
- Post Date

##Required Excel Document  Structure##
This plug requires a excel 2007 (.xlx) file with a specific set of data in three separate worksheets. The name or order of the worksheets and column do not matter as those are set in the plugin options. Worksheets are listed in the order they are processed 
**URL MAP WORKSHEET:**
| Old Url       | Page H1 |  Page H1 | Page Content | Parent Page URL | Page Old Url |
| ------------- | ------------- | ------------- |------------- |------------- |
| Full Url  | The Pages h1 which will be added to the top of the content | This is either all the html o go into the content or a google doc link | This must match the url in the url map | This url will only be be saved as meta info |

**PAGE WORKSHEET:**
| Wordpress Page Title      | New URL |
| ------------- | ------------- |
| The title that will show in the page list  | Full Url  |

##Content Import Summary##
This plugin uses [PHP Simple HTML DOM Parser](http://simplehtmldom.sourceforge.net/) to parse html in a specified content column and will perform of number of alterations to the content before import. The plugin also will have the ability to import content from a google doc after the user logs in but this functionality is not working yet.

**The plugin's content alterations include:**
- It will rewrite old urls even relative ones found with the ```old_url``` option with the ones found in the  ```new_url``` option
- It will import all old live images as media attachments, attach them to that post and replace the old image url with the new attachment url
- It will strip all attributes in the ```$atrr_to_remove``` array found in the [post_modification class](https://github.com/slangberg/Wordpress--Excel-Site-Importer-Plugin/blob/master/post_modification.php)
- It will remove the tags but not content of the tags in the ```$remove_just_tags``` array found in the [post_modification class](https://github.com/slangberg/Wordpress--Excel-Site-Importer-Plugin/blob/master/post_modification.php)
- It will remove the tag and content of the tags in the ```$remove_entire_tag``` array found in the [post_modification class](https://github.com/slangberg/Wordpress--Excel-Site-Importer-Plugin/blob/master/post_modification.php)

