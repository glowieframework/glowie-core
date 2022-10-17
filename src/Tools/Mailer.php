<?php
    namespace Glowie\Core\Tools;

    use Util;
    use Glowie\Core\Exception\FileException;
    use Glowie\Core\View\View;

    /**
     * Email sender for Glowie application.
     * @category Email
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://glowie.tk
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
         * Email attachments.
         * @var array
         */
        private $attachments = [];

        /**
         * Creates a new email sender instance.
         * @param string|array $from The sender address. You can also specify the sender display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         * @param string|array $to The recipient address. You can also specify the recipient display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         * @param array $headers (Optional) Custom headers to append to the message. Must be an associative array with the key\
         * being the name of the header and the value the header value (can be a string or an array of strings).
         */
        public function __construct($from, $to, array $headers = []){
            $this->setFrom($from);
            $this->setTo($to);
            foreach($headers as $key => $value) $this->addHeader($key, $value);
        }

        /**
         * Sets the email sender address.
         * @param string|array $from The sender address. You can also specify the sender display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         * @return Mailer Current Mailer instance for nested calls.
         */
        public function setFrom($from){
            if(is_array($from) && count($from) >= 2) $from = "{$from[0]} <{$from[1]}>";
            $this->from = $from;
            return $this;
        }

        /**
         * Sets the email recipient address.
         * @param string|array $to The recipient address. You can also specify the recipient display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         * @return Mailer Current Mailer instance for nested calls.
         */
        public function setTo($to){
            if(is_array($to) && count($to) >= 2) $to = "{$to[0]} <{$to[1]}>";
            $this->to = $to;
            return $this;
        }

        /**
         * Adds a carbon copy address to the email. All addresses will receive a copy\
         * of the message and their addresses will be shown to all recipients.
         * @param string|array $address The address to add. You can also specify the recipient display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         * @return Mailer Current Mailer instance for nested calls.
         */
        public function addCc($address){
            if(is_array($address) && count($address) >= 2){
                $this->addHeader('Cc', "{$address[0]} <{$address[1]}>");
            }else{
                $this->addHeader('Cc', $address);
            }
            return $this;
        }

        /**
         * Adds a blinded carbon copy address to the email. All addresses will receive a copy\
         * of the message, but their addresses will not be shown to the recipients.
         * @param string|array $address The address to add. You can also specify the recipient display name by passing an array\
         * with the following structure: `['Jane Doe', 'jane@address.com']`.
         * @return Mailer Current Mailer instance for nested calls.
         */
        public function addBcc($address){
            if(is_array($address) && count($address) >= 2){
                $this->addHeader('Bcc', "{$address[0]} <{$address[1]}>");
            }else{
                $this->addHeader('Bcc', $address);
            }
            return $this;
        }

        /**
         * Adds a custom header to the message.
         * @param string $name Header name.
         * @param string|array $content Header content. Can be a value or an array of values.
         * @return Mailer Current Mailer instance for nested calls.
         */
        public function addHeader(string $name, $content){
            $content = implode(', ', (array)$content);
            $content = str_replace(["\r\n", "\r", "\n"], '', trim($content));
            $this->headers[] = "{$name}: {$content}";
            return $this;
        }

        /**
         * Clears all headers from the message. This also cleans **Cc** and **Bcc** addresses.
         * @return Mailer Current Mailer instance for nested calls.
         */
        public function clearHeaders(){
            $this->headers = [];
            return $this;
        }

        /**
         * Adds an attachment file to the message.
         * @param string $filename File path to add, relative to the **app** folder.
         * @return Mailer Current Mailer instance for nested calls.
         */
        public function addAttachment(string $filename){
            $filename = Util::location($filename);
            if(!is_file($filename) || !is_readable($filename)) throw new FileException('File "' . $filename . '" is not a valid or readable file');
            $this->attachments[] = $filename;
            return $this;
        }

        /**
         * Clears all attachments from the message.
         * @return Mailer Current Mailer instance for nested calls.
         */
        public function clearAttachments(){
            $this->attachments = [];
            return $this;
        }

        /**
         * Sends an email using an application view as the message body.\
         * **This requires native PHP `mail()` function support.**
         * @param string $subject The email subject.
         * @param string $view View filename. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
         * @param int $priority (Optional) The email priority level.
         * @return bool Returns true on success or false on errors.
         */
        public function sendView(string $subject, string $view, array $params = [], int $priority = 1){
            $view = new View($view, $params, false);
            return $this->send($subject, $view->getContent(), true, $priority);
        }

        /**
         * Sends the email.\
         * **This requires native PHP `mail()` function support.**
         * @param string $subject The email subject.
         * @param string $message The message to send.
         * @param bool $isHtml (Optional) Set to `false` if you are sending a plain text message.
         * @param int $priority (Optional) The email priority level.
         * @return bool Returns true on success or false on errors.
         */
        public function send(string $subject, string $message, bool $isHtml = true, int $priority = 1){
            // Prepares the headers
            $version = phpversion();
            $headers = "From: {$this->from}\r\n";
            $headers .= "Reply-To: {$this->from}\r\n";
            $headers .= "Return-Path: {$this->from}\r\n";
            $headers .= "X-Sender: {$this->from}\r\n";
            $headers .= "X-Priority: {$priority}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "X-Mailer: PHP/{$version}\r\n";

            // Append custom headers
            if(!empty($this->headers)) $headers .= implode("\r\n", $this->headers);

            // Parse attachments if any
            if(!empty($this->attachments)){
                $boundary = Util::randomToken();
                $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n\r\n";

                // Create body
                $body = "--{$boundary}\r\n";
                $body .= "Content-Type: " . ($isHtml ? 'text/html' : 'text/plain') . "; charset=\"utf-8\"\r\n";
                $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                $body .= $message . "\r\n\r\n";

                foreach($this->attachments as $filename){
                    // Get the file content stream
                    $handle = fopen($filename, 'r');
                    $content = '';
                    while(!feof($handle)) $content .= fread($handle, 1024);
                    fclose($handle);
                    $encoded_content = chunk_split(base64_encode($content));
                    $type = mime_content_type($filename);
                    $filename = pathinfo($filename, PATHINFO_BASENAME);

                    // Parse body
                    $body .= "--{$boundary}\r\n";
                    $body .= "Content-Type: {$type}; name=\"{$filename}\"\r\n";
                    $body .= "Content-Transfer-Encoding: base64\r\n";
                    $body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
                    $body .= $encoded_content . "\r\n";
                }

                $body .= "--{$boundary}--";
            }else{
                // Parse only the message
                $headers .= "Content-Type: " . ($isHtml ? 'text/html' : 'text/plain') . "; charset=\"utf-8\"\r\n";
                $body = $message;
            }

            // Sends the email
            return mail($this->to, $subject, $body, $headers);
        }

    }

?>