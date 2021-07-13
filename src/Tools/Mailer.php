<?php
    namespace Glowie\Core\Tools;

    /**
     * Mail sender for Glowie application.
     * @category Mail
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Mailer{

        /**
         * Custom headers.
         * @var array
         */
        private $headers = [];

        /**
         * Adds a carbon copy address to the mail. All addresses will receive a copy\
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
         * Adds a blinded carbon copy address to the mail. All addresses will receive a copy\
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
         * @param string $content Header content.
         */
        public function addHeader(string $name, string $content){
            $this->headers[] = "{$name}: {$content}";
        }

        /**
         * Sends the mail.
         * @param string|array $from The sender address. You can also specify the sender display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         * @param string|array $to The recipient address. You can also specify the recipient display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         * @param string $subject The mail subject.
         * @param string $message The message to send.
         * @param int $priority (Optional) The message priority level.
         * @param bool $isHtml (Optional) Set to `false` if you are sending a plain text message.
         * @return bool Returns true on success or false on errors.
         */
        public function send($from, $to, string $subject, string $message, int $priority = 1, bool $isHtml = true){
            // Parse addresses
            if(is_array($from)) $from = "{$from[0]} <{$from[1]}>";
            if(is_array($to)) $to = "{$to[0]} <{$to[1]}>";

            // Set headers
            $version = phpversion();
            $headers = "From: {$from}\n";
            $headers .= "Reply-To: {$from}\n";
            $headers .= "Return-Path: {$from}\n";
            $headers .= "X-Sender: {$from}\n";
            $headers .= "X-Mailer: PHP/{$version}\n";
            $headers .= "X-Priority: {$priority}\n";
            $headers .= "MIME-Version: 1.0\n";
            if($isHtml) $headers .= "Content-Type: text/html; charset=utf-8\n";
            if(!empty($this->headers)) $headers .= implode("\n", $this->headers);

            // Sends the mail
            return mail($to, $subject, $message, $headers);
        }

    }

?>