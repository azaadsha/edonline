<?php
namespace Drupal\edlite\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a feedback form for a session.
 * @param $sessionId
 *
 * @internal
 */
class FeedbackForm extends FormBase{

    public function buildForm(array $form, FormStateInterface $form_state, $sessionId='') {

      $feedback_value = '';
      $current_user = \Drupal::currentUser();
      $user_roles = $current_user->getRoles();
      $user_role = $user_roles[1];    
      if(!empty($sessionId)) {    
        $node    = \Drupal\node\Entity\Node::load($sessionId);
        if($user_role == 'student') {
          $feedback_value = $node->field_student_s_feedback;
        }
        elseif($user_role == 'teacher') {
          $feedback_value  = $node->field_teacher_s_feedback;
        }
      }
   
      $form['session_id'] = [
        '#type' => 'hidden',
        '#value' => $sessionId
      ]; 
     $form['feedback'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#default_value' => $feedback_value->getString(),
     ];
     $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ); 
    $form['#theme'] = 'feedback_form';
      return $form;
    }
    
  /**
  * {@inheritdoc}
  */
  public function getFormId() {
      return 'edlite_feedback_form';
    }
  
    /**
   * {@inheritdoc}
   */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      $feedback = trim($form_state->getValue('feedback'));
      $sessionId = $form_state->getValue('session_id');
      $current_user = \Drupal::currentUser();
    //dump($current_user);
      $user_roles = $current_user->getRoles();
      $user_role = $user_roles[1];
      $node    = \Drupal\node\Entity\Node::load($sessionId);
      if($user_role == 'student'){
        $node->field_student_s_feedback = $feedback;
      }
      elseif($user_role == 'teacher'){
        $node->field_teacher_s_feedback = $feedback; 
      }
     $url = Url::fromUri('internal:' . '/'.$user_role.'-sessions');
     $form_state->setRedirectUrl($url);  
     $node->save();
    \Drupal::messenger()->addStatus("Thanks for providing your valuable feedback");

  }
}