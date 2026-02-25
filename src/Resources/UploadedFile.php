<?php

namespace Glowie\Core\Resources;

use Glowie\Core\Element;
use Glowie\Core\Exception\FileException;
use Util;

/**
 * Resource to abstract uploaded files.
 * @category Resource
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class UploadedFile extends Element
{

    public function getName(bool $original = false)
    {
        return $original ? $this->get('original_name', $this->name) : $this->name;
    }

    public function getURL(bool $full = false)
    {
        return $full ? $this->full_url : $this->url;
    }

    /**
     * Checks if the file mimetype matches.
     * @param string|array $mime Mimetype to check. You can also use an array of mimetypes.\
     * This also accepts wildcard mimes, like `image/*`.
     * @return bool Returns true if the mimetype matches or false otherwise.
     */
    public function isMime($mime)
    {
        // Checks if the file mimetype is set
        $fileType = trim(mb_strtolower($this->type));
        if (empty($fileType)) return false;

        // Normalizes the mimetypes
        $mime = is_array($mime) ? $mime : [$mime];
        $mime = array_map(fn($m) => trim(mb_strtolower($m)), $mime);

        // Checks if the mimetype matches exactly
        if (in_array($fileType, $mime)) return true;

        // Check for wildcard mimes
        foreach ($mime as $item) {
            if (!Util::stringContains($item, '*')) continue;
            $regex = '/^' . str_replace('\*', '.*', preg_quote($item, '/')) . '$/i';
            if (preg_match($regex, $fileType)) return true;
        }

        // Return false on unmatched mime
        return false;
    }

    /**
     * Gets the raw contents of the file.
     * @return string|bool Returns the contents as string on success or false on failure.
     * @throws FileException Throws an exception if the file does not exist.
     */
    public function getContents()
    {
        return file_get_contents($this->getLocation());
    }

    /**
     * Gets a preview of the file in base64.
     * @return string|bool Returns the file preview as string on success or false on failure.
     * @throws FileException Throws an exception if the file does not exist.
     */
    public function getPreview()
    {
        $content = $this->getContents();
        if ($content === false) return false;
        return 'data: ' . $this->type . ';base64,' . base64_encode($content);
    }

    /**
     * Gets the dimensions of an image file.
     * @return array|bool Returns an array with the width and height of the image, false otherwise.
     * @throws FileException Throws an exception if the file does not exist.
     */
    public function getDimensions()
    {
        $dimensions = @getimagesize($this->getLocation());
        if (!$dimensions) return false;
        return [$dimensions[0], $dimensions[1]];
    }

    /**
     * Moves the file to another directory.
     * @param string $destination Target directory to move the file to.
     * @param string|null $filename (Optional) Filename to set, leave empty to keep the current name.
     * @return bool Returns true on success, false on failure.
     * @throws FileException Throws an exception if the file does not exist.
     */
    public function move(string $destination, ?string $filename =  null)
    {
        // Gets the file path and parses the filename
        $path = $this->getLocation();
        if (empty($filename)) $filename = pathinfo($path, PATHINFO_BASENAME);

        // Checks if the target directory exists and is writable
        if (!is_dir($destination)) mkdir($destination, 0755, true);
        if (!is_writable($destination)) {
            $e = new FileException('Directory "' . $destination . '" is not writable, please check your chmod settings');
            $e->setSuggestion('Check if the directory exists and has writing permissions for the web server user (chmod 0755)');
            throw $e;
        }

        // Tries to rename the file
        $destination = rtrim($destination, '/') . '/' . $filename;
        if (!rename($path, $destination)) return false;

        // Updates the paths
        $this->remove(['tmp_name', 'url', 'full_url']);
        $this->path = $destination;
        $this->name = $filename;
        return true;
    }

    /**
     * Copies the file to another directory.
     * @param string $destination Target directory to copy the file to.
     * @param string|null $filename (Optional) Filename to set, leave empty to keep the current name.
     * @return UploadedFile|bool Returns a new file object on success, false on failure.
     * @throws FileException Throws an exception if the file does not exist.
     */
    public function copy(string $destination, ?string $filename = null)
    {
        // Gets the file path and parses the filename
        $path = $this->getLocation();
        if (empty($filename)) $filename = pathinfo($path, PATHINFO_BASENAME);

        // Checks if the target directory exists and is writable
        if (!is_dir($destination)) mkdir($destination, 0755, true);
        if (!is_writable($destination)) {
            $e = new FileException('Directory "' . $destination . '" is not writable, please check your chmod settings');
            $e->setSuggestion('Check if the directory exists and has writing permissions for the web server user (chmod 0755)');
            throw $e;
        }

        // Tries to copy the file
        $destination = rtrim($destination, '/') . '/' . $filename;
        if (!copy($path, $destination)) return false;

        // Creates the new instance of the file with the new paths
        $newFile = clone $this;
        $newFile->remove(['tmp_name', 'url', 'full_url']);
        $newFile->path = $destination;
        $newFile->name = $filename;
        return $newFile;
    }

    /**
     * Deletes the file.
     * @return bool Returns true on success, false on failure.
     * @throws FileException Throws an exception if the file does not exist.
     */
    public function delete()
    {
        if (!unlink($this->getLocation())) return false;
        $this->remove(['path', 'tmp_name', 'url', 'full_url']);
        return true;
    }

    /**
     * Gets the current file location.
     * @return string Returns the file location.
     * @throws FileException Throws an exception if the file does not exist.
     */
    public function getLocation()
    {
        $path = $this->get('path', $this->tmp_name);
        if (empty($path) || !is_file($path)) throw new FileException('"' . $path . '" is not a valid file');
        return $path;
    }

    /**
     * Gets a resource parameter using a magic method.
     * @param string $method Parameter name to get.
     * @param array $args Unused.
     * @return mixed Returns the parameter if exists, null otherwise.
     */
    public function __call(string $method, array $args)
    {
        return $this->get($method);
    }
}
