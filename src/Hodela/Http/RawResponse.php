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
/**
 * Class RawResponse
 *
 * @package Hodela
 */
class RawResponse
{
    /**
     * @var array The response headers in the form of an associative array.
     */
    protected $headers;
    /**
     * @var string The raw response body.
     */
    protected $body;
    /**
     * @var int The HTTP status response code.
     */
    protected $httpResponseCode;
    /**
     * Creates a new RawResponse entity.
     *
     * @param string|array $headers        The headers as a raw string or array.
     * @param string       $body           The raw response body.
     * @param int          $httpStatusCode The HTTP response code (if sending headers as parsed array).
     */
    public function __construct($headers, $body, $httpStatusCode = null)
    {
        if (is_numeric($httpStatusCode)) {
            $this->httpResponseCode = (int)$httpStatusCode;
        }
        if (is_array($headers)) {
            $this->headers = $headers;
        } else {
            $this->setHeadersFromString($headers);
        }
        $this->body = $body;
    }
    /**
     * Return the response headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    /**
     * Return the body of the response.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
    /**
     * Return the HTTP response code.
     *
     * @return int
     */
    public function getHttpResponseCode()
    {
        return $this->httpResponseCode;
    }
    /**
     * Sets the HTTP response code from a raw header.
     *
     * @param string $rawResponseHeader
     */
    public function setHttpResponseCodeFromHeader($rawResponseHeader)
    {
        preg_match('|HTTP/\d\.\d\s+(\d+)\s+.*|', $rawResponseHeader, $match);
        if(is_array($match) && count($match) > 1){
            $this->httpResponseCode = (int)$match[1];
        }else{
            $this->httpResponseCode = 500;
        }
    }
    /**
     * Parse the raw headers and set as an array.
     *
     * @param string $rawHeaders The raw headers from the response.
     */
    protected function setHeadersFromString($rawHeaders)
    {
        // Normalize line breaks
        $rawHeaders = str_replace("\r\n", "\n", $rawHeaders);
        // There will be multiple headers if a 301 was followed
        // or a proxy was followed, etc
        $headerCollection = explode("\n\n", trim($rawHeaders));
        // We just want the last response (at the end)
        $rawHeader = array_shift($headerCollection);
        $headerComponents = explode("\n", $rawHeader);
        foreach ($headerComponents as $line) {
            if (strpos($line, ': ') === false) {
                $this->setHttpResponseCodeFromHeader($line);
            } else {
                list($key, $value) = explode(': ', $line);
                $this->headers[$key] = $value;
            }
        }
    }
}