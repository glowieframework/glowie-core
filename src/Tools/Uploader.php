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
     * @link https://glowie.tk
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
        private $directory;

        /**
         * Upload errors.
         * @var int|array
         */
        private $errors = 0;

        /**
         * Allowed extensions.
         * @var array
         */
        private $extensions;

        /**
         * Maximum allowed file size.
         * @var float
         */
        private $maxFileSize;

        /**
         * Overwrite existing files.
         * @var bool
         */
        private $overwrite;

        /**
         * Custom naming handler function.
         * @var Closure|null
         */
        private $namingHandler = null;

        /**
         * Creates a new file uploader instance.
         * @param string $directory (Optional) Target directory to store the uploaded files. Must be an existing directory with write permissions\
         * relative to the **app/public** folder.
         * @param array $extensions (Optional) Array of allowed file extensions. Use an empty array to allow any extension.
         * @param float $maxFileSize (Optional) Maximum allowed file size **in megabytes**. Use `0` for unlimited (not recommended).\
         * **Important:** This setting cannot be higher than your php.ini `upload_max_filesize` directive.
         * @param bool $overwrite (Optional) Overwrite existing files. If false, uploaded files will append a number to its name.
         */
        public function __construct(string $directory = 'uploads', array $extensions = [], float $maxFileSize = 2, bool $overwrite = false){
            $this->setDirectory($directory);
            $this->setExtensions($extensions);
            $this->setMaxFileSize($maxFileSize);
            $this->setOverwrite($overwrite);
        }

        /**
         * Sets the target directory to store the uploaded files. Must be an existing directory with write permissions.
         * @param string $directory Directory location to store files (absolute path or relative to the **app/public** folder).
         * @return Uploader Current Uploader instance for nested calls.
         */
        public function setDirectory(string $directory){
            $directory = trim($directory, '/\\');
            if(!is_dir($directory) || !is_writable($directory)) throw new FileException('Directory "' . Util::location($directory) . '" is invalid or not writable');
            $this->directory = $directory;
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
         * Sets if the uploader should overwrite existing files.
         * @param bool $overwrite If true, uploaded files will be overwritten. Otherwise, it will append a number to its name.
         * @return Uploader Current Uploader instance for nested calls.
         */
        public function setOverwrite(bool $overwrite){
            $this->overwrite = $overwrite;
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
         * Performs one or multiple file uploads.
         * @param string $input Valid file input field name.
         * @param bool $deleteOnFail (Optional) Delete all uploaded files if a upload fails.
         * @return mixed Returns the uploaded file URL (or an array of URLs if multiple files) on success or false on errors.
         */
        public function upload(string $input, bool $deleteOnFail = true){
            if(!empty($_FILES[$input])){
                // Rearrange file array
                $files = $this->arrangeFiles($_FILES[$input]);

                // Validate empty files
                if(empty($files)){
                    $this->errors = self::ERR_FILE_NOT_SELECTED;
                    return false;
                }

                // Single file upload
                if(count($files) == 1){
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
                            $errors[$file['name']] = $this->errors;
                        }
                    }
                    $this->errors = $errors;
                    if(empty($this->errors)){
                        $this->erorrs = self::ERR_UPLOAD_SUCCESS;
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
         * @param bool $assoc (Optional) Return files as an associative array instead of an Element.
         * @return array Returns the new array.
         */
        public function arrangeFiles(array $files, bool $assoc = true){
            if(is_array($files['name'])){
                $result = [];
                for ($i=0; $i < count($files['name']); $i++) {
                    $item = [
                        'name' => Util::sanitizeFilename($files['name'][$i]),
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                        'extension' => $this->getExtension($files['name'][$i])
                    ];
                    $result[] = $assoc ? $item : new Element($item);
                }
                return $result;
            }else{
                $files['name'] = Util::sanitizeFilename($files['name']);
                $files['extension'] = $this->getExtension($files['name']);
                return [($assoc ? $files : new Element($files))];
            }
        }

        /**
         * Fetches a file upload.
         * @param array $file Uploaded file array.
         * @param int $key (Optional) File key in multiple files.
         * @return string|bool Returns the uploaded file relative URL on success or false on error.
         */
        private function processFile(array $file, int $key = 0){
            if(!empty($file['error'])){
                $this->errors = $file['error'];
                return false;
            }

            if ($this->checkFileSize($file['size'])) {
                if ($this->checkExtension($file['extension'])) {
                    $filename = $this->generateFilename($file['name'], $key);
                    $target = $this->directory . '/' . $filename;
                    if (is_uploaded_file($file['tmp_name']) && move_uploaded_file($file['tmp_name'], $target)) {
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
            return empty($this->extensions) || in_array($extension, $this->extensions);
        }

        /**
         * Checks the file size.
         * @param float $size File size to check.
         * @return bool Returns true if the file size is below maximum allowed size, false otherwise.
         */
        private function checkFileSize(float $size){
            if($this->maxFileSize != 0){
                $max = $this->maxFileSize * 1024 * 1024;
                return $size <= $max ? true : false;
            }else{
                return true;
            }
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
         * Checks for an existing file and returns the new filename if overwrite is not enabled.
         * @param string $filename Filename to check.
         * @param int $key (Optional) File key in multiple files.
         * @return string Returns the new filename.
         */
        private function generateFilename(string $filename, int $key = 0){
            if($this->namingHandler) $filename = call_user_func_array($this->namingHandler, [$filename, $key]);
            if(!$this->overwrite){
                $ext = $this->getExtension($filename);
                $name = ($ext == '') ? $name = $filename : $name = substr($filename, 0, strlen($filename) - strlen($ext) - 1);
                $i = 1;
                $result = $filename;
                while (is_file($this->directory . '/' . $filename)) {
                    $result = $name . '_' . $i . '.' . $ext;
                    $filename = $result;
                    $i++;
                }
                return $result;
            }else{
                return $filename;
            }
        }

    }
