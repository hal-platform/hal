<?php
# lib/QL/Hal/Mail/Message.php

namespace QL\Hal\Mail;

use Swift_Message;

/**
 *  HAL Email Message
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class Message extends Swift_Message
{
    const FROM          = 'hal@quickenloans.com';
    const FROM_NAME     = 'HAL9000';
    const REPLY_TO      = 'hal@quickenloans.com';

    private $repo       = '';
    private $env        = '';
    private $server     = '';
    private $pusher     = '';
    private $status     = '';
    private $notifyOnError;

    /**
     *  Constructor
     *
     *  @param string $subject The subject template string
     *  @param array $notify An array of addresses to notify
     *  @param array $notifyOnError An Array of addresses to notify on error
     */
    public function __construct(
        $subject,
        array $notify = [],
        array $notifyOnError = []
    ) {
        parent::__construct(null, null, 'text/html');
        $this->setSubject($subject);
        $this->setFrom(self::FROM, self::FROM_NAME);
        $this->setReplyTo(self::REPLY_TO);

        foreach ($notify as $address) {
            if ($address) {
                $this->addCc($address);
            }
        }

        $this->notifyOnError = $notifyOnError;
    }

    /**
     *  Add build details
     *
     *  @param string $email
     *  @param string $repo
     *  @param string $env
     *  @param string $server
     *  @param string $pusher
     */
    public function setBuildDetails($email, $repo, $env, $server, $pusher)
    {
        $this->repo = $repo;
        $this->env = $env;
        $this->server = $server;
        $this->pusher = $pusher;

        $this->setTo($email);
    }

    /**
     *  Add build result
     *
     *  @param string|bool $status
     */
    public function setBuildResult($status)
    {
        $this->status = $status;

        if (!$status) {
            foreach ($this->notifyOnError as $address) {
                if ($address) {
                    $this->addCc($address);
                }
            }
        }

        $this->setSubject($this->prepareSubject());
    }

    /**
     *  Get the formatted subject string
     *
     *  @return mixed|string
     */
    private function prepareSubject()
    {
        $replacements = array(
            '{repo}'    => $this->repo,
            '{env}'     => $this->env,
            '{server}'  => $this->server,
            '{pusher}'  => $this->pusher,
            '{status}'  => ($this->status) ? 'SUCCESS' : 'FAILURE'
        );

        $subject = parent::getSubject();

        foreach ($replacements as $key => $value) {
            $subject = str_replace($key, $value, $subject);
        }

        return $subject;
    }
}
