<?php
    namespace Glowie\Core\Tools;

    /**
     * Email sender for Glowie application.
     * @category Email
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Mailer{

        /**
         * Email sender address.
         * @var string
         */
        private $from;

        /**
         * Email recipient address.
         * @var string
         */
        private $to;

        /**
         * Custom email headers.
         * @var array
         */
        private $headers = [];

        /**
         * Creates a new email sender instance.
         * @param string|array $from The sender address. You can also specify the sender display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         * @param string|array $to The recipient address. You can also specify the recipient display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         */
        public function __construct($from, $to){
            $this->setFrom($from);
            $this->setTo($to);
        }

        /**
         * Sets the email sender address.
         * @param string|array $from The sender address. You can also specify the sender display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         */
        public function setFrom($from){
            if(is_array($from)) $from = "{$from[0]} <{$from[1]}>";
            $this->from = $from;
        }

        /**
         * Sets the email recipient address.
         * @param string|array $to The recipient address. You can also specify the recipient display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         */
        public function setTo($to){
            if(is_array($to)) $to = "{$to[0]} <{$to[1]}>";
            $this->to = $to;
        }

        /**
         * Adds a carbon copy address to the email. All addresses will receive a copy\
         * of the message and their addresses will be shown to all recipients.
         * @param string|array $address The address to add. You can also specify the recipient display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         */
        public function addCc($address){
            if(is_array($address)){
                $this->addHeader('Cc', "{$address[0]} <{$address[1]}>");
            }else{
                $this->addHeader('Cc', $address);
            }
        }

        /**
         * Adds a blinded carbon copy address to the email. All addresses will receive a copy\
         * of the message, but their addresses will not be shown to the recipients.
         * @param string|array $address The address to add. You can also specify the recipient display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         */
        public function addBcc($address){
            if(is_array($address)){
                $this->addHeader('Bcc', "{$address[0]} <{$address[1]}>");
            }else{
                $this->addHeader('Bcc', $address);
            }
        }

        /**
         * Adds a custom header to the message.
         * @param string $name Header name.
         * @param string|array $content Header content. Can be a value or an array of values.
         */
        public function addHeader(string $name, $content){
            $content = implode(', ', (array)$content);
            $this->headers[] = "{$name}: {$content}";
        }

        /**
         * Sends the email.
         * @param string $subject The email subject.
         * @param string $message The message to send.
         * @param bool $isHtml (Optional) Set to `false` if you are sending a plain text message.
         * @param int $priority (Optional) The email priority level.
         * @return bool Returns true on success or false on errors.
         */
        public function send(string $subject, string $message, bool $isHtml = true, int $priority = 1){
            // Prepares the headers
            $version = phpversion();
            $headers = "From: {$this->from}\n";
            $headers .= "Reply-To: {$this->from}\n";
            $headers .= "Return-Path: {$this->from}\n";
            $headers .= "X-Sender: {$this->from}\n";
            $headers .= "X-Mailer: PHP/{$version}\n";
            $headers .= "X-Priority: {$priority}\n";
            $headers .= "MIME-Version: 1.0\n";
            if($isHtml) $headers .= "Content-Type: text/html; charset=utf-8\n";
            if(!empty($this->headers)) $headers .= implode("\n", $this->headers);

            // Sends the email
            return mail($this->to, $subject, $message, $headers);
        }

    }

?>