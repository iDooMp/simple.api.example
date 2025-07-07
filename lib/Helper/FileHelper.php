<?php
declare(strict_types=1);

namespace TwoQuick\Api\Helper;

use Bitrix\Main\Application;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\FileTable;
use Bitrix\Main\IO\File as IOFile;
use TwoQuick\Api\Entity\File;
use TwoQuick\Api\Entity\Image;
use Exception;

/**
 * Класс-помощник по работе с файлами
 */
class FileHelper
{
    public const SIZE_SMALL = 'SMALL';
    public const SIZE_MEDIUM = 'MEDIUM';
    public const SIZE_BIG = 'BIG';

    /**
     * @return string
     */
    public static function getSrc($id): ?string
    {
        return \CFile::GetPath($id) ?: null;
    }

    /**
     * @param string $id
     * @return array
     */
    public static function getFileArray(int $id): ?array
    {
        return \CFile::GetFileArray($id) ?: null;
    }

    /**
     * Получаем объект картинки
     *
     * @param int|null $imageId
     * @param string $alt Если нет DESCRIPTION у картинки, используем $alt
     * @return Image|null
     */
    public static function getImage(
        ?int $imageId,
        string $alt = '',
    ): ?Image {
        if (empty($imageId)) {
            return null;
        }

        $filePath = \CFile::GetPath($imageId);

        if (!$filePath) {
            AddMessage2Log('Изображение с ID=' . $imageId . ' не найдено' . "\n");
            return null;
        }

        $filePathOld =$filePath;
        $filePath = self::generateWebp($filePath);
        $originalPath = Application::getDocumentRoot() . $filePath;
        $file = \CFile::GetFileArray($imageId);
        if (!IOFile::isFileExists($originalPath) || !$file) {
            $filePath = $filePathOld;
        }

        return new Image(
            $filePath,
            $file['DESCRIPTION'] ?: $alt,
            $filePathOld
        );
    }

    /**
     * Получить описание файла
     * @param int $fileId
     * @return string|null
     */
    public static function getFileDecription(int $fileId): ?string
    {
        return FileTable::getByPrimary($fileId, ['select' => ['DESCRIPTION']])->fetch()['DESCRIPTION'] ?: null;
    }

    /**
     * Получаем url уменьшенной картинки
     * @param int $imageId
     * @param int $width
     * @param int $height
     * @return Image
     */
    public static function getCropImageUrl(int $imageId, int $width, int $height, $type = BX_RESIZE_IMAGE_PROPORTIONAL): Image
    {
        $filePath =  StringHelper::convertWhitespaceUri(
            \CFile::ResizeImageGet(
                $imageId,
                ['width' => $width, 'height' => $height],
                $type
            )['src']
        );

        $filePathOld =$filePath;
        $filePath = self::generateWebp($filePath);
        $originalPath = Application::getDocumentRoot() . $filePath;
        $file = \CFile::GetFileArray($imageId);
        if (!IOFile::isFileExists($originalPath) || !$file) {
            $filePath = $filePathOld;
        }

        return new Image(
            $filePath,
            $file['DESCRIPTION'] ?: '',
            \CFile::GetPath($imageId)
        );
    }

    /**
     * Получаем объект файла
     * @param int|null $imageId
     * @param bool $withOriginalName - позволяет выбрать между original_name и фактическим именем файла для добавления в параметр title
     * @param bool $withHostName - позваляет задать вид ссылки с именем хоста или относительная от корня сайта, по-умолчанию возвращается полный путь
     * @return File|null
     */
    public static function getFile(?int $fileId, bool $withOriginalName = false, bool $withHostName = true): ?File
    {
        if ($fileId && $file = \CFile::GetFileArray($fileId)) {

            if(!empty($file['SRC'])) {
                $file['SRC'] = self::generateWebp($file['SRC']);
            }
            return new File(
                !$withOriginalName ? $file['FILE_NAME'] : $file['ORIGINAL_NAME'],
                !$withHostName ? $file['SRC'] : $file['SRC'],
                $file['FILE_SIZE'],
                $file['CONTENT_TYPE']
            );
        }

        return null;
    }

    /**
     * добавляет текущее имя хоста апи для путей к файлам в вёрстке передаваемой в параметре html в виде строки,
     * которые находятся в папке upload
     * @param string $html
     * @return string|null
     */
    public static function getPreparedSrcHtml(string $html): ?string
    {
        $html = (string)preg_replace(
            '/src="(\/upload[^\s>]*)"/i',
            'src="$1"',
            $html
        );
        return $html;
    }

    /**
     * Проверяет существование файла по заданному пути
     * @param string $path
     * @return bool
     */
    public static function isExists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Генерация Webp изображения из простого изображения
     *
     * @param string $fileSrc
     *
     * @return string
     */
    public static function generateWebp(string $fileSrc): string
    {
        if (empty($fileSrc)) {
            return '';
        }
        $fileSrc = str_replace('%20', ' ', $fileSrc);

        $pathInfo = pathinfo($fileSrc);
        if (mb_strtolower($pathInfo['extension']) === 'gif') {
            return '';
        } elseif (mb_strtolower($pathInfo['extension']) === 'webp') {
            return $fileSrc;
        }

        // Сокращение пути
        $dirname = str_replace('/upload/', '', $pathInfo['dirname']);
        $dirname = str_replace('resize_cache/', '', $dirname);
        $dirname = str_replace('iblock/', '', $dirname);

        $dirPath = '/upload/webp/' . $dirname . '/';
        $dirPath = str_replace('//', '/', $dirPath);

        $fileName = str_replace(' ', '_', $pathInfo['filename']);
        $fileName = str_replace('%20', '_', $fileName);

        $filePath = $dirPath . $fileName . '.webp';
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $dirPath;
        $fullPath = str_replace('//', '/', $fullPath);

        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $filePath) || filesize($_SERVER['DOCUMENT_ROOT'] . $filePath) == 0) {
            try {
                switch ($pathInfo['extension']) {
                    case 'png':
                        $gdImage = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'] . $fileSrc);
                        if (!empty($gdImage)) {
                            imagepalettetotruecolor($gdImage);
                            imagealphablending($gdImage, true);
                            imagesavealpha($gdImage, true);
                        }
                        break;
                    case 'jpg':
                    case 'jpeg':
                        $gdImage = imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'] . $fileSrc);
                        break;
                }

                mkdir($fullPath, BX_DIR_PERMISSIONS, true);
                if (!empty($gdImage)) {
                    imagewebp($gdImage, $_SERVER['DOCUMENT_ROOT'] . $filePath);
                }
            } catch (Exception $ex) {
                AddMessage2Log('Проблема с генерацией webp: ' . $ex->getMessage());
                return '';
            }
        }
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $filePath) && filesize($_SERVER['DOCUMENT_ROOT'] . $filePath) != 0) {
            return $filePath;
        } else {
            return '';
        }
    }
}
