<?php
    namespace Glowie\Core\Tools;

    use Util;
    use Closure;
    use Glowie\Core\Element;
    use Glowie\Core\Exception\FileException;

    /**
     * File upload helper for Glowie application.
     * @category File uploads
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    class Uploader{

        /**
         * Upload status success code.
         * @var int
         */
        public const ERR_UPLOAD_SUCCESS = 0;

        /**
         * Maximum file size (from php.ini `upload_max_filesize` directive) exceeded error code.
         * @var int
         */
        public const ERR_MAX_INI_SIZE_EXCEEDED = 1;

        /**
         * Maximum file size exceeded error code.
         * @var int
         */
        public const ERR_MAX_SIZE_EXCEEDED = 2;

        /**
         * Partial upload error code.
         * @var int
         */
        public const ERR_PARTIAL_UPLOAD = 3;

        /**
         * File not selected error code.
         * @var int
         */
        public const ERR_FILE_NOT_SELECTED = 4;


        /**
         * No temporary directory error code.
         * @var int
         */
        public const ERR_NO_TMP_DIR = 6;

        /**
         * No writing permissions error code.
         * @var int
         */
        public const ERR_NO_WRITE_PERMISSIONS = 7;

        /**
         * Extension cancelled upload error code.
         * @var int
         */
        public const ERR_EXTENSION = 8;

        /**
         * Upload status error code.
         * @var int
         */
        public const ERR_UPLOAD_ERROR = 9;

        /**
         * Extension not allowed error code.
         * @var int
         */
        public const ERR_EXTENSION_NOT_ALLOWED = 10;

        /**
         * Target directory.
         * @var string
         */
        private $directory = 'uploads';

        /**
         * Upload errors.
         * @var int|array
         */
        private $errors = 0;

        /**
         * Allowed extensions.
         * @var array
         */
        private $extensions = [];

        /**
         * Unallowed extensions.
         * @var array
         */
        private $blockedExtensions = ['php', 'html'];

        /**
         * Allowed mime types.
         * @var array
         */
        private $mimes = [];

        /**
         * Unallowed mime types.
         * @var array
         */
        private $blockedMimes = ['application/x-httpd-php', 'text/html'];

        /**
         * Maximum allowed file size.
         * @var float
         */
        private $maxFileSize = 2;

        /**
         * Custom naming handler function.
         * @var Closure|null
         */
        private $namingHandler = null;

        /**
         * Creates a new file uploader instance.
         * @param string $directory (Optional) Target directory to store the uploaded files. Must be an existing directory with write permissions,\
         * absolute path or relative to the **app/public** folder.
         */
        public function __construct(string $directory = 'uploads'){
            $this->setDirectory($directory);
        }

        /**
         * Sets the target directory to store the uploaded files. Must be an existing directory with write permissions.
         * @param string $directory Directory location to store files (absolute path or relative to the **app/public** folder).
         * @return Uploader Current Uploader instance for nested calls.
         */
        public function setDirectory(string $directory){
            $this->directory = rtrim($directory, '/\\');
            return $this;
        }

        /**
         * Sets the allowed extensions that the uploader will accept.
         * @param array $extensions Array of allowed file extensions. Use an empty array to allow any extension.
         * @return Uploader Current Uploader instance for nested calls.
         */
        public function setExtensions(array $extensions){
            $this->extensions = $extensions;
            return $this;
        }

        /**
         * Sets the extensions that the uploader will NOT accept.
         * @param array $extensions Array of blocked file extensions. Use an empty array to don't block any.
         * @return Uploader Current Uploader instance for nested calls.
         */
        public function setBlockedExtensions(array $extensions){
            $this->blockedExtensions = $extensions;
            return $this;
        }

        /**
         * Sets the maximum file size that the uploader will accept.
         * @param float $maxFileSize Maximum allowed file size **in megabytes**. Use `0` for unlimited (not recommended).\
         * **Important:** This setting cannot be higher than your php.ini `upload_max_filesize` directive.
         * @return Uploader Current Uploader instance for nested calls.
         */
        public function setMaxFileSize(float $maxFileSize){
            $this->maxFileSize = $maxFileSize;
            return $this;
        }

        /**
         * Sets the allowed mime types that the uploader will accept.
         * @param array $mimes Array of allowed mime types. Use an empty array to allow any.\
         * This also accepts wildcard mimes, like `image/*`.
         * @return Uploader Current Uploader instance for nested calls.
         */
        public function setMimes(array $mimes){
            $this->mimes = $mimes;
            return $this;
        }

        /**
         * Sets the mime types that the uploader will NOT accept.
         * @param array $mimes Array of blocked mime types. Use an empty array to don't block any.
         * @return Uploader Current Uploader instance for nested calls.
         */
        public function setBlockedMimes(array $mimes){
            $this->blockedMimes = $mimes;
            return $this;
        }

        /**
         * Sets a custom naming handler function for generating filenames.
         * @param Closure|null $callback A closure with the naming handler. It receives the original filename as a parameter.\
         * You can also pass `null` to use the default generator.
         * @return Uploader Current Uploader instance for nested calls.
         */
        public function setNamingHandler(?Closure $callback){
            $this->namingHandler = $callback;
            return $this;
        }

        /**
         * Returns the latest upload errors.
         * @return int|array Upload errors.
         */
        public function getErrors(){
            return $this->errors;
        }

        /**
         * Performs a single file upload.
         * @param string $input Valid file input field name.
         * @return mixed Returns the uploaded file relative URL on success or false on errors.
         */
        public function uploadSingle(string $input){
            return $this->upload($input, false, false);
        }

        /**
         * Performs one or multiple file uploads.
         * @param string $input Valid file input field name.
         * @param bool $multiple (Optional) Allow multiple uploads.
         * @param bool $deleteOnFail (Optional) Delete all uploaded files if an upload fails (only multiple uploads).
         * @return mixed Returns the uploaded file relative URL (or an array of URLs if multiple files) on success or false on errors.
         */
        public function upload(string $input, bool $multiple = true, bool $deleteOnFail = false){
            // Validate target directory
            if(!is_dir($this->directory) || !is_writable($this->directory)) throw new FileException('Directory "' . $this->directory . '" is invalid or not writable');

            // Checks for empty uploads
            if(!empty($_FILES[$input]['name'])){
                // Rearrange file array
                $files = $this->arrangeFiles($_FILES[$input]);

                // Validate empty files
                if(empty($files)){
                    $this->errors = self::ERR_FILE_NOT_SELECTED;
                    return false;
                }

                // Single file upload
                if(!$multiple){
                    return $this->processFile($files[0]);
                }else{
                    // Multiple files upload
                    $result = [];
                    $errors = [];
                    foreach($files as $key => $file){
                        $process = $this->processFile($file, $key);
                        if($process !== false){
                            $result[] = $process;
                        }else{
                            $errors[$key] = $this->errors;
                        }
                    }
                    $this->errors = $errors;
                    if(empty($this->errors)){
                        $this->errors = self::ERR_UPLOAD_SUCCESS;
                        return $result;
                    }else{
                        if($deleteOnFail){
                            foreach($result as $file) if (is_file($file)) @unlink($file);
                        }
                        return false;
                    }
                }
            }else{
                $this->errors = self::ERR_FILE_NOT_SELECTED;
                return false;
            }
        }

        /**
         * Rearrange $_FILES input array.
         * @param array $files $_FILES input array to rearrange.
         * @return array Returns the new array.
         */
        public function arrangeFiles(array $files){
            if(Util::isEmpty($files['name'])) return [];
            if(is_array($files['name'])){
                $result = [];
                for ($i=0; $i < count($files['name']); $i++) {
                    $item = [
                        'name' => Util::sanitizeFilename($files['name'][$i]),
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                        'size_string' => $this->parseSize($files['size'][$i]),
                        'extension' => $this->getExtension($files['name'][$i])
                    ];
                    $result[] = new Element($item);
                }
                return $result;
            }else {
                $files['name'] = Util::sanitizeFilename($files['name']);
                $files['extension'] = $this->getExtension($files['name']);
                $files['size_string'] = $this->parseSize($files['size']);
                return [new Element($files)];
            }
        }

        /**
         * Fetches a file upload.
         * @param Element $file Uploaded file Element.
         * @param int $key (Optional) File key in multiple files.
         * @return string|bool Returns the uploaded file relative URL on success or false on error.
         */
        private function processFile($file, int $key = 0){
            if(!empty($file->error)){
                $this->errors = $file->error;
                return false;
            }

            // Perform upload
            if ($this->checkFileSize($file->size)) {
                if ($this->checkExtension($file->extension) && $this->checkMime($file->type)) {
                    $filename = $this->generateFilename($file->name, $key);
                    $target = $this->directory . '/' . $filename;
                    if (is_uploaded_file($file->tmp_name) && @move_uploaded_file($file->tmp_name, $target)) {
                        $this->errors = self::ERR_UPLOAD_SUCCESS;
                        return $target;
                    } else {
                        $this->errors = self::ERR_UPLOAD_ERROR;
                        return false;
                    }
                } else {
                    $this->errors = self::ERR_EXTENSION_NOT_ALLOWED;
                    return false;
                }
            } else {
                $this->errors = self::ERR_MAX_SIZE_EXCEEDED;
                return false;
            }
        }

        /**
         * Checks the file extension.
         * @param string $extension File extension to check.
         * @return bool Returns true if the file extension is allowed, false otherwise.
         */
        private function checkExtension(string $extension){
            return (empty($this->extensions) || in_array($extension, $this->extensions)) && !in_array($extension, $this->blockedExtensions);
        }

        /**
         * Checks the file mime type.
         * @param string $mime Mime type to check.
         * @return bool Returns true if the mime type is allowed, false otherwise.
         */
        private function checkMime(string $mime){
            $mime = trim(strtolower($mime));
            if((empty($this->mimes) || in_array($mime, $this->mimes)) && !in_array($mime, $this->blockedMimes)) return true;

            // Check for wildcard mimes
            foreach($this->mimes as $item){
                $item = trim(strtolower($item));
                $regex = str_replace('/', '\/', $item);
                $regex = '/^' . str_replace('*', '.*', $regex) . '$/';
                if (preg_match($regex, $mime)) return true;
            }

            return false;
        }

        /**
         * Checks the file size.
         * @param float $size File size to check.
         * @return bool Returns true if the file size is below maximum allowed size, false otherwise.
         */
        private function checkFileSize(float $size){
            return empty($this->maxFileSize) || ($size <= ($this->maxFileSize * 1024 * 1024));
        }

        /**
         * Parses the file size to a readable string.
         * @param float $size File size to parse.
         * @return string Returns the file size in a human-readable way.
         */
        private function parseSize(float $size){
            if ($size >= 1073741824){
                $size = number_format($size / 1073741824, 2) . ' GB';
            }else if ($size >= 1048576){
                $size = number_format($size / 1048576, 2) . ' MB';
            }else if ($size >= 1024){
                $size = number_format($size / 1024, 2) . ' KB';
            }else if ($size > 1){
                $size = $size . ' bytes';
            }else if ($size == 1){
                $size = $size . ' byte';
            }else{
                $size = '0 bytes';
            }
            return $size;
        }

        /**
         * Returns the file extension.
         * @param string $filename Filename to get extension.
         * @return string Returns the file extension, if exists.
         */
        private function getExtension(string $filename){
            $qpos = strpos($filename, "?");
            if ($qpos !== false) $filename = substr($filename, 0, $qpos);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            return $extension;
        }

        /**
         * Generates an unique filename or uses the custom naming generator.
         * @param string $filename Original filename to parse.
         * @param int $key (Optional) File key in multiple files.
         * @return string Returns the new filename.
         */
        private function generateFilename(string $filename, int $key = 0){
            if($this->namingHandler){
                $filename = call_user_func_array($this->namingHandler, [$filename, $key]);
            }else{
                $ext = $this->getExtension($filename);
                $filename = Util::uniqueToken() . (!empty($ext) ? ('.' . $ext) : '');
            }
            return $filename;
        }

    }
?>