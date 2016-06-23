<?php

namespace Lightning\Tools\IO;

interface FileHandlerInterface {
    /**
     * Check if a file exists.
     *
     * @param string $file
     *   The name of the file, relative to the root container.
     *
     * @return boolean
     */
    public function exists($file);

    /**
     * Get the file contents.
     *
     * @param string $file
     *   The name of the file, relative to the root container.
     *
     * @return string
     *   Data from file.
     */
    public function read($file);

    /**
     * Write data to a file.
     *
     * @param string $file
     *   The name of the file, relative to the root container.
     * @param string $contents
     *   The data for the file.
     * @param integer $offset
     *   The offset location for amending files.
     */
    public function write($file, $contents, $offset = 0);

    /**
     * Move from an uplaoded file if possible. This will only work if the
     *   storage mechanism is local. Otherwise, it should call it's own
     *   write() method.
     *
     * @param string $file
     *   The file to write.
     * @param string $temp_file
     *   The absolute location of the temporary file.
     */
    public function moveUploadedFile($file, $temp_file);

    public function delete($file);
}
