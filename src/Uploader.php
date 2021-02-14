<?php
    namespace Glowie;

    /**
     * File upload helper for Glowie application.
     * @category File uploads
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.2-alpha
     */
    class Uploader{
        /**
         * Target directory.
         * @var string
         */
        private $directory;

        /**
         * Upload errors.
         * @var string|array
         */
        private $errors;

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
         * Creates an instance of a file uploader.
         * @param string $directory (Optional) Target directory to store the uploaded files. Must be an existing directory with write permissions.
         * @param array $extensions (Optional) Array of allowed file extensions. Use an empty array to allow any extension.
         * @param float $maxFileSize (Optional) Maximum allowed file size **in megabytes**. Use 0 for unlimited (not recommended).
         * @param bool $overwrite (Optional) Overwrite existing files. If false, uploaded files will append a number to its name.
         */
        public function __construct(string $directory = 'public/uploads', array $extensions = [], float $maxFileSize = 0, bool $overwrite = false){
            if(empty($directory) || trim($directory) == '') trigger_error('Uploader: $directory should not be empty');
            if(!is_dir($directory) || !is_writable($directory)) trigger_error('Uploader: Target directory is invalid or not writable');
            if(!is_array($extensions)) trigger_error('Uploader: $extensions must be an array of extensions');
            
            // Clean trailing slashes
            if(\Util::startsWith($directory, '/')) $directory = substr($directory, 1);
            if(\Util::endsWith($directory, '/')) $directory = substr($directory, 0, -1);
            $this->directory = $directory;
            
            // Store properties
            $this->extensions = $extensions;
            $this->errors = '';
            $this->maxFileSize = $maxFileSize;
            $this->overwrite = $overwrite;
        }

        /**
         * Returns the latest upload errors.
         * @return string|array Upload errors.
         */
        public function getErrors(){
            return $this->errors;
        }

        /**
         * Performs one or multiple file uploads.
         * @param string $input Valid file input field name.
         * @return mixed Returns the uploaded file URL (or an array of URLs if multiple files) on success or false on error.
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
                            $errors[$file['name']] = $this->errors;
                        }
                    }
                    $this->errors = $errors;
                    if(empty($this->errors)){
                        return $result;
                    }else{
                        foreach($result as $file) if (is_file($file)) unlink($file);
                        return false;
                    }
                }
            }else{
                $this->errors = 'FILE_NOT_SELECTED';
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
                    $target = $this->directory . '/' . $filename;
                    if (is_uploaded_file($file['tmp_name']) && move_uploaded_file($file['tmp_name'], $target)) {
                        $this->errors = '';
                        return \Util::baseUrl($target);
                    } else {
                        $this->errors = 'UPLOAD_ERROR';
                        return false;
                    }
                } else {
                    $this->errors = 'INVALID_EXTENSION';
                    return false;
                }
            } else {
                $this->errors = 'INVALID_SIZE';
                return false;
            }
        }

        /**
         * Checks the file extension.
         * @param string $filename Filename to check.
         * @return bool Returns true if the file extension is allowed.
         */
        private function checkExtension(string $filename){
            if(!empty($this->extensions)){
                return in_array($this->getExtension($filename), $this->extensions);
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
            $parts = explode('.', $filename);
            if(count($parts) == 0) return '';
            return strtolower($parts[count($parts) - 1]);
        }

        /**
         * Checks for an existing file and returns the new filename if overwrite not enabled.
         * @param string $filename Filename to check.
         * @return string Returns the new filename.
         */
        private function generateFilename(string $filename){
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