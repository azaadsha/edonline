<?php

namespace Drupal\edlite\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Url;

/**
 * Returns responses for edlite routes.
 */
class EdliteController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }
/**
 * Get all the information of a level and pass it to the template
 * @param $levelId
 * @todo need to refactor the code
 */
public function level($levelId) {

  $session = \Drupal::request()->getSession();
  $output = [];  
  $post_req = \Drupal::request()->request->all();
  $parameters = \Drupal::routeMatch()->getParameters();
  if(isset($post_req['level'])){
    $selected_level_id = trim(strip_tags($post_req['level']));
  }
  elseif (!empty($parameters->get('levelId'))){
    $selected_level_id = $parameters->get('levelId');
  }
  elseif(!empty($session->get('level_selected'))){
    $selected_level_id = $session->get('level_selected');
  }

  $level_data = Node::load($selected_level_id);
  $course_type = !empty($level_data->get('field_course_content_type')->getString()) ? $level_data->get('field_course_content_type')->getString() : 'subjects';
  $output['level_title'] = $level_data->getTitle();
  //@todo need to make the below value configurable
  if($output['level_title']=='Pre-School'){
    return new RedirectResponse('/coming-soon');
  }
  $output['level_id'] = $selected_level_id;
  $output['level_desc'] = $level_data->get('field_level_description')->getString();
 
  // Set the key/value pair in session till session booking.
  $session->set('level_selected', $selected_level_id);
  $session->set('level_title', $level_data->getTitle());
  $bannerImageUri = isset($level_data->get('field_banner_image')->entity) ? $level_data->get('field_banner_image')->entity->createFileUrl() : null;
  $LeftImageUri = isset($level_data->get('field_left_bar_image')->entity) ? $level_data->get('field_left_bar_image')->entity->createFileUrl() : null;
  $default_banner_image_field_info = FieldConfig::loadByName('node', 'levels', 'field_banner_image');
  $default_banner_image_uuid = $default_banner_image_field_info->getSetting('default_image')['uuid'];
  $default_banner__image = \Drupal::service('entity.repository')->loadEntityByUuid('file', $default_banner_image_uuid);
  $default_banner_image_uri = $default_banner__image->createFileUrl();

  $default_left_image_field_info = FieldConfig::loadByName('node', 'levels', 'field_left_bar_image');
  $default_left_image_uuid = $default_left_image_field_info->getSetting('default_image')['uuid'];
  $default_left_image = \Drupal::service('entity.repository')->loadEntityByUuid('file', $default_left_image_uuid);
  $default_left_image_uri = $default_left_image->createFileUrl();

  $output['banner_image_path'] = !empty($bannerImageUri) ? $bannerImageUri : $default_banner_image_uri;
  $output['left_bar_image_path'] = !empty($LeftImageUri) ? $LeftImageUri : $default_left_image_uri;
  $entities = $level_data->get('field_subjects')->referencedEntities();
  $sublevel_entities = $level_data->get('field_sublevels')->referencedEntities(); 
  $subjects = [];
  foreach ($entities as  $entity) {
    $subjects[$entity->id()] = $entity->label();
  }
  $output['subjects']= $subjects;

  $sub_levels = [];
  foreach($sublevel_entities as $key => $sub_level){
    $sub_levels[$key]['id'] = $sub_level->id();
    $sub_levels[$key]['body'] = $sub_level->get('field_sub_level_description')->getValue();
    $sub_levels[$key]['title'] = $sub_level->getTitle(); 
  }

  $output['sub_levels'] = $sub_levels;
  //load the theme based on course type
    return [
      '#theme' => ($course_type=='subjects') ? 'sublevel_display' : 'sublevel_module_display',
      '#items' => $output,
      '#title' => '',
    ];
  
}
/**
 * 
 */
public function session_booking() {
	
  $post_req = \Drupal::request()->request->all();
  $session = \Drupal::request()->getSession();
  $selected_sub_level_id = isset($post_req['sub-level'])? trim(strip_tags($post_req['sub-level'])): $session->get('selected_sub_level');
  $selected_subject_id =  isset($post_req['selected-subject'])? trim(strip_tags($post_req['selected-subject'])): $session->get('selected_subject');
  $selected_level_id= isset($post_req['level-selected'])? trim(strip_tags($post_req['level-selected'])): $session->get('level_selected');
 	
  //for anonymous user and login redirect scenario
  if(empty($post_req) && empty($selected_subject_id) ) {
	    $selected_sub_level_id = \Drupal::request()->query->get('sub-level'); 
      $selected_subject_id = \Drupal::request()->query->get('selected-subject'); 
      $selected_level_id = \Drupal::request()->query->get('level-selected');
  }

  $session->set('selected_sub_level',$selected_sub_level_id);
  $session->set('selected_subject',$selected_subject_id);
	$session->set('level_selected',$selected_level_id);
		
	// Special Id's which are static
  //@todo need to keep them in configuration and load
    if($selected_subject_id==10) {
      $session->set('selected_subject_language',$post_req['language']);
      
    }
    elseif($selected_subject_id==11) {
      $session->set('selected_subject_others', $post_req['others']); 
    }
    $logged_in_user_roles = \Drupal::currentUser()->getRoles();
    $is_teacher = FALSE;
    if(in_array('teacher',$logged_in_user_roles)){
      $is_teacher = TRUE;
    }
    if (\Drupal::currentUser()->isAuthenticated() && !$is_teacher) {
		
      return [
        '#theme' =>'slot_booking',
		    '#items' => array('selected_subject'=>$selected_subject_id,'selected_sub_level'=>$selected_sub_level_id,'level_selected'=> $selected_level_id),
			'#cache' => ['max-age'=> 0]
		
      ];
    }
    elseif(\Drupal::currentUser()->isAuthenticated() && $is_teacher) {
      return new RedirectResponse('/booking-not-allowed');
    } else {
      return new RedirectResponse('/user/login/?destination='.urlencode('/slot-booking?sub-level='.$selected_sub_level_id.'&selected-subject='.$selected_subject_id.'&level-selected='.$selected_level_id));
    }
  }
/**
 * Creates a session for student based on select level,sublevel and subject
 * 
 */
  public function create_session(){
    $post_req = \Drupal::request()->request->all();
    $session = \Drupal::request()->getSession();
    $selected_sub_level_id = isset($post_req['sub-level'])? trim(strip_tags($post_req['sub-level'])): $session->get('selected_sub_level');
    $selected_subject_id =  isset($post_req['selected-subject'])? trim(strip_tags($post_req['selected-subject'])): $session->get('selected_subject');
    $selected_level_id= isset($post_req['level-selected'])? trim(strip_tags($post_req['level-selected'])): $session->get('level_selected');
    $session->set('selected_sub_level',$selected_sub_level_id);
    $session->set('selected_subject',$selected_subject_id);
    $session->set('level_selected',$selected_level_id);

  $sub_level_name = 'Module';
  if(!empty($selected_sub_level_id)){ //because for some courses there is no sublevel
    $sub_level_data = Node::load($selected_sub_level_id);
    $sub_level_name = $sub_level_data->getTitle();
  }
  $subject = Term::load($selected_subject_id);
  $subject_name = $subject->getName();
  $data = [
  'type' => 'sessions', 
  'title' => $session->get('level_title').'/'.$sub_level_name.'/'.$subject_name
  ];


  $number_of_sessions = $post_req['number_of_sessions'];
  $user_id = \Drupal::currentUser()->id();

  for($i=1; $i <= $number_of_sessions; $i++){
    if(isset($post_req['slot_date-'.$i])){

      $node = \Drupal::entityTypeManager()->getStorage('node')->create($data);
      $node->save();    
      $session_date = explode('/',$post_req['slot_date-'.$i]);
      $session_date_formatted = $session_date[1].'/'.$session_date[0].'/'.$session_date[2];
      $session_time = str_replace(['am', 'pm'], "", $post_req['radio-'.$i]);
      $node->field_session_level->target_id = $session->get('level_selected');
      $node->field_session_requested_by->target_id = $user_id;
      $node->field_session_sub_level->target_id = $session->get('selected_sub_level');
      $node->field_session_subject->target_id = $session->get('selected_subject');
      $node->field_session_time = $session_time;
      $node->field_session_date = date('Y-m-d',strtotime($session_date_formatted));
      $node->field_session_date_time = strtotime($session_date_formatted.$post_req['radio-'.$i]);

      if($session->get('selected_subject')==10) {
        $node->field_session_language = $session->get('selected_subject_language');
      }
      elseif($session->get('selected_subject')==11) {
        $node->field_session_other_subject = $session->get('selected_subject_others'); 
      }
      $node->save();

    }
  }
  $session->remove('selected_sub_level');
  $session->remove('selected_subject');
  $session->remove('level_selected');
  return new RedirectResponse('/session-creation-success');

  }
/**
 * Display the dashboard for the  loggedin student
 */
  public function student_dashboard(){

    $current_user = \Drupal::currentUser();
    $user = \Drupal\user\Entity\User::load($current_user->id());
    $output['username'] = $user->get('name')->getString();
    $output['user_id'] = $current_user->id();
    $output['mail'] = $user->get('mail')->getString();
    $output['registration_date']  = date('d-m-Y',strtotime($user->get('field_date_of_joining')->getString()));
    $output['first_name'] = $user->get('field_first_name')->getString();
    $output['last_name'] = $user->get('field_last_name')->getString();
    $output['grade_year'] = $user->get('field_grade_year')->getString();
    $output['phone'] = $user->get('field_phone')->getString();
    $output['dob'] = date('d-m-Y',strtotime($user->get('field_date_of_birth')->getString()));
	
	//echo $user->get('field_school')->getString();
    if($user->get('field_school')->getString()) {
      $output['school'] = !empty($user->field_school->entity->label())?$user->field_school->entity->label():'N/A';
    }
    if($user->get('field_polytechnic')->getString()) {
      $output['polytechnic'] = !empty($user->field_polytechnic->entity->label())?$user->field_polytechnic->entity->label():'N/A';
    }
    if($user->get('field_university')->getString()) {
      $output['university'] = !empty($user->field_university->entity->label())?$user->field_university->entity->label():'N/A';
    }
	  $output['level'] = $user->field_level->entity->label();
   
    // Get default image
    $field_info = FieldConfig::loadByName('user', 'user', 'field_profile_picture');
    $image_uuid = $field_info->getSetting('default_image')['uuid'];
    $image = \Drupal::service('entity.repository')->loadEntityByUuid('file', $image_uuid);
    $image_uri = $image->createFileUrl();
    $output['profile_picture'] = isset($user->get('field_profile_picture')->entity) ? $user->get('field_profile_picture')->entity->createFileUrl() : $image_uri;
   
    return [
      '#theme' => 'student_profile',
      '#items' => $output,
      '#title' => 'Profile',
    ];

  }
  /**
   * Display for fee structure for all the levels
  */
  public function fee_structure(){
    $levels = \Drupal::entityTypeManager() 
      ->getStorage('node') 
      ->loadByProperties(['type' => 'levels']);
    $output = [];
    foreach($levels as $level){
      $fee_image = isset($level->get('field_fee_structure_image')->entity) ? $level->get('field_fee_structure_image')->entity->createFileUrl() : null;
      $default_fee_image_field_info = FieldConfig::loadByName('node', 'levels', 'field_fee_structure_image');
      $default_fee_image_uuid = $default_fee_image_field_info->getSetting('default_image')['uuid'];
      $default_fee__image = \Drupal::service('entity.repository')->loadEntityByUuid('file', $default_fee_image_uuid);
      $default_fee_image_uri = $default_fee__image->createFileUrl();
      $output[$level->id()] = [ 'id'=>$level->id(),
                                  'title'=>$level->getTitle(),
                                  'min_fee'=>!empty($level->get('field_min_session_price')->getString())? $level->get('field_min_session_price')->getString():0,
                                  'max_fee'=>!empty($level->get('field_max_session_price')->getString())? $level->get('field_max_session_price')->getString():0,
                                  'fee_image'=>!empty($fee_image) ? $fee_image : $default_fee_image_uri
                                ];
      }
      
      return [
        '#theme' => 'fee_structure',
        '#items' => $output,
        
      ];
  }

  /**
 * Display the dashboard for the  loggedin teacher
 */
  public function teacher_dashboard() {
    
    $current_user = \Drupal::currentUser();
    $user = \Drupal\user\Entity\User::load($current_user->id());
    $output['username'] = $user->get('name')->getString();
    $output['user_id'] = $current_user->id();
    $output['mail'] = $user->get('mail')->getString();
    $output['registration_date']  = date('d-m-Y',strtotime($user->get('field_date_of_joining')->getString()));
    $output['first_name'] = $user->get('field_first_name')->getString();
    $output['last_name'] = $user->get('field_last_name')->getString();
    $output['phone'] = $user->get('field_phone')->getString();
    $output['doj'] = date('d-m-Y',strtotime($user->get('field_date_of_joining')->getString()));
    $output['department'] = $user->get('field_department')->getString();
        
    // Get default image
    $field_info = FieldConfig::loadByName('user', 'user', 'field_profile_picture');
    $image_uuid = $field_info->getSetting('default_image')['uuid'];
    $image = \Drupal::service('entity.repository')->loadEntityByUuid('file', $image_uuid);
    $image_uri = $image->createFileUrl();

    $output['profile_picture'] = isset($user->get('field_profile_picture')->entity) ? $user->get('field_profile_picture')->entity->createFileUrl() : $image_uri;
     
    return [
      '#theme' => 'teacher_profile',
      '#items' => $output,
      '#title' => 'Profile',
    ];
  }

/**
 * Display the dashboard for site admin
 */
  public function siteadmin_dashboard(){
     return new RedirectResponse('/universities');
  }
/**
 * ajax call back  for validating session conflicts
 */
  public function validate_session_slot(){

    $data = \Drupal::request()->request->all();
    $response['message'] = '';
    $slotDate = explode('/',$data['slotDate']);

    $slotdateFormatted = $slotDate[1].'/'.$slotDate[0].'/'.$slotDate[2];
    $slotTime = $data['slotTime'];
    $requestedTimeStamp = strtotime($slotdateFormatted.$slotTime);
    $user_id = \Drupal::currentUser()->id();
  
    $query = \Drupal::entityQuery('node')
    ->condition('type', 'sessions')
    ->condition('field_session_requested_by', $user_id, '=');
    $nids = $query->execute();
    $nodes = Node::loadMultiple($nids);
    
    foreach($nodes as $node){
      $session_date_time = $node->get('field_session_date_time')->getString();
      if($requestedTimeStamp == $session_date_time ) {
        $response['message']='A session is already booked on the same date and time. Please modify.';
        return new JsonResponse($response);
      }
      elseif($requestedTimeStamp > $session_date_time ) {
        $timeDiff = $requestedTimeStamp - $session_date_time;
      }
      elseif($requestedTimeStamp < $session_date_time) {
        $timeDiff =  $session_date_time - $requestedTimeStamp;
      }
      if($timeDiff < 7200){
        $response['message']='The time difference between two sessions on same date should be at least 2 hours.Please modify';
        return new JsonResponse($response);
      }

    }
    return new JsonResponse($response);
    
  }

/**
 * 
 *Render Edlite contact form  in a custom template 
 */

  public function contact_edlite(){

    $message = \Drupal::entityTypeManager()
            ->getStorage('contact_message')
            ->create(array(
            'contact_form' => 'edlite_contact_form', //ID(Machine name) of form
        ));
    $contact_form = \Drupal::service('entity.form_builder')->getForm($message);

    return [
      '#theme' => 'contact_edlite',
      '#items' => $contact_form,
     
    ];

  }

  /**
 * 
 *Render Edlite special kids contact form  in a custom template 
 */
  public function contact_edlite_special_kids(){

    $message = \Drupal::entityTypeManager()
            ->getStorage('contact_message')
            ->create(array(
            'contact_form' => 'edlite_special_kids_form', //ID(Machine name) of form
        ));
    $contact_form = \Drupal::service('entity.form_builder')->getForm($message);

    return [
      '#theme' => 'contact_edlite_special_kids',
      '#items' => $contact_form,
     
    ];

  }

/**
 * Provide clear cache option for site admin (not the drupal admin)
 */
  
  public function clear_cache(){

    drupal_flush_all_caches();
    \Drupal::service("messenger")->addStatus('All caches have been cleared successfully');
    return [];
  }

  /**
   * When student delete a session node and send an email notification to site admin
   * param $nodeId
   * session ID to delete
   * @todo need to separate the logic for deletion and notification mail
   */

  public function delete_session($nodeId){
    
    $node = Node::load($nodeId);
    
    //if deleting an upcoming session
    $session_datetime = $node->get('field_session_date_time')->value;
    $current_datetime = time();
    $time_togo = $session_datetime - $current_datetime;
  
    if($time_togo < 21600){
      $redirect =  new RedirectResponse('/session-edit-not-allowed');
      $redirect->send();
    }
    
    elseif( $session_datetime > $current_datetime){
      
      $node->setUnpublished();
      $node->save();
      $base_path = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
      $message = '<p>Dear Admin,</p>The below session has been cancelled by the student.<br><br>';
      $message.= '<p>Student Details:</strong><p>';
      $message.='Email : '. \Drupal::currentUser()->getEmail().'<br>';
      $message.='User Name : '. \Drupal::currentUser()->getDisplayName().'<br>';
      $message.='Profile Link: <a href="'.$base_path.'user/'.\Drupal::currentUser()->id().'" >'.$base_path.'user/'.\Drupal::currentUser()->id().'</a><br><br><p>&nbsp;</p>';
      $message.= '<p>Session Details:</p><br>';
      $message.='Title : '. $node->getTitle().'<br>';
      $message.='Session Link : <a href="'.$base_path.'node/'.$node->id().'/edit?destination=/session-list ">'.$base_path.'node/'.$node->id().'/edit?destination=/session-list</a>'.'<br>';
      send_session_edit_delete_mail($message,'session_delete');  
    }
    
    //Send a mail to site admin
     \Drupal::service("messenger")->addStatus('Session has been deleted successfully');
    return new RedirectResponse('/student-sessions');
    
  }
}
