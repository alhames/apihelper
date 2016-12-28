<?php

/*
 * This file is part of the API Helper package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
