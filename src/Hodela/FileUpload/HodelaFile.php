<?php
/**
 * Bản quyền 2016 thuộc về Hodela
 *
 * Bạn được cấp phép để sử dụng, sao chép, sửa đổi và tích hợp
 * miễn phí phần mềm vào mã nguồn của bạn để kết nối và sử dụng
 * các dịch vụ, API được cung cấp bởi Hodela.
 *
 * Bất cứ phần mềm nào tích hợp với nền tảng Hodela đều phải tuân
 * thủ các Nguyên tắc và Chính sách phát triển tại: http://hodela.com/development-policy
 *
 * PHẦN MỀM NÀY ĐƯỢC CUNG CẤP KHÔNG KÈM BẢO HÀNH. TRONG BẤT CỨ
 * TRƯỜNG HỢP NÀO, TÁC GIẢ HOẶC NGƯỜI GIỮ BẢN QUYỀN PHẦN MỀM NÀY
 * KHÔNG CHỊU TRÁCH NHIỆM CHO NHỮNG KHIẾU NẠI, THIỆT HẠI MÀ VIỆC
 * SỬ DỤNG PHẦN MỀM CỦA BẠN GÂY RA.
 */

namespace Hodela\FileUpload;

use Hodela\Exceptions\HodelaSDKException;

/**
 * Class HodelaFile
 *
 * @package Hodela
 */
class HodelaFile
{
    /**
     * @var string The path to the file on the system.
     */
    protected $path;
    /**
     * @var int The maximum bytes to read. Defaults to -1 (read all the remaining buffer).
     */
    private $maxLength;
    /**
     * @var int Seek to the specified offset before reading. If this number is negative, no seeking will occur and reading will start from the current position.
     */
    private $offset;
    /**
     * @var resource The stream pointing to the file.
     */
    protected $stream;
    /**
     * Creates a new HodelaFile entity.
     *
     * @param string $filePath
     * @param int $maxLength
     * @param int $offset
     *
     * @throws HodelaSDKException
     */
    public function __construct($filePath, $maxLength = -1, $offset = -1)
    {
        $this->path = $filePath;
        $this->maxLength = $maxLength;
        $this->offset = $offset;
        $this->open();
    }
    /**
     * Closes the stream when destructed.
     */
    public function __destruct()
    {
        $this->close();
    }
    /**
     * Opens a stream for the file.
     *
     * @throws HodelaSDKException
     */
    public function open()
    {
        if (!$this->isRemoteFile($this->path) && !is_readable($this->path)) {
            throw new HodelaSDKException('Failed to create HodelaFile entity. Unable to read resource: ' . $this->path . '.');
        }
        $this->stream = fopen($this->path, 'r');
        if (!$this->stream) {
            throw new HodelaSDKException('Failed to create HodelaFile entity. Unable to open resource: ' . $this->path . '.');
        }
    }
    /**
     * Stops the file stream.
     */
    public function close()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
    /**
     * Return the contents of the file.
     *
     * @return string
     */
    public function getContents()
    {
        return stream_get_contents($this->stream, $this->maxLength, $this->offset);
    }
    /**
     * Return the name of the file.
     *
     * @return string
     */
    public function getFileName()
    {
        return basename($this->path);
    }
    /**
     * Return the path of the file.
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->path;
    }
    /**
     * Return the size of the file.
     *
     * @return int
     */
    public function getSize()
    {
        return filesize($this->path);
    }
    /**
     * Return the mimetype of the file.
     *
     * @return string
     */
    public function getMimetype()
    {
        return Mimetypes::getInstance()->fromFilename($this->path) ?: 'text/plain';
    }
    /**
     * Returns true if the path to the file is remote.
     *
     * @param string $pathToFile
     *
     * @return boolean
     */
    protected function isRemoteFile($pathToFile)
    {
        return preg_match('/^(https?|ftp):\/\/.*/', $pathToFile) === 1;
    }
}