<?php

namespace Drupal\user_registrationpassword\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Url;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Component\Datetime\TimeInterface;

/**
 * User registration password controller class.
 */
class RegistrationController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a UserController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(DateFormatterInterface $date_formatter, UserStorageInterface $user_storage, LoggerInterface $logger, TimeInterface $time) {
    $this->dateFormatter = $date_formatter;
    $this->userStorage = $user_storage;
    $this->logger = $logger;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('logger.factory')->get('user_registrationpassword'),
      $container->get('datetime.time')
    );
  }

  /**
   * Confirms a user account.
   *
   * @param int $uid
   *   User ID of user requesting confirmation.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the login link is for an invalid user ID.
   */
  public function confirmAccount($uid, $timestamp, $hash) {
    $current_user = $this->currentUser();

    // Verify that the user exists. For UX we do not error on
    // $current_user->isActive(), see below.
    if ($current_user === NULL) {
      throw new AccessDeniedHttpException();
    }

    // When processing the one-time login link, we have to make sure that a user
    // isn't already logged in.
    if ($current_user->isAuthenticated()) {
      // The existing user is already logged in.
      if ($current_user->id() == $uid) {
        $this->messenger()->addMessage($this->t('You are currently authenticated as user %user.', ['%user' => $current_user->getAccountName()]));
        // Redirect to user page.
        return $this->redirect('user.page', ['user' => $current_user->id()]);
      }
      // A different user is already logged in on the computer.
      else {
        /** @var \Drupal\user\UserInterface $reset_link_user */
        $reset_link_user = $this->userStorage->load($uid);
        if (!empty($reset_link_user)) {
          $this->messenger()
            ->addWarning($this->t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href=":logout">log out</a> and try using the link again.',
              [
                '%other_user' => $current_user->getAccountName(),
                '%resetting_user' => $reset_link_user->getAccountName(),
                ':logout' => Url::fromRoute('user.logout')->toString(),
              ]), 'warning');
          return $this->redirect('<front>');
        }
        else {
          // Invalid one-time link specifies an unknown user.
          $this->messenger()->addError($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'));
          return $this->redirect('user.pass');
        }
      }
    }
    else {
      // Time out, in seconds, until login URL expires. 24 hours = 86400
      // seconds.
      $timeout = $this->config('user_registrationpassword.settings')->get('registration_ftll_timeout');
      $current = $this->time->getRequestTime();
      $timestamp_created = $timestamp - $timeout;

      // Some redundant checks for extra security ?
      $users = $this->userStorage->getQuery()
        ->condition('uid', $uid)
        ->condition('status', 0)
        ->condition('access', 0)
        ->execute();

      // Timestamp can not be larger than current.
      /** @var \Drupal\user\UserInterface $account */
      if ($timestamp_created <= $current && !empty($users) && $account = $this->userStorage->load(reset($users))) {
        // Check if we have to enforce expiration for activation links.
        if ($this->config('user_registrationpassword.settings')->get('registration_ftll_expire') && !$account->getLastLoginTime() && $current - $timestamp > $timeout) {
          $this->messenger()->addError($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'));
          return $this->redirect('user.pass');
        }
        // Else try to activate the account.
        // Password = user's password - timestamp = current request - login =
        // username.
        elseif ($account->id() && $timestamp >= $account->getCreatedTime() && !$account->getLastLoginTime() && $hash == user_pass_rehash($account, $timestamp)) {
          // Format the date, so the logs are a bit more readable.
          $date = $this->dateFormatter->format($timestamp);
          $this->logger->notice('User %name used one-time login link at time %timestamp.', ['%name' => $account->getDisplayName(), '%timestamp' => $date]);

          // Activate the user and update the access and login time to $current.
          $account
            ->activate()
            ->setLastAccessTime($current)
            ->setLastLoginTime($current)
            ->save();

          // user_login_finalize() also updates the login timestamp of the
          // user, which invalidates further use of the one-time login link.
          user_login_finalize($account);

          // Display default welcome message.
          $this->messenger()->addStatus($this->t('You have just used your one-time login link. Your account is now active and you are authenticated.'));

          // By default redirect to the user account page.
          return $this->redirect('user.page', ['user' => $account->id()]);
        }
      }
    }
    $this->messenger()->addError($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'));
    return $this->redirect('user.pass');
  }

}
