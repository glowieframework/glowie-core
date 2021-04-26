<?php
    namespace Glowie\Core;

    use Util;

    /**
     * File upload helper for Glowie application.
     * @category File uploads
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.3-alpha
     */
    class Uploader{
        
        /**
         * Target directory.
         * @var string
         */
        private $_directory;

        /**
         * Upload errors.
         * @var string|array
         */
        private $_errors = '';

        /**
         * Allowed extensions.
         * @var array
         */
        private $_extensions;

        /**
         * Maximum allowed file size.
         * @var float
         */
        private $_maxFileSize;

        /**
         * Overwrite existing files.
         * @var bool
         */
        private $_overwrite;

        /**
         * Creates an instance of the uploader.
         * @param string $directory (Optional) Target directory to store the uploaded files. Must be an existing directory with write permissions.
         * @param array $extensions (Optional) Array of allowed file extensions. Use an empty array to allow any extension.
         * @param float $maxFileSize (Optional) Maximum allowed file size **in megabytes**. Use 0 for unlimited (not recommended).
         * @param bool $overwrite (Optional) Overwrite existing files. If false, uploaded files will append a number to its name.
         */
        public function __construct(string $directory = 'public/uploads', array $extensions = [], float $maxFileSize = 0, bool $overwrite = false){
            $this->setDirectory($directory);
            $this->setExtensions($extensions);
            $this->setMaxFileSize($maxFileSize);
            $this->setOverwrite($overwrite);
        }
        
        /**
         * Sets the target directory to store the uploaded files. Must be an existing directory with write permissions.
         * @param string $directory Directory location to store files (relative to the **app** folder).
         */
        public function setDirectory(string $directory){
            if(empty($directory) || trim($directory) == '') trigger_error('Uploader: $directory should not be empty');
            if(!is_dir($directory) || !is_writable($directory)) trigger_error('Uploader: Target directory is invalid or not writable');
            $this->_directory = trim($directory, '/');
        }
        
        /**
         * Sets the allowed extensions that the uploader will accept.
         * @param array $extensions Array of allowed file extensions. Use an empty array to allow any extension.
         */
        public function setExtensions(array $extensions){
            if(!is_array($extensions)) trigger_error('Uploader: $extensions must be an array of extensions');
            $this->_extensions = $extensions;
        }
        
        /**
         * Sets the maximum file size that the uploader will accept.
         * @param float $maxFileSize Maximum allowed file size **in megabytes**. Use 0 for unlimited (not recommended).
         */
        public function setMaxFileSize(float $maxFileSize){
            $this->_maxFileSize = $maxFileSize;
        }

        /**
         * Sets if the uploader should overwrite existing files.
         * @param bool $overwrite If true, uploaded files will be overwritten. Otherwise, it will append a number to its name.
         */
        public function setOverwrite(bool $overwrite){
            $this->_overwrite = $overwrite;
        }

        /**
         * Returns the latest upload errors.
         * @return string|array Upload errors.
         */
        public function getErrors(){
            return $this->_errors;
        }

        /**
         * Performs one or multiple file uploads.
         * @param string $input Valid file input field name.
         * @return mixed Returns the uploaded file URL (or an array of URLs if multiple files) on success or false on errors.
         */
        public function upload(string $input){
            if(!empty($_FILES[$input])){
                // Rearrange file array
                $files = $this->arrangeFiles($_FILES[$input]);

                // Single file upload
                if(count($files) == 1){
                    return $this->processFile($files[0]);
                }else{
                    // Multiple files upload
                    $result = [];
                    $errors = [];
                    foreach($files as $file){
                        $process = $this->processFile($file);
                        if($process !== false){
                            $result[] = $process;
                        }else{
                            $errors[$file['name']] = $this->_errors;
                        }
                    }
                    $this->_errors = $errors;
                    if(empty($this->_errors)){
                        return $result;
                    }else{
                        foreach($result as $file) if (is_file($file)) unlink($file);
                        return false;
                    }
                }
            }else{
                $this->_errors = 'FILE_NOT_SELECTED';
                return false;
            }
        }

        /**
         * Fetches a file upload.
         * @param array $file Uploaded file array.
         * @return string|bool Returns the uploaded file URL on success or false on error.
         */
        private function processFile(array $file){
            if ($this->checkFileSize($file['size'])) {
                if ($this->checkExtension($file['name'])) {
                    $filename = $this->generateFilename($file['name']);
                    $target = $this->_directory . '/' . $filename;
                    if (is_uploaded_file($file['tmp_name']) && move_uploaded_file($file['tmp_name'], $target)) {
                        $this->_errors = '';
                        return Util::baseUrl($target);
                    } else {
                        $this->_errors = 'UPLOAD_ERROR';
                        return false;
                    }
                } else {
                    $this->_errors = 'INVALID_EXTENSION';
                    return false;
                }
            } else {
                $this->_errors = 'INVALID_SIZE';
                return false;
            }
        }

        /**
         * Checks the file extension.
         * @param string $filename Filename to check.
         * @return bool Returns true if the file extension is allowed.
         */
        private function checkExtension(string $filename){
            if(!empty($this->_extensions)){
                return in_array($this->getExtension($filename), $this->_extensions);
            }else{
                return true;
            }
        }

        /**
         * Checks the file size.
         * @param float $size File size to check.
         * @return bool Returns true if the file size is below maximum allowed size.
         */
        private function checkFileSize(float $size){
            if($this->_maxFileSize != 0){
                $max = $this->_maxFileSize * 1024 * 1024;
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
            $parts = explode('.', $filename);
            return count($parts) != 0 ? strtolower(end($parts)) : '';
        }

        /**
         * Checks for an existing file and returns the new filename if overwrite not enabled.
         * @param string $filename Filename to check.
         * @return string Returns the new filename.
         */
        private function generateFilename(string $filename){
            if(!$this->_overwrite){
                $ext = $this->getExtension($filename);
                $name = ($ext == '') ? $name = $filename : $name = substr($filename, 0, strlen($filename) - strlen($ext) - 1);
                $i = 1;
                $result = $filename;
                while (is_file($this->_directory . '/' . $filename)) {
                    $result = $name . '_' . $i . '.' . $ext;
                    $filename = $result;
                    $i++;
                }
                return $result;
            }else{
                return $filename;
            }
        }

        /**
         * Rearrange $_FILES input array.
         * @param array $files $_FILES input array to rearrange.
         * @return array Returns the new array.
         */
        private function arrangeFiles(array $files){
            if(is_array($files['name'])){
                $result = [];
                for ($i=0; $i < count($files['name']); $i++) { 
                    $result[] = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];
                }
                return $result;
            }else{
                return [$files];
            }
        }

    }

?>