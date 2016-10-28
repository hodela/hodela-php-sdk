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

namespace Hodela\Http;

use Hodela\FileUpload\HodelaFile;

/**
 * Class RequestBodyMultipartt
 *
 * Some things copied from Guzzle
 *
 * @package Hodela
 *
 * @see https://github.com/guzzle/guzzle/blob/master/src/Post/MultipartBody.php
 */
class RequestBodyMultipart implements RequestBodyInterface
{
    /**
     * @var string The boundary.
     */
    private $boundary;
    /**
     * @var array The parameters to send with this request.
     */
    private $params;
    /**
     * @var array The files to send with this request.
     */
    private $files = [];
    /**
     * @param array  $params   The parameters to send with this request.
     * @param array  $files    The files to send with this request.
     * @param string $boundary Provide a specific boundary.
     */
    public function __construct(array $params = [], array $files = [], $boundary = null)
    {
        $this->params = $params;
        $this->files = $files;
        $this->boundary = $boundary ?: uniqid();
    }
    /**
     * @inheritdoc
     */
    public function getBody()
    {
        $body = '';
        // Compile normal params
        $params = $this->getNestedParams($this->params);
        foreach ($params as $k => $v) {
            $body .= $this->getParamString($k, $v);
        }
        // Compile files
        foreach ($this->files as $k => $v) {
            $body .= $this->getFileString($k, $v);
        }
        // Peace out
        $body .= "--{$this->boundary}--\r\n";
        return $body;
    }
    /**
     * Get the boundary
     *
     * @return string
     */
    public function getBoundary()
    {
        return $this->boundary;
    }
    /**
     * Get the string needed to transfer a file.
     *
     * @param string       $name
     * @param HodelaFile $file
     *
     * @return string
     */
    private function getFileString($name, HodelaFile $file)
    {
        return sprintf(
            "--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"%s\r\n\r\n%s\r\n",
            $this->boundary,
            $name,
            $file->getFileName(),
            $this->getFileHeaders($file),
            $file->getContents()
        );
    }
    /**
     * Get the string needed to transfer a POST field.
     *
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    private function getParamString($name, $value)
    {
        return sprintf(
            "--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n%s\r\n",
            $this->boundary,
            $name,
            $value
        );
    }
    /**
     * Returns the params as an array of nested params.
     *
     * @param array $params
     *
     * @return array
     */
    private function getNestedParams(array $params)
    {
        $query = http_build_query($params, null, '&');
        $params = explode('&', $query);
        $result = [];
        foreach ($params as $param) {
            list($key, $value) = explode('=', $param, 2);
            $result[urldecode($key)] = urldecode($value);
        }
        return $result;
    }
    /**
     * Get the headers needed before transferring the content of a POST file.
     *
     * @param HodelaFile $file
     *
     * @return string
     */
    protected function getFileHeaders(HodelaFile $file)
    {
        return "\r\nContent-Type: {$file->getMimetype()}";
    }
}