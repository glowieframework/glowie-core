<?php

namespace Glowie\Core\Tools;

use Util;
use Glowie\Core\Collection;
use Glowie\Core\Element;
use Glowie\Core\Exception\FileException;

/**
 * File upload helper for Glowie application.
 * @category File uploads
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/extra/file-uploads
 */
class Uploader
{

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
    private $blockedExtensions = ['php', '.phtml', 'html'];

    /**
     * Allowed mime types.
     * @var array
     */
    private $mimes = [];

    /**
     * Unallowed mime types.
     * @var array
     */
    private $blockedMimes = ['application/x-httpd-php', 'application/php', 'application/x-php', 'text/php', 'text/x-php', 'text/html'];

    /**
     * Maximum allowed file size.
     * @var float
     */
    private $maxFileSize = 2;

    /**
     * Custom naming handler function.
     * @var callable|null
     */
    private $namingHandler = null;

    /**
     * Creates a new file uploader instance.
     * @param string $directory (Optional) Target directory to store the uploaded files. Must be an existing directory with write permissions,\
     * absolute path or relative to the **app/public** folder.
     */
    public function __construct(string $directory = 'uploads')
    {
        $this->setDirectory($directory);
    }

    /**
     * Creates a new file uploader instance in a static-binding.
     * @param string $directory (Optional) Target directory to store the uploaded files. Must be an existing directory with write permissions,\
     * absolute path or relative to the **app/public** folder.
     * @return Uploader New Uploader instance.
     */
    public static function make(string $directory = 'uploads')
    {
        return new static($directory);
    }

    /**
     * Sets the target directory to store the uploaded files. Must be an existing directory with write permissions.
     * @param string $directory Directory location to store files (absolute path or relative to the **app/public** folder).
     * @return Uploader Current Uploader instance for nested calls.
     */
    public function setDirectory(string $directory)
    {
        $this->directory = rtrim($directory, '/\\');
        return $this;
    }

    /**
     * Sets the allowed extensions that the uploader will accept.
     * @param array $extensions Array of allowed file extensions. Use an empty array to allow any extension.
     * @return Uploader Current Uploader instance for nested calls.
     */
    public function setExtensions(array $extensions)
    {
        $this->extensions = $extensions;
        return $this;
    }

    /**
     * Sets the extensions that the uploader will NOT accept.
     * @param array $extensions Array of blocked file extensions. Use an empty array to don't block any.
     * @return Uploader Current Uploader instance for nested calls.
     */
    public function setBlockedExtensions(array $extensions)
    {
        $this->blockedExtensions = $extensions;
        return $this;
    }

    /**
     * Sets the maximum file size that the uploader will accept.
     * @param float $maxFileSize Maximum allowed file size **in megabytes**. Use `0` for unlimited (not recommended).\
     * **Important:** This setting cannot be higher than your php.ini `upload_max_filesize` directive.
     * @return Uploader Current Uploader instance for nested calls.
     */
    public function setMaxFileSize(float $maxFileSize)
    {
        $this->maxFileSize = $maxFileSize;
        return $this;
    }

    /**
     * Sets the allowed mime types that the uploader will accept.
     * @param array $mimes Array of allowed mime types. Use an empty array to allow any.\
     * This also accepts wildcard mimes, like `image/*`.
     * @return Uploader Current Uploader instance for nested calls.
     */
    public function setMimes(array $mimes)
    {
        $this->mimes = $mimes;
        return $this;
    }

    /**
     * Sets the mime types that the uploader will NOT accept.
     * @param array $mimes Array of blocked mime types. Use an empty array to don't block any.
     * @return Uploader Current Uploader instance for nested calls.
     */
    public function setBlockedMimes(array $mimes)
    {
        $this->blockedMimes = $mimes;
        return $this;
    }

    /**
     * Sets a custom naming handler function for generating filenames.
     * @param callable|null $callback A function with the naming handler. It receives the original filename and the extension as the parameters.\
     * You can also pass `null` to use the default generator. It will generate random filenames.
     * @return Uploader Current Uploader instance for nested calls.
     */
    public function setNamingHandler(?callable $callback)
    {
        $this->namingHandler = $callback;
        return $this;
    }

    /**
     * Returns the latest upload errors.
     * @return int|array Upload errors.
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Performs a single file upload.
     * @param string $input Valid file input field name.
     * @return Element|bool Returns an object with the uploaded file data on success or false on error.
     */
    public function uploadSingle(string $input)
    {
        return $this->upload($input, false, false);
    }

    /**
     * Performs one or multiple file uploads.
     * @param string $input Valid file input field name.
     * @param bool $multiple (Optional) Allow multiple uploads.
     * @param bool $deleteOnFail (Optional) Delete all uploaded files if an upload fails (only multiple uploads).
     * @return mixed Returns an object with the uploaded file data (or a Collection of files on multiple uploads) on success or false on errors.
     */
    public function upload(string $input, bool $multiple = true, bool $deleteOnFail = false)
    {
        // Validate target directory
        if (!is_dir($this->directory) || !is_writable($this->directory)) {
            $e = new FileException('Directory "' . $this->directory . '" is invalid or not writable');
            $e->setSuggestion('Check if the directory exists and has writing permissions for the web server user (chmod 0755)');
            throw $e;
        }

        // Checks for empty uploads
        if (!empty($_FILES[$input]['name'])) {
            // Rearrange file array
            $files = $this->arrangeFiles($_FILES[$input]);

            // Validate empty files
            if (empty($files)) {
                $this->errors = self::ERR_FILE_NOT_SELECTED;
                return false;
            }

            // Single file upload
            if (!$multiple) {
                return $this->processFile($files[0]);
            } else {
                // Multiple files upload
                $result = [];
                $errors = [];
                foreach ($files as $key => $file) {
                    $process = $this->processFile($file, $key);
                    if ($process !== false) {
                        $result[] = $process;
                    } else {
                        $errors[$key] = $this->errors;
                    }
                }
                $this->errors = $errors;
                if (empty($this->errors)) {
                    $this->errors = self::ERR_UPLOAD_SUCCESS;
                    return new Collection($result);
                } else {
                    if ($deleteOnFail) {
                        foreach ($result as $file) {
                            if (is_file($file->path)) @unlink($file->path);
                        }
                    }
                    return false;
                }
            }
        } else {
            $this->errors = self::ERR_FILE_NOT_SELECTED;
            return false;
        }
    }

    /**
     * Rearrange the `$_FILES` input array.
     * @param array $files `$_FILES` input array to rearrange.
     * @return array Returns the new array.
     */
    public function arrangeFiles(array $files)
    {
        // Checks for empty uploads
        if (Util::isEmpty($files['name'])) return [];

        // Multiple files upload
        if (is_array($files['name'])) {
            return array_map(function ($i) use ($files) {
                $item = [
                    'name' => Util::sanitizeFilename($files['name'][$i]),
                    'type' => mime_content_type($files['tmp_name'][$i]) || $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                    'size_string' => $this->parseSize($files['size'][$i]),
                    'extension' => $this->getExtension($files['name'][$i]),
                ];
                return new Element($item);
            }, array_keys($files['name']));
        }

        // Single file upload
        $files['name'] = Util::sanitizeFilename($files['name']);
        $files['type'] = mime_content_type($files['tmp_name']) || $files['type'];
        $files['extension'] = $this->getExtension($files['name']);
        $files['size_string'] = $this->parseSize($files['size']);
        return [new Element($files)];
    }

    /**
     * Fetches a file upload.
     * @param Element $file Uploaded file Element.
     * @param int $key (Optional) File key in multiple files.
     * @return Element|false Returns an object with the uploaded file data on success or false on error.
     */
    private function processFile($file, int $key = 0)
    {
        if (!empty($file->error)) {
            $this->errors = $file->error;
            return false;
        }

        // Perform upload
        if ($this->checkFileSize($file->size)) {
            if ($this->checkExtension(mb_strtolower($file->extension)) && $this->checkMime($file->type)) {
                $filename = $this->generateFilename($file->name, $key);
                $target = $this->directory . '/' . $filename;
                if (is_uploaded_file($file->tmp_name) && @move_uploaded_file($file->tmp_name, $target)) {
                    $this->errors = self::ERR_UPLOAD_SUCCESS;
                    return new Element([
                        'name' => $filename,
                        'url' => $target,
                        'full_url' => Util::baseUrl($target),
                        'path' => realpath($target),
                        'original_name' => $file->name,
                        'type' => $file->type,
                        'size' => $file->size,
                        'size_string' => $file->size_string,
                        'extension' => $file->extension
                    ]);
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
    private function checkExtension(string $extension)
    {
        return (empty($this->extensions) || in_array($extension, $this->extensions)) && !in_array($extension, $this->blockedExtensions);
    }

    /**
     * Checks the file mime type.
     * @param string $mime Mime type to check.
     * @return bool Returns true if the mime type is allowed, false otherwise.
     */
    private function checkMime(string $mime)
    {
        // Check for exact match
        $mime = trim(mb_strtolower($mime));
        if ((empty($this->mimes) || in_array($mime, $this->mimes)) && !in_array($mime, $this->blockedMimes)) return true;

        // Check for wildcard mimes
        foreach ($this->mimes as $item) {
            $item = trim(mb_strtolower($item));
            $regex = '/^' . str_replace('\*', '.*', preg_quote($item, '/')) . '$/i';
            if (preg_match($regex, $mime)) return true;
        }

        // Return false on unmatched mime
        return false;
    }

    /**
     * Checks the file size.
     * @param float $size File size to check.
     * @return bool Returns true if the file size is below maximum allowed size, false otherwise.
     */
    private function checkFileSize(float $size)
    {
        return empty($this->maxFileSize) || ($size <= ($this->maxFileSize * 1024 * 1024));
    }

    /**
     * Parses the file size to a readable string.
     * @param float $size File size to parse.
     * @return string Returns the file size in a human-readable way.
     */
    private function parseSize(float $size)
    {
        if ($size >= 1073741824) {
            $size = number_format($size / 1073741824, 2) . ' GB';
        } else if ($size >= 1048576) {
            $size = number_format($size / 1048576, 2) . ' MB';
        } else if ($size >= 1024) {
            $size = number_format($size / 1024, 2) . ' KB';
        } else if ($size > 1) {
            $size = $size . ' bytes';
        } else if ($size === 1) {
            $size = $size . ' byte';
        } else {
            $size = '0 bytes';
        }
        return $size;
    }

    /**
     * Returns the file extension.
     * @param string $filename Filename to get extension.
     * @return string Returns the file extension, if exists.
     */
    private function getExtension(string $filename)
    {
        $qpos = mb_strpos($filename, "?");
        if ($qpos !== false) $filename = mb_substr($filename, 0, $qpos);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return $extension;
    }

    /**
     * Generates an unique filename or uses the custom naming generator.
     * @param string $filename Original filename to parse.
     * @param int $key (Optional) File key in multiple files.
     * @return string Returns the new filename.
     */
    private function generateFilename(string $filename, int $key = 0)
    {
        $ext = $this->getExtension($filename);
        if (!is_null($this->namingHandler)) {
            $filename = call_user_func_array($this->namingHandler, [$filename, $ext, $key]);
        } else {
            $filename = Util::uniqueToken();
            if (!Util::isEmpty($ext)) $filename .= ".$ext";
        }
        return $filename;
    }
}
