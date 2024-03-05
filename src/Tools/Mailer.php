<?php
    namespace Glowie\Core\Tools;

    use Exception;
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
     * @link https://gabrielsilva.dev.br/glowie
     */
    class Mailer{

        /**
         * Message high priority level.
         * @var int
         */
        public const PRIORITY_HIGH = 1;

        /**
         * Message normal priority level.
         * @var int
         */
        public const PRIORITY_NORMAL = 3;

        /**
         * Message low priority level.
         * @var int
         */
        public const PRIORITY_LOW = 5;

        /**
         * Email sender address.
         * @var string
         */
        private $from = '';

        /**
         * Email recipients.
         * @var array
         */
        private $to = [];

        /**
         * Email Cc recipients.
         * @var array
         */
        private $cc = [];

        /**
         * Email Bcc recipients.
         * @var array
         */
        private $bcc = [];

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
         * @param array $headers (Optional) Custom headers to append to the message. Must be an associative array with the key\
         * being the name of the header and the value the header value (can be a string or an array of strings).
         */
        public function __construct(array $headers = []){
            foreach($headers as $key => $value) $this->addHeader($key, $value);
        }

        /**
         * Sets the email sender.
         * @param string $email Sender email address.
         * @param string|null $name (Optional) Sender name.
         * @return Mailer Current Mailer instance for nested calls.
         */
        public function setFrom(string $email, ?string $name = null){
            if(!empty($name)) $email = "{$name} <{$email}>";
            $this->from = $this->sanitizeString($email);
            return $this;
        }

        /**
         * Adds an email recipient.
         * @param string $email Recipient email address.
         * @param string $name (Optional) Recipient name.
         * @return Mailer Current Mailer instance for nested calls.
         */
        public function addTo(string $email, ?string $name = null){
            if(!empty($name)) $email = "{$name} <{$email}>";
            $this->to[] = $this->sanitizeString($email);
            return $this;
        }

        /**
         * Adds a carbon copy address to the email. All addresses will receive a copy\
         * of the message and their addresses will be shown to all recipients.
         * @param string $email Carbon copy address.
         * @param string $name (Optional) Carbon copy name.
         * @return Mailer Current Mailer instance for nested calls.
         */
        public function addCc(string $email, ?string $name = null){
            if(!empty($name)) $email = "{$name} <{$email}>";
            $this->cc[] = $this->sanitizeString($email);
            return $this;
        }

        /**
         * Adds a blinded carbon copy address to the email. All addresses will receive a copy\
         * of the message, but their addresses will not be shown to the recipients.
         * @param string $email Blinded carbon copy address.
         * @param string $name (Optional) Blinded carbon copy name.
         * @return Mailer Current Mailer instance for nested calls.
         */
        public function addBcc(string $email, ?string $name = null){
            if(!empty($name)) $email = "{$name} <{$email}>";
            $this->bcc[] = $this->sanitizeString($email);
            return $this;
        }

        /**
         * Clears all recipients from the message. This clears **To**, **Cc** and **Bcc** addresses.
         * @return Mailer Current Mailer instance for nested calls.
         */
        public function clearAddresses(){
            $this->to = [];
            $this->cc = [];
            $this->bcc = [];
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
            $content = $this->sanitizeString($content);
            $this->headers[] = "{$name}: {$content}";
            return $this;
        }

        /**
         * Clears all custom headers from the message.
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
            if(!is_file($filename) || !is_readable($filename)) throw new FileException('Mail attachment "' . $filename . '" is not a valid or readable file');
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
        public function sendView(string $subject, string $view, array $params = [], int $priority = self::PRIORITY_NORMAL){
            $view = new View($view, $params);
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
        public function send(string $subject, string $message, bool $isHtml = true, int $priority = self::PRIORITY_NORMAL){
            // Check for empty address
            if(empty($this->to)) throw new Exception('Mail: "To" address cannot be empty');

            // Prepares the headers
            $version = phpversion();
            $headers = "X-Priority: {$priority}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "X-Mailer: PHP/{$version}\r\n";

            // Sets from
            if(!empty($this->from)){
                $headers .= "From: {$this->from}\r\n";
                $headers .= "Reply-To: {$this->from}\r\n";
                $headers .= "Return-Path: {$this->from}\r\n";
                $headers .= "X-Sender: {$this->from}\r\n";
            }

            // Sets recipients
            $to = implode(', ', $this->to);

            if(!empty($this->cc)){
                foreach($this->cc as $cc) $headers.= "Cc: {$cc}\r\n";
            }

            if(!empty($this->bcc)){
                foreach($this->bcc as $bcc) $headers.= "Bcc: {$bcc}\r\n";
            }

            // Append custom headers
            if(!empty($this->headers)) $headers .= implode("\r\n", $this->headers);

            // Parse attachments if any
            if(!empty($this->attachments)){
                $boundary = Util::uniqueToken();
                $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n\r\n";

                // Create body
                $body = "--{$boundary}\r\n";
                $body .= "Content-Type: " . ($isHtml ? 'text/html' : 'text/plain') . "; charset=\"utf-8\"\r\n";
                $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                $body .= wordwrap($message, 70, "\r\n") . "\r\n\r\n";

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
                $body = wordwrap($message, 70, "\r\n");
            }

            // Sends the email
            return mail($to, $subject, $body, $headers);
        }

        /**
         * Sanitizes a string removing line breaks.
         * @param string $string String to sanitize.
         * @return string Returns the sanitized string.
         */
        public function sanitizeString(string $string){
            return str_replace(["\r\n", "\r", "\n"], '', trim($string));
        }

    }

?>