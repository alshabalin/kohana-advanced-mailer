<?php

/**
 * Mailer sends mails by templates in APPPATH . 'mailer/'
 *
 * @package   Advanced_Mailer
 * @author    Alexei Shabalin <mail@alshabalin.com>
 */
class Advanced_Mailer {

  use Advanced_View_Transport;

  protected $view;

  protected $defaults = [];

  public static function factory($mailer, $action)
  {
    $mailer_class = 'Mailer_' . $mailer;
    return new $mailer_class($action);
  }


  protected $mailer;

  protected $message;

  public function __construct($action)
  {
    $this->mailer = Swift_Mailer::newInstance(Swift_MailTransport::newInstance());

    $this->view = View::factory([
      'basedir' => 'mailer/' . substr(strtolower(get_class($this)), 7),
      'action'  => $action,
      'format'  => 'html',
    ]);

    $this->message = Swift_Message::newInstance();
  }

  public static function __callStatic($method_name, $args)
  {
    return call_user_func_array([new static($method_name), 'action_' . $method_name], $args);
  }

  protected function mail($params = [])
  {
    $params = $params + $this->defaults;

    $to          = Arr::get($params, 'to');
    $subject     = Arr::get($params, 'subject');
    $from        = Arr::get($params, 'from');
    $return_path = Arr::get($params, 'return_path');

    $this->message->setFrom($from);
    $this->message->setTo($to);
    $this->message->setSubject($subject);

    $this->view->message = $this->message;

    if ($this->view->does_exist())
    {
      $this->message->setBody($this->view->render(), 'text/html');

      $this->view->change_format('txt');
      $this->message->addPart($this->view->render(), 'text/plain');
    }
    else
    {
      $this->view->change_format('txt');
      $this->message->setBody($this->view->render(), 'text/plain');
    }

    $this->mailer->send($this->message);
  }

  protected function embed($path)
  {
    if ( ! ($path instanceof Swift_Image))
    {
      $path = Swift_Image::fromPath($path);
    }

    return $this->message->embed($path);
  }

  protected function attach($attachment)
  {
    if ( ! ($attachment instanceof Swift_Attachment))
    {
      $attachment = Swift_Attachment::fromPath($attachment);
    }

    return $this->message->attach($attachment);
  }


}
