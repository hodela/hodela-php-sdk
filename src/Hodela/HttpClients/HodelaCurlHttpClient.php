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

namespace Hodela\HttpClients;

use Hodela\Http\RawResponse;
use Hodela\Exceptions\HodelaSDKException;

/**
 * Class HodelaCurlHttpClient
 *
 * @package Hodela
 */
class HodelaCurlHttpClient implements HodelaHttpClientInterface
{
    /**
     * @var string The client error message
     */
    protected $curlErrorMessage = '';
    /**
     * @var int The curl client error code
     */
    protected $curlErrorCode = 0;
    /**
     * @var string|boolean The raw response from the server
     */
    protected $rawResponse;
    /**
     * @var HodelaCurl Procedural curl as object
     */
    protected $hodelaCurl;
    /**
     * @param HodelaCurl|null Procedural curl as object
     */
    public function __construct(HodelaCurl $hodelaCurl = null)
    {
        $this->hodelaCurl = $hodelaCurl ?: new HodelaCurl();
    }
    /**
     * @inheritdoc
     */
    public function send($url, $method, $body, array $headers, $timeOut)
    {
        $this->openConnection($url, $method, $body, $headers, $timeOut);
        $this->sendRequest();
        if ($curlErrorCode = $this->hodelaCurl->errno()) {
            throw new HodelaSDKException($this->hodelaCurl->error(), $curlErrorCode);
        }
        // Separate the raw headers from the raw body
        list($rawHeaders, $rawBody) = $this->extractResponseHeadersAndBody();
        $this->closeConnection();
        return new RawResponse($rawHeaders, $rawBody);
    }
    /**
     * Opens a new curl connection.
     *
     * @param string $url     The endpoint to send the request to.
     * @param string $method  The request method.
     * @param string $body    The body of the request.
     * @param array  $headers The request headers.
     * @param int    $timeOut The timeout in seconds for the request.
     */
    public function openConnection($url, $method, $body, array $headers, $timeOut)
    {
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->compileRequestHeaders($headers),
            CURLOPT_URL => $url,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $timeOut,
            CURLOPT_RETURNTRANSFER => true, // Follow 301 redirects
            CURLOPT_HEADER => true, // Enable header processing
        ];
        if ($method !== "GET") {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        $this->hodelaCurl->init();
        $this->hodelaCurl->setoptArray($options);
    }
    /**
     * Closes an existing curl connection
     */
    public function closeConnection()
    {
        $this->hodelaCurl->close();
    }
    /**
     * Send the request and get the raw response from curl
     */
    public function sendRequest()
    {
        $this->rawResponse = $this->hodelaCurl->exec();
    }
    /**
     * Compiles the request headers into a curl-friendly format.
     *
     * @param array $headers The request headers.
     *
     * @return array
     */
    public function compileRequestHeaders(array $headers)
    {
        $return = [];
        foreach ($headers as $key => $value) {
            $return[] = $key . ': ' . $value;
        }
        return $return;
    }
    /**
     * Extracts the headers and the body into a two-part array
     *
     * @return array
     */
    public function extractResponseHeadersAndBody()
    {
        $parts = explode("\r\n\r\n", $this->rawResponse);
        $rawHeaders = array_shift($parts);
        $rawBody = implode("\r\n\r\n",$parts);
        return [trim($rawHeaders), trim($rawBody)];
    }
}