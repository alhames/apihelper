<?php

namespace ApiHelper\Core;

/**
 * Interface ImageHostingClientInterface.
 */
interface FileHostingClientInterface
{
    /**
     * @param \SplFileInfo $file
     * @param array        $options
     *
     * @return mixed
     */
    public function uploadFile(\SplFileInfo $file, array $options = []);

    /**
     * @param       $url
     * @param array $options
     *
     * @return mixed
     */
    public function uploadFileFromUrl($url, array $options = []);

    /**
     * @param       $string
     * @param array $options
     *
     * @return mixed
     */
    public function uploadFileFromString($string, array $options = []);

    /**
     * @param       $id
     * @param array $options
     *
     * @return mixed
     */
    public function removeFile($id, array $options = []);
}
