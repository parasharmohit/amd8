<?php
namespace Drupal\custom_data_migration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\am_registration\Controller\CreateLoginLinkController;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use Drupal\comment\Entity\Comment;

class CustomDataMigrationController {

  public function strpos_r($haystack, $needle)
{
    if(strlen($needle) > strlen($haystack))
        trigger_error(sprintf("%s: length of argument 2 must be <= argument 1", _FUNCTION_), E_USER_WARNING);

    $seeks = array();
    while($seek = strrpos($haystack, $needle))
    {
        array_push($seeks, $seek);
        $haystack = substr($haystack, 0, $seek);
    }
    return $seeks;
}
//===============================================================================================================================
//===============================================================================================================================
	
public function migration() {
    $domain_name = 'http://devnt-americad8.pantheonsite.io';
    $listing = '<ul>
<li><a href="'.$domain_name.'/migration/user-data"><p><b>User Data Migration</b></a></p></li>
<li><a href="'.$domain_name.'/migration/taxonomy-data"><p><b>Taxonomy Data Migration</b></a></p></li>
<li><b>Content Type - Node Migration</b><ul>
<li><a href="'.$domain_name.'/migration/photo-gallery">Photo Gallery</a></li>
<li><a href="'.$domain_name.'/migration/node-profile">Profile</a></li>
<li><a href="'.$domain_name.'/migration/node-basicpage">Basic Page</a></li>
<li><a href="'.$domain_name.'/migration/node-book">Book</a></li>
<li><a href="'.$domain_name.'/migration/node-issue">Issue</a></li>
<li><a href="'.$domain_name.'/migration/node-podcast">Podcast</a></li>
<li><a href="'.$domain_name.'/migration/node-video">Video</a></li>
<li><a href="'.$domain_name.'/migration/node-article">Article</a>
	<ul><li><a href="'.$domain_name.'/migration/node-blog-post-article">Blog Post</a></li></ul>
</li>
<li><a href="'.$domain_name.'/migration/node-bookreview">Book Review</a></li>
<li><a href="'.$domain_name.'/migration/node-theword">The Word</a></li>
</ul></li>

<li><p><a href="'.$domain_name.'/migration/comments">Comments</a></p></li>
</ul>';

	return array('#title' => 'AM Data Migration','#markup' => 'Please make sure you are using this module first time.<br/>'.$listing,);
}




/**
===============================================================================================================================
===============================================================================================================================


				**************************** Migration - User Roles ****************************


===============================================================================================================================
===============================================================================================================================
**/
public function user_migration() {
      $query = \Drupal::database()->select('z_old_d7_users_data', 'zu');
      $query->fields('zu', ['uid', 'name', 'pass', 'mail', 'created', 'access', 'login', 'status', 'timezone', 'field_account_type_value', 'field_salutation_value', 'field_first_name_value', 'field_middle_initial_value', 'field_last_name_value', 'field_profile_suffix_value', 'field_profile_title_value', 'field_religious_order_value', 'field_company_value', 'field_user_addressfield_address1', 'field_user_addressfield_address2', 'field_user_addressfield_city', 'field_user_addressfield_postal_code', 'field_user_addressfield_state', 'field_user_addressfield_country', 'field_user_phone_value', 'subscription_validity']);
      $query->condition('uid', '1', '>');
	  $query->condition('move_status', '0', '=');//This record is ready to process and Status - 0
	  $query->orderBy('uid', 'ASC');
      $query->range(0, 30);
      $z_results = $query->execute()->fetchAll();

      $fp = fopen("user.txt","a+");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      foreach ($z_results as $z_result) {

      //Update Status - This record is inprocess and Set - Status - 1
      $upd1 = \Drupal::database()->update('z_old_d7_users_data');
             $upd1->fields(['move_status' =>1,]);
             $upd1->condition('uid', $z_result->uid, '=');
             $upd1->execute();

          $user = \Drupal\user\Entity\User::create();    
		  // ***** 
		  // ** Inserted User Basic Details.
		  // *****

          fwrite($fp, PHP_EOL."\n"."\r\n");
          fwrite($fp, "--");
          fwrite($fp, $z_result->uid);
          fwrite($fp, "--");
          fwrite($fp, PHP_EOL."\n"."\r\n");
          $user->uid = $z_result->uid;
          $user->setUsername($z_result->name);
		  $user->setPassword('deep@123');
          $user->setEmail($z_result->mail);
          $user->enforceIsNew();
          $user->set('init', $z_result->mail);
          $user->set('langcode', $language);
          $user->set('preferred_langcode', $language);
          $user->set('preferred_admin_langcode', $language);
          $user->set('status', $z_result->status);    
          $user->set('timezone', $z_result->timezone);

          // *****
          // ** Inserted User Extra Details.
          // *****
          $user->set('field_account_type', $z_result->field_account_type_value);
          $user->set('field_salutation', $z_result->field_salutation_value);
          $user->set('field_first_name', $z_result->field_first_name_value);
          $user->set('field_last_name', $z_result->field_last_name_value);
          $user->set('field_middle_initial', $z_result->field_middle_initial_value);
          $user->set('field_phone_number', $z_result->field_user_phone_value);
          $user->set('field_religious_order', $z_result->field_religious_order_value);
          $user->set('field_suffix', $z_result->field_profile_suffix_value);
          $user->set('field_title', $z_result->field_profile_title_value);
          $user->set('field_company', $z_result->field_company_value);
          $user->set('field_subscription_validity', $z_result->subscription_validity);
    
          // *****
          // ** Inserted Newsletters Data.
          // *****
          // This is first Newsletter - Weekly Magazine
/*		  
		  $nl_str= array();
          $query_nl = \Drupal::database()->select('z_old_d7_nl_weekly_magazine_data', 'weekly_magazine');
          $query_nl->fields('weekly_magazine', ['uid']);
          $query_nl->condition('uid', $z_result->uid, '=');
          $z_results_nl1 = $query_nl->execute()->fetchAll();
          if(count($z_results_nl1) == 1){
             $nl_str[]='Weekly Magazine';
          }

          // This is second Newsletter - Digital Content Updates
          $query_nl = \Drupal::database()->select('z_old_d7_nl_digital_content_updates_data', 'digital_content');
          $query_nl->fields('digital_content', ['uid']);
          $query_nl->condition('uid', $z_result->uid, '=');
          $z_results_nl2 = $query_nl->execute()->fetchAll();
          if(count($z_results_nl2) == 1){
             $nl_str[]='Digital Content Updates';
          }

          // This is third Newsletter - Catholic Book Club
          $query_nl = \Drupal::database()->select('z_old_d7_nl_catholic_book_club_data', 'catholic_book');
          $query_nl->fields('catholic_book', ['uid']);
          $query_nl->condition('uid', $z_result->uid, '=');
          $z_results_nl3 = $query_nl->execute()->fetchAll();
          if(count($z_results_nl3) == 1){
             $nl_str[]='Catholic Book Club';
          }
          // Insert 1st, 2nd, and 3rd Newsletters in database
          $user->set('field_america_media_newsletters', $nl_str);
*/
          // *****
          // ** Inserted User Roles.
          // *****
          $query1 = \Drupal::database()->select('z_old_d7_users_roles_data', 'zur');
          $query1->fields('zur', ['uid', 'role_name']);
          $query1->condition('uid', $z_result->uid, '=');
          $z_results_role = $query1->execute()->fetchAll();
        
          foreach ($z_results_role as $z_result_role){
              if($z_result_role->role_name == "Web Editor"){
                 $user->addRole('editor');
              } else if($z_result_role->role_name == "User Manager"){
                 $user->addRole('site_manager');
              } if($z_result_role->role_name != "Author" && $z_result_role->role_name != "Subscriber" && $z_result_role->role_name != "Ad Manager" && $z_result_role->role_name != "Blog Editor" && $z_result_role->role_name != "Blog Contributor" && $z_result_role->role_name != "Archive Access" && $z_result_role->role_name != "Trusted User" && $z_result_role->role_name != "Commerce Manager" && $z_result_role->role_name != "Employer" && $z_result_role->role_name != "Print Subscriber"){
                 $user->addRole(trim(strtolower(str_replace(' ', '_', $z_result_role->role_name))));
              }
          }
          
          // ***** Save User Record ***** //
          $result = $user->save();

         //Update Status - User created, add more extra fields and Set - Status - 2
         $upd1 = \Drupal::database()->update('z_old_d7_users_data');
             $upd1->fields(['move_status' =>2,]);
             $upd1->condition('uid', $z_result->uid, '=');
             $upd1->execute();

          // *****
          // ** Inserted User address fields such that Country, State, City, Address 1, Address 2, and Postal Code.
          // *****
          if($result){
             $ins=\Drupal::database()->insert('user__field_address')->fields(
                 array('bundle' => 'user', 'deleted' => '0', 'entity_id' => $z_result->uid, 'revision_id' => $z_result->uid, 'langcode' => $language, 'delta' => '0',
                       'field_address_country_code' => $z_result->field_user_addressfield_country, 'field_address_administrative_area' => $z_result->field_user_addressfield_state,
                       'field_address_locality' => $z_result->field_user_addressfield_city, 'field_address_postal_code' => $z_result->field_user_addressfield_postal_code,
                       'field_address_address_line1' => $z_result->field_user_addressfield_address1, 'field_address_address_line2' => $z_result->field_user_addressfield_address2,
                      )
             )->execute();
	
             // *****
             // ** Update actual passowrd and date-time of user create, access and login fields.
             // *****
             $upd = \Drupal::database()->update('users_field_data');
             $upd->fields(['pass' => $z_result->pass, 'created' => $z_result->created, 'changed' => $z_result->created, 'access' => $z_result->access, 'login' => $z_result->login,]);
             $upd->condition('uid',$z_result->uid);
             $upd->execute();
          }

          //Update Status - This record has been fully processed and Set - Status - 3
          $upd = \Drupal::database()->update('z_old_d7_users_data');
          $upd->fields(['move_status' =>3,]);
          $upd->condition('uid', $z_result->uid, '=');
          $upd->execute();
	  }//loop end

      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "END-------------".date("Y-m-d H:i:s")."-----------------").PHP_EOL;
      fclose($fp);

   return array('#title' => 'User Data Migration', '#markup' => 'Records has been Processed!',);
}









/**
===============================================================================================================================
===============================================================================================================================


				**************************** Migration - Taxonomy & Term ****************************


===============================================================================================================================
===============================================================================================================================
**/
public function taxonomy_migration() {
   $query_term = \Drupal::database()->select('taxonomy_term_data', 'ttd');
   $query_term->fields('ttd', ['tid']);
   $results_term = $query_term->execute()->fetchAll();

   if(count($results_term)<100){
	  for($i=0; $i<1107; $i++){
          $term = \Drupal\taxonomy\Entity\Term::create([
             'vid' => 'article_type',
             'langcode' => 'en',
             'name' => 'Dep Vishwa',
             'description' => ['value' => '', 'format' => '',],
             'weight' => -1,
             'parent' => array(0),
             ]);
	        $term->save();
	  }
         $msg = 'Done!! Dummy Taxonomy Created, (This is only for one time). ';
	} else {
         $msg = 'This script has already executed.';
	}
	return array('#title' => $msg,'#markup' => 'Now follow the document "Taxonomy Data Migration.docx" for term migration',);
}






/**
===============================================================================================================================
===============================================================================================================================


				**************************** Migration - Content Type ****************************


===============================================================================================================================
===============================================================================================================================
**/


/**
===============================================================================================================================
===============================================================================================================================
	**************************** Content Type - Site Page (Now it is Basic Page in New System) ****************************
===============================================================================================================================
===============================================================================================================================
**/

public function node_migration_basicpage() {
  $domain_name = 'http://devnt-americad8.pantheonsite.io/sites/default/files/old_files_11012017/'; //Get Domain Name

  //Get data from Drupal 7 site
  $query = \Drupal::database()->select('z_old_d7_node__site_page', 'n_sp');
  $query->fields('n_sp', ['nid', 'vid', 'title', 'language', 'type', 'uid', 'STATUS', 'created', 'changed', 'body_value', 'body_summary', 'body_format', 'field_subhed_value', 'field_op_main_image_uri', 'field_op_main_image_filename', 'field_attached_file_filename', 'field_attached_file_uri']);
  $query->condition('move_status', '0', '='); //This record is ready to process and Status - 0
  $query->orderBy('nid', 'ASC');
  $query->range(0, 30);
  $z_results = $query->execute()->fetchAll();   


$fp = fopen("basic_page.txt","a+");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------");
      fwrite($fp, PHP_EOL."\n"."\r\n");


  foreach ($z_results as $z_result) {
    //Update Status - This record is inprocess and Set - Status - 1
    $upd1 = \Drupal::database()->update('z_old_d7_node__site_page');
          $upd1->fields(['move_status' =>1,]);
          $upd1->condition('nid', $z_result->nid, '=');
          $upd1->execute();

	//Image Uploading
	if ($z_result->field_op_main_image_uri) {
        $old_location = file_get_contents(str_replace("public://", $domain_name , $z_result->field_op_main_image_uri));
        $new_location = file_save_data($old_location, 'public://main_image/'.$z_result->field_op_main_image_filename);

		$image_target = $new_location->id();
		$image_alt = $z_result->field_profile_photo_alt;
		$image_title = $z_result->field_profile_photo_title;
	} else {
        $image_target = null;
        $image_alt = null;
        $image_title = null;
	}

	//file Uploading
	if ($z_result->field_attached_file_uri) {
        $old_location_file = file_get_contents(str_replace("public://", $domain_name, $z_result->field_attached_file_uri));
        $new_location_file = file_save_data($old_location_file, 'public://attachments/'.$z_result->field_attached_file_filename);
		$file_target = $new_location_file->id();
	}else {
        $file_target = null;
	}

      fwrite($fp, PHP_EOL."\n"."\r\n");
          fwrite($fp, "--");
          fwrite($fp, $z_result->nid);
          fwrite($fp, "--");
          fwrite($fp, PHP_EOL."\n"."\r\n");

	$node = Node::create([
          'type' => 'page',
		  'nid' => $z_result->nid,
          'vid' => $z_result->vid,
          'langcode' => $z_result->language,
          'uid' => $z_result->uid,
          'status' => $z_result->STATUS,
          'created' => $z_result->created,
          'changed' => $z_result->changed,
          'title' => $z_result->title,
          'body' => array('summary' => $z_result->body_summary, 'value' => $z_result->body_value,  'format' => $z_result->body_format,),
          //Extra Fields
		  'field_image' => ['target_id' => $image_target, 'alt' => $image_alt, 'title' => $image_title,],
		  'field_op_attached_file' => ['target_id' => $file_target,],
          'field_subtitle' => $z_result->field_subhed_value,
          'field_old_d7_nid' => $z_result->nid,
    ]);
    $node->save();
   //Update Status - This record has been processed and Set - Status - 2
    $upd = \Drupal::database()->update('z_old_d7_node__site_page');
           $upd->fields(['move_status' =>2,]);
           $upd->condition('nid', $z_result->nid, '=');
           $upd->execute();
  }//end for loop

      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "END-------------".date("Y-m-d H:i:s")."-----------------").PHP_EOL;
      fclose($fp);

  return array('#title' => 'Content Type - Basic or Site Page Migration','#markup' => '<b>'.count($z_results).'</b> Node Created !!',);
}







/**
===============================================================================================================================
===============================================================================================================================
				**************************** Content Type - Profile ****************************
===============================================================================================================================
===============================================================================================================================
**/
public function node_migration_profile() {
  $domain_name = 'http://devnt-americad8.pantheonsite.io/sites/default/files/old_files_11012017/'; //Get Domain Name
   
  //Get data from Drupal 7 site
  $query = \Drupal::database()->select('z_old_d7_node__profile', 'n_p');
  $query->fields('n_p', ['nid', 'vid', 'title', 'language', 'type', 'uid', 'STATUS', 'created', 'changed', 'body_value', 'body_summary', 'body_format', 'field_title_value', 'field_profile_first_name_value', 'field_middle_name_value', 'field_profile_last_name_value', 'field_suffix_value', 'field_profile_organization_value', 'filename', 'uri', 'field_profile_photo_alt', 'field_profile_photo_title', 'field_profile_user_uid', 'field_profile_phone_value', 'field_profile_email_value', 'field_profile_address_value', 'field_profile_job_title_value', 'field_twitter_value', 'field_facebook_url_title', 'field_facebook_url_url']);
  $query->condition('move_status', '0', '='); //This record is ready to process and Status - 0
  $query->orderBy('nid', 'ASC');
  $query->range(0, 30);
  $z_results = $query->execute()->fetchAll();


$fp = fopen("profile.txt","a+");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------");
      fwrite($fp, PHP_EOL."\n"."\r\n");


  foreach ($z_results as $z_result) {
   //Update Status - This record is inprocess and Set - Status - 1
   $upd1 = \Drupal::database()->update('z_old_d7_node__profile');
          $upd1->fields(['move_status' =>1,]);
          $upd1->condition('nid', $z_result->nid, '=');
          $upd1->execute();

	if ($z_result->uri) {
        $old_location = file_get_contents(str_replace("public://", $domain_name, $z_result->uri));
        $new_location = file_save_data($old_location, 'public://profile_photo/'.$z_result->filename);

		$image_target = $new_location->id();
		$image_alt = $z_result->field_profile_photo_alt;
		$image_title = $z_result->field_profile_photo_title;
	} else {
        $image_target = null;
        $image_alt = null;
        $image_title = null;
	}


fwrite($fp, PHP_EOL."\n"."\r\n");
          fwrite($fp, "--");
          fwrite($fp, $z_result->nid);
          fwrite($fp, "--");
          fwrite($fp, PHP_EOL."\n"."\r\n");


	$node = Node::create([
          'type' => 'profile',
		  'nid' => $z_result->nid,
          'vid' => $z_result->vid,
          'langcode' => $z_result->language,
          'uid' => $z_result->uid,
          'status' => $z_result->STATUS,
          'created' => $z_result->created,
          'changed' => $z_result->changed,
          'title' => $z_result->title,
          'body' => array('summary' => $z_result->body_summary, 'value' => $z_result->body_value,  'format' => $z_result->body_format,),

          'field_title' => $z_result->field_title_value,
          'field_first_name' => $z_result->field_profile_first_name_value,
          'field_middle_name' => $z_result->field_middle_name_value,  
          'field_profile_last_name' => $z_result->field_profile_last_name_value,
          'field_suffix' => $z_result->field_suffix_value,
          'field_profile_organization' => $z_result->field_profile_organization_value,
		  'field_profile_photo' => ['target_id' => $image_target, 'alt' => $image_alt, 'title' => $image_title,],
		  'field_profile_user' => $z_result->field_profile_user_uid,
          'field_profile_phone' => $z_result->field_profile_phone_value,
          'field_profile_email' => $z_result->field_profile_email_value,
          'field_address' => $z_result->field_profile_address_value,
          'field_profile_job_title' => $z_result->field_profile_job_title_value,
          'field_twitter_username' => $z_result->field_twitter_value,
		  'field_profile_facebook_url' => array('uri' => $z_result->field_facebook_url_url, 'title' => $z_result->field_facebook_url_title,),
          'field_old_d7_nid' => $z_result->nid,
    ]);
    $node->save();
   //Update Status - This record has been processed and Set - Status - 2
    $upd = \Drupal::database()->update('z_old_d7_node__profile');
           $upd->fields(['move_status' =>2,]);
           $upd->condition('nid', $z_result->nid, '=');
           $upd->execute();
  }//end for loop


     fwrite($fp, PHP_EOL."\n"."\r\n");
     fwrite($fp, "END-------------".date("Y-m-d H:i:s")."-----------------").PHP_EOL;
     fclose($fp);


  return array('#title' => 'Content Type - Profile Data Migration','#markup' => '<b>'.count($z_results).'</b> Node Created !!',);
}











/**
===============================================================================================================================
===============================================================================================================================
					**************************** Content Type - Book ****************************
===============================================================================================================================
===============================================================================================================================
**/
public function node_migration_book() {
  $domain_name = 'http://devnt-americad8.pantheonsite.io/sites/default/files/old_files_11012017/'; //Get Domain Name

  //Get data from Drupal 7 site
  $query = \Drupal::database()->select('z_old_d7_node__book', 'n_b');
  $query->fields('n_b', ['nid', 'vid', 'title', 'language', 'type', 'uid', 'STATUS', 'created', 'changed', 'body_value', 'body_format', 'field_subtitle_value', 'field_book_author_value', 'field_author_value', 'field_book_image_alt', 'field_book_image_title', 'filename', 'uri', 'field_critique_value', 'field_quote_value', 'field_isbn_value', 'field_publication_month_value', 'field_publisher_value', 'field_price_value']);
  $query->condition('move_status', '0', '='); //This record is ready to process and Status - 0
  $query->orderBy('nid', 'ASC');
  $query->range(0, 30);
  $z_results = $query->execute()->fetchAll();


$fp = fopen("book.txt","a+");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------");
      fwrite($fp, PHP_EOL."\n"."\r\n");

  foreach ($z_results as $z_result) {
   //Update Status - This record is inprocess and Set - Status - 1
   $upd1 = \Drupal::database()->update('z_old_d7_node__book');
          $upd1->fields(['move_status' =>1,]);
          $upd1->condition('nid', $z_result->nid, '=');
          $upd1->execute();

	//Image Uploading
	if ($z_result->uri) {
        $old_location = file_get_contents(str_replace("public://", $domain_name , $z_result->uri));
        $new_location = file_save_data($old_location, 'public://book_cover/'.$z_result->filename);

		$image_target = $new_location->id();
		$image_alt = $z_result->field_book_image_alt;
		$image_title = $z_result->field_book_image_title;
	} else {
        $image_target = null;
        $image_alt = null;
        $image_title = null;
	}


fwrite($fp, PHP_EOL."\n"."\r\n");
          fwrite($fp, "--");
          fwrite($fp, $z_result->nid);
          fwrite($fp, "--");
          fwrite($fp, PHP_EOL."\n"."\r\n");


	$node = Node::create([
          'type' => 'book',
          'nid' => $z_result->nid,
		  'vid' => $z_result->vid,
		  'langcode' => $z_result->language,
          'uid' => $z_result->uid,
          'status' => $z_result->STATUS,
          'created' => $z_result->created,
          'changed' => $z_result->changed,
          'title' => $z_result->title,
          'body' => array('summary' => '', 'value' => $z_result->body_value,  'format' => $z_result->body_format,),

		  'field_book_cover' => ['target_id' => $image_target, 'alt' => $image_alt, 'title' => $image_title,],
		  'field_author' => $z_result->field_author_value,
		  'field_book_author' => $z_result->field_book_author_value,
		  'field_critique' => $z_result->field_critique_value,
		  'field_publication_month' => date("Y-m-d", strtotime($z_result->field_publication_month_value)),
		  'field_isbn' => $z_result->field_isbn_value,
		  'field_price' => $z_result->field_price_value,
		  'field_publisher' => $z_result->field_publisher_value,
		  'field_quote' => $z_result->field_quote_value,
          'field_subtitle' => $z_result->field_subtitle_value,
          'field_old_d7_nid' => $z_result->nid,
    ]);
    $node->save();
   //Update Status - This record has been processed and Set - Status - 2
    $upd = \Drupal::database()->update('z_old_d7_node__book');
           $upd->fields(['move_status' =>2,]);
           $upd->condition('nid', $z_result->nid, '=');
           $upd->execute();
  }//end for loop


      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "END-------------".date("Y-m-d H:i:s")."-----------------").PHP_EOL;
      fclose($fp);

  return array('#title' => "Content Type - Book Page Migration",'#markup' => "Records has been Processed!",);
}








/**
===============================================================================================================================
===============================================================================================================================
					**************************** Content Type - Issue ****************************
===============================================================================================================================
===============================================================================================================================
**/
public function node_migration_issue() {
  $domain_name = 'http://devnt-americad8.pantheonsite.io/sites/default/files/old_files_11012017/'; //Get Domain Name

  $query = \Drupal::database()->select('z_old_d7_node__issue', 'n_i');
  $query->fields('n_i', ['nid', 'vid', 'title', 'language', 'type', 'uid', 'STATUS', 'created', 'changed', 'field_iss_date_value', 'field_iss_id_value', 'field_iss_num_value', 'field_iss_vol_value', 'field_iss_whole_num_value', 'field_iss_cover_alt', 'field_iss_cover_title', 'iss_cover_filename', 'iss_cover_uri', 'field_issue_pdf_description', 'issue_pdf_filename', 'issue_pdf_uri']);
  $query->condition('move_status', '0', '='); //This record is ready to process and Status - 0
  $query->orderBy('nid', 'ASC');
  $query->range(0, 30);
  $z_results = $query->execute()->fetchAll();


$fp = fopen("issue.txt","a+");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------");
      fwrite($fp, PHP_EOL."\n"."\r\n");

  foreach ($z_results as $z_result) {
   //Update Status - This record is inprocess and Set - Status - 1
   $upd1 = \Drupal::database()->update('z_old_d7_node__issue');
          $upd1->fields(['move_status' =>1,]);
          $upd1->condition('nid', $z_result->nid, '=');
          $upd1->execute();

	//Image Uploading
	if ($z_result->iss_cover_uri) {
        $old_location = file_get_contents(str_replace("public://", $domain_name , $z_result->iss_cover_uri));
        $new_location = file_save_data($old_location, 'public://issue_cover_image/'.$z_result->iss_cover_filename);

		$image_target = $new_location->id();
		$image_alt = $z_result->field_iss_cover_alt;
		$image_title = $z_result->field_iss_cover_title;
	} else {
        $image_target = null;
        $image_alt = null;
        $image_title = null;
	}

	//file Uploading
	if ($z_result->issue_pdf_uri) {
        $old_location_file = file_get_contents(str_replace("public://", $domain_name, $z_result->issue_pdf_uri));
        $new_location_file = file_save_data($old_location_file, 'public://issue_pdf/'.$z_result->issue_pdf_filename);
		$file_target = $new_location_file->id();
	}else {
        $file_target = null;
	}


fwrite($fp, PHP_EOL."\n"."\r\n");
          fwrite($fp, "--");
          fwrite($fp, $z_result->nid);
          fwrite($fp, "--");
          fwrite($fp, PHP_EOL."\n"."\r\n");

	$node = Node::create([
          'type' => 'issue',
		  'nid' => $z_result->nid,
		  'vid' => $z_result->vid,
          'langcode' => $z_result->language,
          'uid' => $z_result->uid,
          'status' => $z_result->STATUS,
          'created' => $z_result->created,
          'changed' => $z_result->changed,
          'title' => $z_result->title,

		  'field_iss_date' => $z_result->field_iss_date_value,
		  'field_iss_id' => $z_result->field_iss_id_value,
		  'field_iss_num' => $z_result->field_iss_num_value,
		  'field_iss_vol' => $z_result->field_iss_vol_value,
		  'field_iss_whole_num' => $z_result->field_iss_whole_num_value,
		  'field_iss_cover' => ['target_id' => $image_target, 'alt' => $image_alt, 'title' => $image_title,],
		  'field_issue_pdf' => ['target_id' => $file_target,],
		  'field_old_d7_nid' => $z_result->nid,
   ]);
     $node->save();
   //Update Status - This record has been processed and Set - Status - 2
	 $upd = \Drupal::database()->update('z_old_d7_node__issue');
           $upd->fields(['move_status' =>2,]);
           $upd->condition('nid', $z_result->nid, '=');
           $upd->execute();

	

  }//end for loop


  fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "END-------------".date("Y-m-d H:i:s")."-----------------").PHP_EOL;
      fclose($fp);

  return array('#title' => "Content Type - Issue Page Migration",'#markup' => "Records has been Processed!",);
}






/**
===============================================================================================================================
===============================================================================================================================
					**************************** Content Type - Podcast ****************************
===============================================================================================================================
===============================================================================================================================
**/
public function node_migration_podcast() {
  $domain_name = 'http://devnt-americad8.pantheonsite.io/sites/default/files/old_files_11012017/'; //Get Domain Name

  $query = \Drupal::database()->select('z_old_d7_node__podcast', 'n_pc');
  $query->fields('n_pc', ['nid', 'vid', 'title', 'language', 'type', 'uid', 'STATUS', 'created', 'changed', 'field_subtitle_value', 'field_op_author_nid', 'field_description_value', 'field_podcast_description_summary', 'field_podcast_description_value', 'field_speaker_value', 'field_speaker_title_value', 'field_audio_file_description', 'audio_filename', 'audio_uri', 'field_op_main_image_alt', 'field_op_main_image_title', 'main_image_filename', 'main_image_uri', 'field_series_tid', 'field_credits_value', 'field_podcast_section_tid']);
  $query->condition('move_status', '0', '='); //This record is ready to process and Status - 0
  $query->orderBy('nid', 'ASC');
  $query->range(0, 10);
  $z_results = $query->execute()->fetchAll();

$fp = fopen("podcast.txt","a+");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------");
      fwrite($fp, PHP_EOL."\n"."\r\n");

  foreach ($z_results as $z_result) {
   //Update Status - This record is inprocess and Set - Status - 1
   $upd1 = \Drupal::database()->update('z_old_d7_node__podcast');
          $upd1->fields(['move_status' =>1,]);
          $upd1->condition('nid', $z_result->nid, '=');
          $upd1->execute();

	//Image Uploading
	if ($z_result->main_image_uri) {
        $old_location = file_get_contents(str_replace("public://", $domain_name , $z_result->main_image_uri));
        $new_location = file_save_data($old_location, 'public://main_image/'.$z_result->main_image_filename);

		$image_target = $new_location->id();
		$image_alt = $z_result->field_op_main_image_alt;
		$image_title = $z_result->field_op_main_image_title;
	} else {
        $image_target = null;
        $image_alt = null;
        $image_title = null;
	}

	//file Uploading
	if ($z_result->audio_uri) {
        $old_location_file = file_get_contents(str_replace("public://", $domain_name, $z_result->audio_uri));
        $new_location_file = file_save_data($old_location_file, 'public://audio_file/'.$z_result->audio_filename);
		$file_target = $new_location_file->id();
		$file_description = $z_result->field_audio_file_description;
	}else {
        $file_target = null;
		$file_description = null;
	}

    if ($z_result->field_podcast_description_summary == null || $z_result->field_podcast_description_summary == ''){
	   $summary_value =	trim(substr(strip_tags($z_result->field_podcast_description_value), 0, 300));
	   $summary_value =	preg_replace('/[^A-Za-z0-9\-]/', ' ', $summary_value);
    } else {
	   $summary_value = $z_result->field_podcast_description_summary;
    }
    $pub_date = date("Y-m-d", $z_result->created) .'T'.date("H:i:s", $z_result->created);
	


fwrite($fp, PHP_EOL."\n"."\r\n");
          fwrite($fp, "--");
          fwrite($fp, $z_result->nid);
          fwrite($fp, "--");
          fwrite($fp, PHP_EOL."\n"."\r\n");

	$node = Node::create([
          'type' => 'podcast',
		  'nid' => $z_result->nid,
		  'vid' => $z_result->vid,
          'langcode' => $z_result->language,
          'uid' => $z_result->uid,
          'status' => $z_result->STATUS,
          'created' => $z_result->created,
          'changed' => $z_result->changed,
          'title' => $z_result->title,
          //Extra fields
          'field_by_author' => $z_result->field_op_author_nid,
          'field_credits' => $z_result->field_credits_value,
          'field_description' => $z_result->field_description_value,
          'field_podcast_description' => array('summary' => $summary_value, 'value' => $z_result->field_podcast_description_value,  'format' =>'full_html',),
          'field_publication_date' => $pub_date,
          'field_section' => $z_result->field_podcast_section_tid,
          'field_series' => $z_result->field_series_tid,
          'field_speaker' => $z_result->field_speaker_value,
          'field_speaker_title' => $z_result->field_speaker_title_value,
          'field_subtitle' => $z_result->field_subtitle_value,
          //'field_op_video_embed' => $z_result-> No Data avaialbe in D7
          'field_image' => ['target_id' => $image_target, 'alt' => $image_alt, 'title' => $image_title,],
          'field_audio_file' => ['target_id' => $file_target, 'description' => $file_description,],
          'field_old_d7_nid' => $z_result->nid,
          ]);
	 $node->save();
   //Update Status - This record has been processed and Set - Status - 2
     $upd = \Drupal::database()->update('z_old_d7_node__podcast');
           $upd->fields(['move_status' =>2,]);
           $upd->condition('nid', $z_result->nid, '=');
           $upd->execute();



  }//end for loop

fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "END-------------".date("Y-m-d H:i:s")."-----------------").PHP_EOL;
      fclose($fp);

  return array('#title' => "Content Type - Podcast Page Migration",'#markup' => "Records has been Processed!",);
}








/**
===============================================================================================================================
===============================================================================================================================
					**************************** Content Type - Video ****************************
===============================================================================================================================
===============================================================================================================================
**/
public function node_migration_video() {
  $domain_name = 'http://devnt-americad8.pantheonsite.io/sites/default/files/old_files_11012017/'; //Get Domain Name

  $query = \Drupal::database()->select('z_old_d7_node__video', 'n_v');
  $query->fields('n_v', ['nid', 'vid', 'title', 'language', 'type', 'uid', 'STATUS', 'created', 'changed', 'body_value', 'body_summary', 'body_format', 'field_op_author_nid', 'field_op_video_embed_video_url', 'field_op_section_term_tid', 'field_op_related_nref_nid', 'move_status']);
  $query->condition('move_status', '0', '='); //This record is ready to process and Status - 0
  $query->orderBy('nid', 'ASC');
  $query->range(0, 30);
  $z_results = $query->execute()->fetchAll();

$fp = fopen("video.txt","a+");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------");
      fwrite($fp, PHP_EOL."\n"."\r\n");


  foreach ($z_results as $z_result) {
   //Update Status - This record is inprocess and Set - Status - 1
   $upd1 = \Drupal::database()->update('z_old_d7_node__video');
          $upd1->fields(['move_status' =>1,]);
          $upd1->condition('nid', $z_result->nid, '=');
          $upd1->execute();

	if ($z_result->body_summary == null || $z_result->body_summary == ''){
	   $summary_value =	trim(substr(strip_tags($z_result->body_value), 0, 300));
    } else {
	   $summary_value = $z_result->body_summary;
    }

fwrite($fp, PHP_EOL."\n"."\r\n");
          fwrite($fp, "--");
          fwrite($fp, $z_result->nid);
          fwrite($fp, "--");
          fwrite($fp, PHP_EOL."\n"."\r\n");


	$node = Node::create([
          'type' => 'video',
		  'nid' => $z_result->nid,
		  'vid' => $z_result->vid,
          'langcode' => $z_result->language,
          'uid' => $z_result->uid,
          'status' => $z_result->STATUS,
          'created' => $z_result->created,
          'changed' => $z_result->changed,
          'title' => $z_result->title,
          //Extra fields
          'body' => array('summary' => $summary_value, 'value' => $z_result->body_value,  'format' => $z_result->body_format,),
          'field_op_video_embed' => $z_result->field_op_video_embed_video_url,
          'field_by_author' => $z_result->field_op_author_nid,
          'field_op_related_nref' => $z_result->field_op_related_nref_nid,
          'field_section' => $z_result->field_op_section_term_tid,
          'field_old_d7_nid' => $z_result->nid,
          ]);
	 $node->save();
   //Update Status - This record has been processed and Set - Status - 2
	 $upd = \Drupal::database()->update('z_old_d7_node__video');
           $upd->fields(['move_status' =>2,]);
           $upd->condition('nid', $z_result->nid, '=');
           $upd->execute();
  }//end for loop

fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "END-------------".date("Y-m-d H:i:s")."-----------------").PHP_EOL;
      fclose($fp);

  return array('#title' => "Content Type - Video Page Migration",'#markup' => "Records has been Processed!",);
}









/**
===============================================================================================================================
===============================================================================================================================
					**************************** Content Type - Photo Gallery ****************************
===============================================================================================================================
===============================================================================================================================
**/
public function node_migration_photo_gallery() {
  $query = \Drupal::database()->select('z_old_d7_node__photo_gallery', 'n_pg');
  $query->fields('n_pg', ['nid','vid','title', 'language', 'uid', 'STATUS', 'created', 'changed', 'field_op_author_nid', 'field_op_section_term_tid', 'field_op_related_nref_nid']);
  $query->condition('move_status', '0', '='); //This record is ready to process and Status - 0
  $query->orderBy('nid', 'ASC');
  $query->range(0, 30);
  $z_results = $query->execute()->fetchAll();

$fp = fopen("photo_gallery.txt","a+");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------");
      fwrite($fp, PHP_EOL."\n"."\r\n");

  foreach ($z_results as $z_result) {

      //Update Status - This record is inprocess and Set - Status - 1
      $upd1 = \Drupal::database()->update('z_old_d7_node__photo_gallery');
      $upd1->fields(['move_status' =>1,]);
      $upd1->condition('nid', $z_result->nid, '=');
      $upd1->execute();


fwrite($fp, PHP_EOL."\n"."\r\n");
          fwrite($fp, "--");
          fwrite($fp, $z_result->nid);
          fwrite($fp, "--");
          fwrite($fp, PHP_EOL."\n"."\r\n");

	  $node = Node::create([
          'type' => 'photo_gallery',
		  'nid' => $z_result->nid,
		  'vid' => $z_result->vid,
          'langcode' => $z_result->language,
          'uid' => $z_result->uid,
          'status' => $z_result->STATUS,
          'created' => $z_result->created,
          'changed' => $z_result->changed,
          'title' => $z_result->title,
          //Extra Fields
		  'field_by_author' => $z_result->field_op_author_nid,
		  'field_op_related_nref' => $z_result->field_op_related_nref_nid,
          'field_section' => $z_result->field_op_section_term_tid,
		  'field_old_d7_nid' => $z_result->nid,
      ]);
      $node->save();
      
	  //Update Status - This record has been processed and Set - Status - 2
      $upd = \Drupal::database()->update('z_old_d7_node__photo_gallery');
      $upd->fields(['move_status' =>2,]);
      $upd->condition('nid', $z_result->nid, '=');
      $upd->execute();
  }//end for loop

fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "END-------------".date("Y-m-d H:i:s")."-----------------").PHP_EOL;
      fclose($fp);
return array('#title' => "Content Type - Photo Gallery Migration", '#markup' => "Records has been Processed!",);
}






/**
===============================================================================================================================
===============================================================================================================================
					**************************** Content Type - Article ****************************
===============================================================================================================================
===============================================================================================================================
**/
public function node_migration_article() {
  $domain_name = 'http://devnt-americad8.pantheonsite.io/sites/default/files/old_files_11012017/'; //Get Domain Name

  $query = \Drupal::database()->select('z_old_d7_node__article', 'n_a');
  $query->fields('n_a', ['nid', 'vid', 'title', 'language', 'type', 'uid', 'STATUS', 'created', 'changed', 'body_value', 'body_summary', 'body_format', 'field_subhed_value', 'field_social_hed_value', 'field_author_bio_value', 'field_op_main_image_title', 'field_op_main_image_alt', 'main_image_filename', 'main_image_uri', 'field_social_media_image_title', 'field_social_media_image_alt', 'social_media_image_filename', 'social_media_image_uri', 'field_related_media_target_id', 'field_issue_target_id', 'field_op_section_term_tid', 'field_in_these_pages_value', 'field_editors_pick_value', 'field_print_value', 'field_living_word_feature_value', 'move_status']);
  $query->condition('move_status', '0', '='); //This record is ready to process and Status - 0
  $query->orderBy('nid', 'ASC');
  $query->range(0, 30);
  $z_results = $query->execute()->fetchAll();


$fp = fopen("article.txt","a+");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------");
      fwrite($fp, PHP_EOL."\n"."\r\n");


  foreach ($z_results as $z_result) {
   //Update Status - This record is inprocess and Set - Status - 1
   $upd1 = \Drupal::database()->update('z_old_d7_node__article');
          $upd1->fields(['move_status' =>1,]);
          $upd1->condition('nid', $z_result->nid, '=');
          $upd1->execute();

	//Main Image Uploading
	if ($z_result->main_image_uri) {
        $old_location_mi = file_get_contents(str_replace("public://", $domain_name , $z_result->main_image_uri));
        $new_location_mi = file_save_data($old_location_mi, 'public://main_image/'.$z_result->main_image_filename);

		$image_target_mi = $new_location_mi->id();
		$image_alt_mi = $z_result->field_op_main_image_title;
		$image_title_mi = $z_result->field_op_main_image_alt;
	} else {
        $image_target_mi = null;
        $image_alt_mi = null;
        $image_title_mi = null;
	}

	//Social Media Image Uploading
	if ($z_result->social_media_image_uri) {
        $old_location_smi = file_get_contents(str_replace("public://", $domain_name , $z_result->social_media_image_uri));
        $new_location_smi = file_save_data($old_location_smi, 'public://social_media_image/'.$z_result->social_media_image_filename);

		$image_target_smi = $new_location_smi->id();
		$image_alt_smi = $z_result->field_social_media_image_alt;
		$image_title_smi = $z_result->field_social_media_image_title;
	} else {
        $image_target_smi = null;
        $image_alt_smi = null;
        $image_title_smi = null;
	}

    // If Social Media Headline exists, use that "Title: Social Media Headline", else IF Subheadline exists, use �Title: Subheadline�, else use just use D7 title alone.
    if ($z_result->field_social_hed_value){
	    $new_title = $z_result->field_social_hed_value;
    } else if ($z_result->field_subhed_value){
		$new_title = $z_result->title.': '.$z_result->field_subhed_value;
    } else {
	    $new_title = $z_result->title;
	}

    // If Summary field is blank then 300 characters copy and pasted from body section to summary field
    if ($z_result->body_summary == null || $z_result->body_summary == ''){
	   $summary_value =	trim(substr(strip_tags($z_result->body_value), 0, 300));
	   $summary_value =	preg_replace('/[^A-Za-z0-9\-]/', ' ', $summary_value);
    } else {
	   $summary_value = $z_result->body_summary;
    }
	
	// Distinct Related Media Content to Podcast, Video & Photo Gallery contents and store into separate fields
	if($z_result->field_related_media_target_id){
		$node = Node::load($z_result->field_related_media_target_id); //related media node load
		$rm_node_type = $node->bundle();
		if($rm_node_type == 'podcast'){
		     $podcast_type_nid = $z_result->field_related_media_target_id;
			 $video_type_nid = null;
			 $photo_gallery_type_nid = null;
	    } else if($rm_node_type == 'video'){
		     $podcast_type_nid = null;
			 $video_type_nid = $z_result->field_related_media_target_id;
			 $photo_gallery_type_nid = null;
	    } else if($rm_node_type == 'photo_gallery'){
		     $podcast_type_nid = null;
			 $video_type_nid = null;
			 $photo_gallery_type_nid = $z_result->field_related_media_target_id;
		}  else {
		     $podcast_type_nid = null;
			 $video_type_nid = null;
			 $photo_gallery_type_nid = null;
		}
    } else {
		$podcast_type_nid = null;
		$video_type_nid = null;
		$photo_gallery_type_nid = null;
	}
    
	$author_list= array();
	$attached_file_list= array();
	$related_content_list= array();
    
	// Insert Multiple authors
    $query_author = \Drupal::database()->select('z_old_d7_node__article__author_node', 'aan');
    $query_author->fields('aan', ['nid', 'field_op_author_nid']);
    $query_author->condition('nid', $z_result->nid, '=');
    $query_author->orderBy('nid', 'ASC');
    $qa_results = $query_author->execute()->fetchAll();

    foreach ($qa_results as $qa_result) {
		$author_list[] = $qa_result->field_op_author_nid;
	}
	
	// Upload Multiple attached files
    $query_attach_file = \Drupal::database()->select('z_old_d7_node__article__attach_file', 'aaf');
    $query_attach_file->fields('aaf', ['nid', 'field_attached_file_description', 'attached_file_filename', 'attached_file_uri']);
    $query_attach_file->condition('nid', $z_result->nid, '=');
    $query_attach_file->orderBy('nid', 'ASC');
    $qaf_results = $query_attach_file->execute()->fetchAll();

    foreach ($qaf_results as $qaf_result) {
		//file Uploading
		if ($qaf_result->attached_file_uri) {
			$old_location_file_attach = file_get_contents(str_replace("public://", $domain_name, $qaf_result->attached_file_uri));
			$new_location_file_attach = file_save_data($old_location_file_attach, 'public://attachments/'.$qaf_result->attached_file_filename);

			$file_target = $new_location_file_attach->id();
			$file_description = $qaf_result->field_attached_file_description;
		}else {
			$file_target = null;
			$file_description = null;
		}
		$attached_file_list[] = array('target_id' => $file_target, 'description' => $file_description,);
	}

    // Insert Multiple Related Contents
    $query_rcontent = \Drupal::database()->select('z_old_d7_node__article__relatedcontent_node', 'arn');
    $query_rcontent->fields('arn', ['nid', 'field_op_related_nref_nid']);
	$query_rcontent->condition('nid', $z_result->nid, '=');
    $query_rcontent->orderBy('nid', 'ASC');
    $qrc_results = $query_rcontent->execute()->fetchAll();

    foreach ($qrc_results as $qrc_result) {
		$related_content_list[] = $qrc_result->field_op_related_nref_nid;
	}

    $pub_date = date("Y-m-d", $z_result->created) .'T'.date("H:i:s", $z_result->created);
	

fwrite($fp, PHP_EOL."\n"."\r\n");
          fwrite($fp, "--");
          fwrite($fp, $z_result->nid);
          fwrite($fp, "--");
          fwrite($fp, PHP_EOL."\n"."\r\n");

	$node = Node::create([
          'type' => 'article',
		  'nid' => $z_result->nid,
		  'vid' => $z_result->vid,
          'langcode' => $z_result->language,
          'uid' => $z_result->uid,
          'status' => $z_result->STATUS,
          'created' => $z_result->created,
          'changed' => $z_result->changed,
          'title' => $new_title,
          'body' => array('summary' => $summary_value, 'value' => $z_result->body_value,  'format' => $z_result->body_format,),
          //Extra Fields
		  'field_print_headline' => $z_result->title,
		  'field_publication_date' => $pub_date,
          'field_old_d7_subheadline' => $z_result->field_subhed_value,
          'field_old_d7_social_media_headli' => $z_result->field_social_hed_value,
          'field_author_bio' => $z_result->field_author_bio_value,
          'field_image' => ['target_id' => $image_target_mi, 'alt' => $image_alt_mi, 'title' => $image_title_mi,],
          'field_old_d7_social_media_image' => ['target_id' => $image_target_smi, 'alt' => $image_alt_smi, 'title' => $image_title_smi,],
		  'field_old_d7_related_media' => $z_result->field_related_media_target_id,
          'field_related_podcast' => $podcast_type_nid,
          'field_related_video' => $video_type_nid,
          'field_related_photo_gallery' => $photo_gallery_type_nid,
          'field_issue' => $z_result->field_issue_target_id,
          'field_section' => $z_result->field_op_section_term_tid,
          'field_old_d7_editors_pick' => $z_result->field_editors_pick_value,
          'field_old_d7_in_these_pages' => $z_result->field_in_these_pages_value,
          'field_old_d7_published_in_print' => $z_result->field_print_value,
		  'field_old_d7_living_word_feature' => $z_result->field_living_word_feature_value,
          'field_old_d7_nid' => $z_result->nid,
		  //More Extra Fields
		  'field_by_author' => $author_list,
		  'field_op_related_nref' => $related_content_list,
		  'field_op_attached_file' => $attached_file_list,
   ]);
   $node->save();
   //Update Status - This record has been processed and Set - Status - 2
   $upd = \Drupal::database()->update('z_old_d7_node__article');
          $upd->fields(['move_status' =>2,]);
          $upd->condition('nid', $z_result->nid, '=');
          $upd->execute();
  }//end for loop

fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "END-------------".date("Y-m-d H:i:s")."-----------------").PHP_EOL;
      fclose($fp);

  return array('#title' => "Content Type - Article Migration", '#markup' => "Records has been Processed!",);
}











/**
===============================================================================================================================
===============================================================================================================================
	**************************** Content Type - Blog Post (Now it is Article in New System) ****************************
===============================================================================================================================
===============================================================================================================================
**/
public function node_migration_blog_post_article() {
 $domain_name = 'http://devnt-americad8.pantheonsite.io/sites/default/files/old_files_11012017/'; //Get Domain Name

  $query = \Drupal::database()->select('z_old_d7_node__blog_post', 'n_bp');
  $query->fields('n_bp', ['nid', 'vid', 'title', 'language', 'type', 'uid', 'STATUS', 'created', 'changed', 'body_value', 'body_summary', 'body_format', 'field_subhed_value', 'field_social_hed_value', 'field_op_section_term_tid', 'field_op_related_nref_nid', 'field_op_blogpost_blog_tid', 'field_publish_date_value', 'field_op_main_image_title', 'field_op_main_image_alt', 'main_image_filename', 'main_image_uri', 'field_social_media_image_title', 'field_social_media_image_alt', 'social_media_filename', 'social_media_uri', 'field_editors_pick_value', 'move_status']);
  $query->condition('move_status', '0', '='); //This record is ready to process and Status - 0
  $query->orderBy('nid', 'ASC');
  $query->range(0, 30);
  $z_results = $query->execute()->fetchAll();


$fp = fopen("blog_post_article.txt","a+");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------");
      fwrite($fp, PHP_EOL."\n"."\r\n");

  foreach ($z_results as $z_result) {
   //Update Status - This record is inprocess and Set - Status - 1
   $upd1 = \Drupal::database()->update('z_old_d7_node__blog_post');
          $upd1->fields(['move_status' =>1,]);
          $upd1->condition('nid', $z_result->nid, '=');
          $upd1->execute();

	//Main Image Uploading
	if ($z_result->main_image_uri) {
        $old_location_mi = file_get_contents(str_replace("public://", $domain_name , $z_result->main_image_uri));
        $new_location_mi = file_save_data($old_location_mi, 'public://main_image/'.$z_result->main_image_filename);

		$image_target_mi = $new_location_mi->id();
		$image_alt_mi = $z_result->field_op_main_image_title;
		$image_title_mi = $z_result->field_op_main_image_alt;
	} else {
        $image_target_mi = null;
        $image_alt_mi = null;
        $image_title_mi = null;
	}

	//Social Media Image Uploading
	if ($z_result->social_media_uri) {
        $old_location_smi = file_get_contents(str_replace("public://", $domain_name , $z_result->social_media_uri));
        $new_location_smi = file_save_data($old_location_smi, 'public://social_media_image/'.$z_result->social_media_filename);

		$image_target_smi = $new_location_smi->id();
		$image_alt_smi = $z_result->field_social_media_image_alt;
		$image_title_smi = $z_result->field_social_media_image_title;
	} else {
        $image_target_smi = null;
        $image_alt_smi = null;
        $image_title_smi = null;
	}

    // If Social Media Headline exists, use that "Title: Social Media Headline", else IF Subheadline exists, use �Title: Subheadline�, else use just use D7 title alone.
    if ($z_result->field_social_hed_value){
	    $new_title = $z_result->field_social_hed_value;
    } else if ($z_result->field_subhed_value){
		$new_title = $z_result->title.': '.$z_result->field_subhed_value;
    } else {
	    $new_title = $z_result->title;
	}

    // If Summary field is blank then 300 characters copy and pasted from body section to summary field
    if ($z_result->body_summary == null || $z_result->body_summary == ''){
	   $summary_value =	trim(substr(strip_tags($z_result->body_value), 0, 300)); //issue in single quote and slash etc
	   $summary_value =	preg_replace('/[^A-Za-z0-9\-]/', ' ', $summary_value);
    } else {
	   $summary_value = $z_result->body_summary;
    }

	$author_list= array();
	
	// Insert Multiple authors
    $query_author = \Drupal::database()->select('z_old_d7_node__blog_post__author_node', 'aan');
    $query_author->fields('aan', ['nid', 'field_op_author_nid']);
    $query_author->condition('nid', $z_result->nid, '=');
    $query_author->orderBy('nid', 'ASC');
    $qa_results = $query_author->execute()->fetchAll();

    foreach ($qa_results as $qa_result) {
		$author_list[] = $qa_result->field_op_author_nid;
	}
	
    $pub_date = date("Y-m-d", strtotime($z_result->field_publish_date_value)) .'T'.date("H:i:s", strtotime($z_result->field_publish_date_value));
	

fwrite($fp, PHP_EOL."\n"."\r\n");
          fwrite($fp, "--");
          fwrite($fp, $z_result->nid);
          fwrite($fp, "--");
          fwrite($fp, PHP_EOL."\n"."\r\n");

	$node = Node::create([
          'type' => 'article',
		  'nid' => $z_result->nid,
		  'vid' => $z_result->vid,
          'langcode' => $z_result->language,
          'uid' => $z_result->uid,
          'status' => $z_result->STATUS,
          'created' => $z_result->created,
          'changed' => $z_result->changed,
          'title' => $new_title,
          'body' => array('summary' => $summary_value, 'value' => $z_result->body_value,  'format' => $z_result->body_format,),
          //Extra Fields
		  'field_print_headline' => $z_result->title,
		  'field_publication_date' => $pub_date,
          'field_old_d7_subheadline' => $z_result->field_subhed_value,
          'field_old_d7_social_media_headli' => $z_result->field_social_hed_value,
          'field_image' => ['target_id' => $image_target_mi, 'alt' => $image_alt_mi, 'title' => $image_title_mi,],
          'field_old_d7_social_media_image' => ['target_id' => $image_target_smi, 'alt' => $image_alt_smi, 'title' => $image_title_smi,],
          'field_section' => $z_result->field_op_section_term_tid,
          'field_old_d7_editors_pick' => $z_result->field_editors_pick_value,
          'field_old_d7_nid' => $z_result->nid,
		  'field_op_related_nref' => $z_result->field_op_related_nref_nid,
		  'field_blog' => $z_result->field_op_blogpost_blog_tid,
		  //More Extra Fields
		  'field_by_author' => $author_list,
   ]);
   $node->save();
   //Update Status - This record has been processed and Set - Status - 2
   $upd = \Drupal::database()->update('z_old_d7_node__blog_post');
          $upd->fields(['move_status' =>2,]);
          $upd->condition('nid', $z_result->nid, '=');
          $upd->execute();
  }//end for loop


      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "END-------------".date("Y-m-d H:i:s")."-----------------").PHP_EOL;
      fclose($fp);

  return array('#title' => "Content Type - Blog Post (Article) Migration", '#markup' => "Records has been Processed!",);
}












/**
===============================================================================================================================
===============================================================================================================================
					**************************** Content Type - Book Review ****************************
===============================================================================================================================
===============================================================================================================================
**/
public function node_migration_bookreview() {
  $domain_name = 'http://devnt-americad8.pantheonsite.io/sites/default/files/old_files_11012017/'; //Get Domain Name

  $query = \Drupal::database()->select('z_old_d7_node__book_review', 'n_br');
  $query->fields('n_br', ['nid', 'vid', 'title', 'language', 'type', 'uid', 'STATUS', 'created', 'changed', 'body_value', 'body_summary', 'body_format', 'field_subhed_value', 'field_social_hed_value', 'field_issue_target_id', 'field_author_bio_value', 'field_op_caption_value', 'field_op_section_term_tid', 'field_op_main_image_title', 'field_op_main_image_alt', 'main_image_filename', 'main_image_uri', 'field_social_media_image_title', 'field_social_media_image_alt', 'social_media_image_filename', 'social_media_image_uri', 'field_op_related_nref_nid', 'field_related_media_target_id', 'field_review_bookclub_value', 'field_in_these_pages_value', 'field_editors_pick_value', 'field_print_value', 'field_living_word_feature_value', 'move_status']);
  $query->condition('move_status', '0', '='); //This record is ready to process and Status - 0
  $query->orderBy('nid', 'ASC');
  $query->range(0, 30);
  $z_results = $query->execute()->fetchAll();


$fp = fopen("bookreview.txt","a+");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------");
      fwrite($fp, PHP_EOL."\n"."\r\n");


  foreach ($z_results as $z_result) {
    //Update Status - This record is inprocess and Set - Status - 1
    $upd1 = \Drupal::database()->update('z_old_d7_node__book_review');
          $upd1->fields(['move_status' =>1,]);
          $upd1->condition('nid', $z_result->nid, '=');
          $upd1->execute();

	//Main Image Uploading
	if ($z_result->main_image_uri) {
        $old_location_mi = file_get_contents(str_replace("public://", $domain_name , $z_result->main_image_uri));
        $new_location_mi = file_save_data($old_location_mi, 'public://main_image/'.$z_result->main_image_filename);

		$image_target_mi = $new_location_mi->id();
		$image_alt_mi = $z_result->field_op_main_image_title;
		$image_title_mi = $z_result->field_op_main_image_alt;
	} else {
        $image_target_mi = null;
        $image_alt_mi = null;
        $image_title_mi = null;
	}

	//Social Media Image Uploading
	if ($z_result->social_media_image_uri) {
        $old_location_smi = file_get_contents(str_replace("public://", $domain_name , $z_result->social_media_image_uri));
        $new_location_smi = file_save_data($old_location_smi, 'public://social_media_image/'.$z_result->social_media_image_filename);

		$image_target_smi = $new_location_smi->id();
		$image_alt_smi = $z_result->field_social_media_image_alt;
		$image_title_smi = $z_result->field_social_media_image_title;
	} else {
        $image_target_smi = null;
        $image_alt_smi = null;
        $image_title_smi = null;
	}

    // If Social Media Headline exists, use that "Title: Social Media Headline", else IF Subheadline exists, use �Title: Subheadline�, else use just use D7 title alone.
    if ($z_result->field_social_hed_value){
	    $new_title = $z_result->field_social_hed_value;
    } else if ($z_result->field_subhed_value){
		$new_title = $z_result->title.': '.$z_result->field_subhed_value;
    } else {
	    $new_title = $z_result->title;
	}

    // If Summary field is blank then 300 characters copy and pasted from body section to summary field
    if ($z_result->body_summary == null || $z_result->body_summary == ''){
	   $summary_value =	trim(substr(strip_tags($z_result->body_value), 0, 300));
	   $summary_value =	preg_replace('/[^A-Za-z0-9\-]/', ' ', $summary_value);
    } else {
	   $summary_value = $z_result->body_summary;
    }
	
	// Distinct Related Media Content to Podcast, Video & Photo Gallery contents and store into separate fields
	if($z_result->field_related_media_target_id){
		$node = Node::load($z_result->field_related_media_target_id); //related media node load
		$rm_node_type = $node->bundle();
		if($rm_node_type == 'podcast'){
		     $podcast_type_nid = $z_result->field_related_media_target_id;
			 $video_type_nid = null;
			 $photo_gallery_type_nid = null;
	    } else if($rm_node_type == 'video'){
		     $podcast_type_nid = null;
			 $video_type_nid = $z_result->field_related_media_target_id;
			 $photo_gallery_type_nid = null;
	    } else if($rm_node_type == 'photo_gallery'){
		     $podcast_type_nid = null;
			 $video_type_nid = null;
			 $photo_gallery_type_nid = $z_result->field_related_media_target_id;
		}  else {
		     $podcast_type_nid = null;
			 $video_type_nid = null;
			 $photo_gallery_type_nid = null;
		}
    } else {
		$podcast_type_nid = null;
		$video_type_nid = null;
		$photo_gallery_type_nid = null;
	}
    
	$author_list= array();
	$book_list= array();
    
	// Insert Multiple authors
    $query_author = \Drupal::database()->select('z_old_d7_node__book_review__author_node', 'aan');
    $query_author->fields('aan', ['nid', 'field_op_author_nid']);
    $query_author->condition('nid', $z_result->nid, '=');
    $query_author->orderBy('nid', 'ASC');
    $qa_results = $query_author->execute()->fetchAll();

    foreach ($qa_results as $qa_result) {
		$author_list[] = $qa_result->field_op_author_nid;
	}

    // Insert Multiple Book Reference
    $query_rcontent = \Drupal::database()->select('z_old_d7_node__book_review__book_node', 'brn');
    $query_rcontent->fields('brn', ['nid', 'field_book_node_nid']);
	$query_rcontent->condition('nid', $z_result->nid, '=');
    $query_rcontent->orderBy('nid', 'ASC');
    $qrc_results = $query_rcontent->execute()->fetchAll();

    foreach ($qrc_results as $qrc_result) {
		$book_list[] = $qrc_result->field_book_node_nid;
	}

    $pub_date = date("Y-m-d", $z_result->created) .'T'.date("H:i:s", $z_result->created);
	

fwrite($fp, PHP_EOL."\n"."\r\n");
          fwrite($fp, "--");
          fwrite($fp, $z_result->nid);
          fwrite($fp, "--");
          fwrite($fp, PHP_EOL."\n"."\r\n");

	$node = Node::create([
          'type' => 'book_review',
		  'nid' => $z_result->nid,
		  'vid' => $z_result->vid,
          'langcode' => $z_result->language,
          'uid' => $z_result->uid,
          'status' => $z_result->STATUS,
          'created' => $z_result->created,
          'changed' => $z_result->changed,
          'title' => $new_title,
          'body' => array('summary' => $summary_value, 'value' => $z_result->body_value,  'format' => $z_result->body_format,),
          //Extra Fields
		  'field_author_bio' => $z_result->field_author_bio_value,
		  'field_image_caption' => $z_result->field_op_caption_value,
		  'field_issue' => $z_result->field_issue_target_id,
		  'field_image' => ['target_id' => $image_target_mi, 'alt' => $image_alt_mi, 'title' => $image_title_mi,],
		  'field_old_d7_editors_pick' => $z_result->field_editors_pick_value,
          'field_old_d7_in_these_pages' => $z_result->field_in_these_pages_value,
		  'field_old_d7_is_this_a_book_club' => $z_result-> field_review_bookclub_value,
		  'field_old_d7_living_word_feature' => $z_result->field_living_word_feature_value,
          'field_old_d7_nid' => $z_result->nid,
		  'field_old_d7_published_in_print' => $z_result->field_print_value,
          'field_old_d7_related_media' => $z_result->field_related_media_target_id,
          'field_old_d7_social_media_headli' => $z_result->field_social_hed_value,
          'field_old_d7_social_media_image' => ['target_id' => $image_target_smi, 'alt' => $image_alt_smi, 'title' => $image_title_smi,],
          'field_old_d7_subheadline' => $z_result->field_subhed_value,
		  'field_print_headline' => $z_result->title,
		  'field_publication_date' => $pub_date,
          'field_op_related_nref' => $z_result->field_op_related_nref_nid,
          'field_related_podcast' => $podcast_type_nid,
          'field_related_video' => $video_type_nid,
          'field_related_photo_gallery' => $photo_gallery_type_nid,
          'field_section' => $z_result->field_op_section_term_tid,
		  //More Extra Fields
		  'field_by_author' => $author_list,
		  'field_book_node' => $book_list,
   ]);
   $node->save();
   //Update Status - This record has been processed and Set - Status - 2
   $upd = \Drupal::database()->update('z_old_d7_node__book_review');
          $upd->fields(['move_status' =>2,]);
          $upd->condition('nid', $z_result->nid, '=');
          $upd->execute();
  }//end for loop

fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "END-------------".date("Y-m-d H:i:s")."-----------------").PHP_EOL;
      fclose($fp);

return array('#title' => "Content Type - Book Review Migration", '#markup' => "Records has been Processed!",);
}








/**
===============================================================================================================================
===============================================================================================================================
					**************************** Content Type - The Word ****************************
===============================================================================================================================
===============================================================================================================================
**/
public function node_migration_theword() {
  $domain_name = 'http://devnt-americad8.pantheonsite.io/sites/default/files/old_files_11012017/'; //Get Domain Name

  $query = \Drupal::database()->select('z_old_d7_node__the_word', 'n_tw');
  $query->fields('n_tw', ['nid', 'vid', 'title', 'language', 'type', 'uid', 'STATUS', 'created', 'changed', 'body_value', 'body_summary', 'body_format', 'field_subhed_value', 'field_social_hed_value', 'field_op_author_nid', 'field_author_bio_value', 'field_liturgical_date_value', 'field_readings_value', 'field_word_quote_value', 'field_op_caption_value', 'field_word_prayer_value','field_op_main_image_title', 'field_op_main_image_alt', 'main_image_filename', 'main_image_uri', 'field_social_media_image_title', 'field_social_media_image_alt', 'social_media_image_filename', 'social_media_image_uri', 'field_related_media_target_id', 'field_op_related_nref_nid', 'field_issue_target_id', 'field_op_section_term_tid', 'field_in_these_pages_value', 'field_editors_pick_value', 'field_print_value','move_status']);
  $query->condition('move_status', '0', '='); //This record is ready to process and Status - 0
  $query->orderBy('nid', 'ASC');
  $query->range(0, 30);  
  $z_results = $query->execute()->fetchAll();


$fp = fopen("theword.txt","a+");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------");
      fwrite($fp, PHP_EOL."\n"."\r\n");


  foreach ($z_results as $z_result) {

   //Update Status - This record is inprocess and Set - Status - 1
   $upd1 = \Drupal::database()->update('z_old_d7_node__the_word');
          $upd1->fields(['move_status' =>1,]);
          $upd1->condition('nid', $z_result->nid, '=');
          $upd1->execute();

	//Main Image Uploading
	if ($z_result->main_image_uri) {
        $old_location_mi = file_get_contents(str_replace("public://", $domain_name , $z_result->main_image_uri));
        $new_location_mi = file_save_data($old_location_mi, 'public://main_image/'.$z_result->main_image_filename);

		$image_target_mi = $new_location_mi->id();
		$image_alt_mi = $z_result->field_op_main_image_title;
		$image_title_mi = $z_result->field_op_main_image_alt;
	} else {
        $image_target_mi = null;
        $image_alt_mi = null;
        $image_title_mi = null;
	}

	//Social Media Image Uploading
	if ($z_result->social_media_image_uri) {
        $old_location_smi = file_get_contents(str_replace("public://", $domain_name , $z_result->social_media_image_uri));
        $new_location_smi = file_save_data($old_location_smi, 'public://social_media_image/'.$z_result->social_media_image_filename);

		$image_target_smi = $new_location_smi->id();
		$image_alt_smi = $z_result->field_social_media_image_alt;
		$image_title_smi = $z_result->field_social_media_image_title;
	} else {
        $image_target_smi = null;
        $image_alt_smi = null;
        $image_title_smi = null;
	}

    // If Social Media Headline exists, use that "Title: Social Media Headline", else IF Subheadline exists, use �Title: Subheadline�, else use just use D7 title alone.
    if ($z_result->field_social_hed_value){
	    $new_title = $z_result->field_social_hed_value;
    } else if ($z_result->field_subhed_value){
		$new_title = $z_result->title.': '.$z_result->field_subhed_value;
    } else {
	    $new_title = $z_result->title;
	}

    // If Summary field is blank then 300 characters copy and pasted from body section to summary field
    if ($z_result->body_summary == null || $z_result->body_summary == ''){
	   $summary_value =	trim(substr(strip_tags($z_result->body_value), 0, 300));
	   $summary_value =	preg_replace('/[^A-Za-z0-9\-]/', ' ', $summary_value);
    } else {
	   $summary_value = $z_result->body_summary;
    }
	
	// Distinct Related Media Content to Podcast, Video & Photo Gallery contents and store into separate fields
	if($z_result->field_related_media_target_id){
		$node = Node::load($z_result->field_related_media_target_id); //related media node load
		$rm_node_type = $node->bundle();
		if($rm_node_type == 'podcast'){
		     $podcast_type_nid = $z_result->field_related_media_target_id;
			 $video_type_nid = null;
			 $photo_gallery_type_nid = null;
	    } else if($rm_node_type == 'video'){
		     $podcast_type_nid = null;
			 $video_type_nid = $z_result->field_related_media_target_id;
			 $photo_gallery_type_nid = null;
	    } else if($rm_node_type == 'photo_gallery'){
		     $podcast_type_nid = null;
			 $video_type_nid = null;
			 $photo_gallery_type_nid = $z_result->field_related_media_target_id;
		}  else {
		     $podcast_type_nid = null;
			 $video_type_nid = null;
			 $photo_gallery_type_nid = null;
		}
    } else {
		$podcast_type_nid = null;
		$video_type_nid = null;
		$photo_gallery_type_nid = null;
	}
    
	$pub_date = date("Y-m-d", $z_result->created) .'T'.date("H:i:s", $z_result->created);
	


fwrite($fp, PHP_EOL."\n"."\r\n");
          fwrite($fp, "--");
          fwrite($fp, $z_result->nid);
          fwrite($fp, "--");
          fwrite($fp, PHP_EOL."\n"."\r\n");

	$node = Node::create([
          'type' => 'the_word',
		  'nid' => $z_result->nid,
		  'vid' => $z_result->vid,
          'langcode' => $z_result->language,
          'uid' => $z_result->uid,
          'status' => $z_result->STATUS,
          'created' => $z_result->created,
          'changed' => $z_result->changed,
          'title' => $new_title,
          'body' => array('summary' => $summary_value, 'value' => $z_result->body_value,  'format' => $z_result->body_format,),
          //Extra Fields
		  'field_by_author' => $z_result->field_op_author_nid,
		  'field_author_bio' => $z_result->field_author_bio_value,
		  'field_image_caption' => $z_result->field_op_caption_value,
		  'field_issue' => $z_result->field_issue_target_id,
		  'field_liturgical_date' => $z_result->field_liturgical_date_value,
		  'field_image' => ['target_id' => $image_target_mi, 'alt' => $image_alt_mi, 'title' => $image_title_mi,],
		  'field_old_d7_editors_pick' => $z_result->field_editors_pick_value,
          'field_old_d7_in_these_pages' => $z_result->field_in_these_pages_value,
		  'field_old_d7_nid' => $z_result->nid,
		  'field_old_d7_published_in_print' => $z_result->field_print_value,
          'field_old_d7_related_media' => $z_result->field_related_media_target_id,
          'field_old_d7_social_media_headli' => $z_result->field_social_hed_value,
          'field_old_d7_social_media_image' => ['target_id' => $image_target_smi, 'alt' => $image_alt_smi, 'title' => $image_title_smi,],
          'field_old_d7_subheadline' => $z_result->field_subhed_value,
		  'field_word_prayer' => $z_result->field_word_prayer_value,
		  'field_print_headline' => $z_result->title,
		  'field_publication_date' => $pub_date,
          'field_quote' => $z_result->field_word_quote_value,
		  'field_readings' => $z_result->field_readings_value,
		  'field_op_related_nref' => $z_result->field_op_related_nref_nid,
          'field_related_podcast' => $podcast_type_nid,
          'field_related_video' => $video_type_nid,
          'field_related_photo_gallery' => $photo_gallery_type_nid,
          'field_section' => $z_result->field_op_section_term_tid,
		  
   ]);
   $node->save();
   //Update Status - This record has been processed and Set - Status - 2
   $upd = \Drupal::database()->update('z_old_d7_node__the_word');
          $upd->fields(['move_status' =>2,]);
          $upd->condition('nid', $z_result->nid, '=');
          $upd->execute();
  }//end for loop


      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "END-------------".date("Y-m-d H:i:s")."-----------------").PHP_EOL;
      fclose($fp);


return array('#title' => "Content Type - The Word Migration", '#markup' => "Records has been Processed!",);
}




/**
===============================================================================================================================
===============================================================================================================================


				**************************** Migration - Comments ****************************


===============================================================================================================================
===============================================================================================================================
**/
public function comments_migration() {
  $query = \Drupal::database()->select('z_old_d7_comment_data', 'c_d');
  $query->fields('c_d', ['cid', 'pid', 'nid', 'uid_new', 'subject', 'hostname', 'created', 'changed', 'status', 'thread', 'name', 'mail', 'homepage', 'language', 'entity_type', 'bundle', 'comment_body_value', 'comment_body_format', 'move_status']);
  $query->condition('move_status', '0', '='); //This record is ready to process and Status - 0
  $query->orderBy('nid', 'ASC');
  $query->range(0, 20); 
  $z_results = $query->execute()->fetchAll();


$fp = fopen("comments.txt","a+");
      fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------");
      fwrite($fp, PHP_EOL."\n"."\r\n");


  foreach ($z_results as $z_result) {
       //Update Status - This record is inprocess and Set - Status - 1
       $upd1 = \Drupal::database()->update('z_old_d7_comment_data');
       $upd1->fields(['move_status' =>1,]);
       $upd1->condition('cid', $z_result->cid, '=');
       $upd1->execute();


fwrite($fp, PHP_EOL."\n"."\r\n");
          fwrite($fp, "--");
          fwrite($fp, $z_result->cid);
          fwrite($fp, "--");
          fwrite($fp, PHP_EOL."\n"."\r\n");


	   $values = [
              'cid' => $z_result->cid,
		      'pid' => $z_result->pid,
              'entity_type' => 'node',
              'field_name' => 'comment',
              'entity_id' => $z_result->nid,
              'uid' => $z_result->uid_new,
	          'status' => $z_result->status,
              'comment_type' => 'comment',
              'subject' => $z_result->subject,
			  'comment_body' => array('value' => $z_result->comment_body_value,  'format' => $z_result->comment_body_format,),
			  'name' => $z_result->name,
			  'mail' => $z_result->mail,
			  'created' => $z_result->created,
			  'changed' => $z_result->changed,
		      'thread' => $z_result->thread,
             ];
       $comment = Comment::create($values); // This will create an actual comment entity out of our field values.
       $comment->save();

       //Update actual Host IP address
	   $upd_host = \Drupal::database()->update('comment_field_data');
       $upd_host->fields(['hostname' => $z_result->hostname,]);
       $upd_host->condition('cid', $z_result->cid, '=');
       $upd_host->execute();


       //Update actual Comment Statistics
       $s_query = \Drupal::database()->select('z_old_d7_comment_statistics', 'cs');
       $s_query->fields('cs', ['nid', 'cid', 'last_comment_timestamp', 'last_comment_name', 'uid_new', 'comment_count']);
       $s_query->condition('nid', $z_result->nid, '=');
       $s_results = $s_query->execute()->fetchAll();

	   $upd_cs = \Drupal::database()->update('comment_entity_statistics');
       $upd_cs->fields(['cid' => $s_results[0]->cid, 'last_comment_timestamp' => $s_results[0]->last_comment_timestamp, 'last_comment_name' => $s_results[0]->last_comment_name, 'last_comment_uid' => $s_results[0]->uid_new, 'comment_count' => $s_results[0]->comment_count,]);
       $upd_cs->condition('entity_id', $s_results[0]->nid, '=');
       $upd_cs->execute();

	   //Update Status - This record has been processed and Set - Status - 2
       $upd = \Drupal::database()->update('z_old_d7_comment_data');
       $upd->fields(['move_status' =>2,]);
       $upd->condition('cid', $z_result->cid, '=');
       $upd->execute();
  }//end for loop

fwrite($fp, PHP_EOL."\n"."\r\n");
      fwrite($fp, "END-------------".date("Y-m-d H:i:s")."-----------------").PHP_EOL;
      fclose($fp);


  return array('#title' => "Comments - Migration", '#markup' => "This is comment section",);
}








/**
===============================================================================================================================
===============================================================================================================================


				**************************** Deepesh Custom Work ****************************


===============================================================================================================================
===============================================================================================================================
**/
public function deeptemp_work() {
  // $s_query = \Drupal::database()->select('z_br_body', 'zb');
  // $s_query->fields('zb', ['nid']);
  // $s_query->orderBy('nid', 'DESC');
  // $s_query->range(0, 1);
  // $s_results = $s_query->execute()->fetchAll();

  $query = \Drupal::database()->select('node__body', 'nb');
  $query->fields('nb', ['bundle', 'entity_id', 'body_value']);
  $query->condition('bundle', 'book_review', '=');
  $query->condition('nb.body_value', '%[view:book_in_review]%', 'NOT LIKE');
  //$query->condition('entity_id', $s_results[0]->nid, '>');
  //$query->condition('delta', 1, '=');
  // $query->condition('nb.entity_id', 128827, '=');
  $query->orderBy('entity_id', 'ASC');
  $query->range(0, 30);
  $z_results = $query->execute()->fetchAll();

  $fp = fopen("book_review-2-march.txt","a+");
        fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------\n");
  
  // $placeholder1 = '';//'<p>[view:word]</p>';
  $str_to_insert = '<p>[view:book_in_review]</p>';

  $n_count = 0;
  foreach ($z_results as $z_result) {
	  fwrite($fp, $z_result->entity_id."\n");
    // echo $z_result->entity_id;
      //=====================================================
      $output = '';
	  $content = $z_result->body_value;
    $content = str_replace("<P","<p",$content);
    $content = str_replace("</P>","</p>",$content);
    $content = str_replace("<DIV","<div",$content);
    $content = str_replace("</DIV>","</div>",$content);

    //logic
    $findp = '<p';

    // First position
    $first_pos = strpos($content, $findp);
    // Second Position
    $second_pos = strpos($content, $findp, $first_pos+1);

    $required_pos = strpos($content, $findp, $second_pos+1);
    if($required_pos){
    $output = substr_replace($content, $str_to_insert, $required_pos, 0);
    $n_count++;
    }else{
        $output = $content.$str_to_insert;
        $n_count++;
    }
    
     //===================================================
     $upd1 = \Drupal::database()->update('node__body');
     $upd1->fields(['body_value' => $output,]);
     $upd1->condition('entity_id',$z_result->entity_id);
     $upd1->execute();

     $upd2 = \Drupal::database()->update('node_revision__body');
     $upd2->fields(['body_value' => $output,]);
     $upd2->condition('entity_id',$z_result->entity_id);
     $upd2->execute();

	 // $ins=\Drupal::database()->insert('z_br_body')->fields(array('nid' => $z_result->entity_id,))->execute();
  }//end for loop

//print $no_of_para.'Body Value-'.$z_result->body_value.'<br/>--OUTPUT-'.$output;

  fwrite($fp, "Nodes Processed count-- ".$n_count."END-------------".date("Y-m-d H:i:s")."-----------------\n");
  fclose($fp);

  return array('#title' => "Deep Custom Work", '#markup' => "Done, Nodes Processed Count ".$n_count,);
}


/**
===============================================================================================================================
===============================================================================================================================


        **************************** Deepesh Custom Work ****************************


===============================================================================================================================
===============================================================================================================================
**/
public function theword_bodytoken() {
  // $s_query = \Drupal::database()->select('z_br_body', 'zb');
  // $s_query->fields('zb', ['nid']);
  // $s_query->orderBy('nid', 'DESC');
  // $s_query->range(0, 1);
  // $s_results = $s_query->execute()->fetchAll();

  $query = \Drupal::database()->select('node__body', 'nb');
  $query->fields('nb', ['bundle', 'entity_id', 'body_value']);
  $query->condition('bundle', 'the_word', '=');
  $query->condition('nb.body_value', '%[view:word]%', 'NOT LIKE');
  //$query->condition('entity_id', $s_results[0]->nid, '>');
  //$query->condition('delta', 1, '=');
  // $query->condition('nb.entity_id', 128827, '=');
  $query->orderBy('entity_id', 'ASC');
  $query->range(0, 30);
  $z_results = $query->execute()->fetchAll();

  $fp = fopen("the-word-3-march.txt","a+");
        fwrite($fp, "Start-------------".date("Y-m-d H:i:s")."-----------------\n");
  
  // $placeholder1 = '';//'<p>[view:word]</p>';
  $str_to_insert = '<p>[view:word]</p>';

  $n_count = 0;
  foreach ($z_results as $z_result) {
    fwrite($fp, $z_result->entity_id."\n");
    // echo $z_result->entity_id;
      //=====================================================
      $output = '';
    $content = $z_result->body_value;
    $content = str_replace("<P","<p",$content);
    $content = str_replace("</P>","</p>",$content);
    $content = str_replace("<DIV","<div",$content);
    $content = str_replace("</DIV>","</div>",$content);

    //logic
    $findp = '<p';

    // First position
    $first_pos = strpos($content, $findp);
    // Second Position
    $second_pos = strpos($content, $findp, $first_pos+1);

    $required_pos = strpos($content, $findp, $second_pos+1);
    if($required_pos){
    $output = substr_replace($content, $str_to_insert, $required_pos, 0);
    $n_count++;
    }else{
        $output = $content.$str_to_insert;
        $n_count++;
    }
    
     //===================================================
     $upd1 = \Drupal::database()->update('node__body');
     $upd1->fields(['body_value' => $output,]);
     $upd1->condition('entity_id',$z_result->entity_id);
     $upd1->execute();

     $upd2 = \Drupal::database()->update('node_revision__body');
     $upd2->fields(['body_value' => $output,]);
     $upd2->condition('entity_id',$z_result->entity_id);
     $upd2->execute();

   // $ins=\Drupal::database()->insert('z_br_body')->fields(array('nid' => $z_result->entity_id,))->execute();
  }//end for loop

//print $no_of_para.'Body Value-'.$z_result->body_value.'<br/>--OUTPUT-'.$output;

  fwrite($fp, "Nodes Processed count-- ".$n_count."END-------------".date("Y-m-d H:i:s")."-----------------\n");
  fclose($fp);

  return array('#title' => "Deep Custom Work", '#markup' => "Done, Nodes Processed Count ".$n_count,);
}

//===============================================================================================================================
//===============================================================================================================================
} //Class End

/*	Delete User Account
	  $query = \Drupal::database()->select('users', 'u');
      $query->fields('u', ['uid']);
	  $query->condition('uid', 1, '>');
      $query->orderBy('uid', 'ASC');
	  $query->range(0, 1500);
      $del_results = $query->execute()->fetchAll();
      foreach ($del_results as $del_result) {
		user_delete($del_result->uid);
	  }
*/	

/* Delete Node
	  $query = \Drupal::database()->select('node', 'n');
      $query->fields('n', ['nid','type']);
	  $query->condition('type', 'page', '=');
      $query->orderBy('nid', 'ASC');
      $z_results = $query->execute()->fetchAll();
      foreach ($z_results as $z_result) {
         $node_delete = node_load($z_result->nid);
		 $node_delete->delete();
	  }
*/