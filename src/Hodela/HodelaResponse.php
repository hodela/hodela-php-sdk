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
namespace Hodela;
use Hodela\Exceptions\HodelaResponseException;
use Hodela\Exceptions\HodelaSDKException;
/**
 * Class HodelaResponse
 *
 * @package Hodela
 */
class HodelaResponse
{
    /**
     * @var int The HTTP status code response from API.
     */
    protected $httpStatusCode;
    /**
     * @var array The headers returned from API.
     */
    protected $headers;
    /**
     * @var string The raw body of the response from API.
     */
    protected $body;
    /**
     * @var array The decoded body of the API response.
     */
    protected $decodedBody = [];
    /**
     * @var HodelaRequest The original request that returned this response.
     */
    protected $request;
    /**
     * @var HodelaSDKException The exception thrown by this request.
     */
    protected $thrownException;
    /**
     * Creates a new Response entity.
     *
     * @param HodelaRequest $request
     * @param string|null     $body
     * @param int|null        $httpStatusCode
     * @param array|null      $headers
     */
    public function __construct(HodelaRequest $request, $body = null, $httpStatusCode = null, array $headers = [])
    {
        $this->request = $request;
        $this->body = $body;
        $this->httpStatusCode = $httpStatusCode;
        $this->headers = $headers;
        $this->decodeBody();
    }
    /**
     * Return the original request that returned this response.
     *
     * @return HodelaRequest
     */
    public function getRequest()
    {
        return $this->request;
    }
    /**
     * Return the HodelaApp entity used for this response.
     *
     * @return HodelaApp
     */
    public function getApp()
    {
        return $this->request->getApp();
    }
    /**
     * Return the HTTP status code for this response.
     *
     * @return int
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }
    /**
     * Return the HTTP headers for this response.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    /**
     * Return the raw body response.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
    /**
     * Return the decoded body response.
     *
     * @return array
     */
    public function getDecodedBody()
    {
        return $this->decodedBody;
    }
    /**
     * Get the ETag associated with the response.
     *
     * @return string|null
     */
    public function getETag()
    {
        return isset($this->headers['ETag']) ? $this->headers['ETag'] : null;
    }
    /**
     * Returns true if API returned an error message.
     *
     * @return boolean
     */
    public function isError()
    {
        return isset($this->decodedBody['error']) && $this->decodedBody['error'] == 1;
    }
    /**
     * Throws the exception.
     *
     * @throws HodelaSDKException
     */
    public function throwException()
    {
        throw $this->thrownException;
    }
    /**
     * Instantiates an exception to be thrown later.
     */
    public function makeException()
    {
        $this->thrownException = HodelaResponseException::create($this);
    }
    /**
     * Returns the exception that was thrown for this request.
     *
     * @return HodelaResponseException|null
     */
    public function getThrownException()
    {
        return $this->thrownException;
    }
    /**
     * Convert the raw response into an array if possible.
     */
    public function decodeBody()
    {
        $this->decodedBody = json_decode($this->body, true);
        if ($this->decodedBody === null) {
            $this->decodedBody = [];
            parse_str($this->body, $this->decodedBody);
        } elseif (is_bool($this->decodedBody)) {
            $this->decodedBody = ['success' => $this->decodedBody];
        } elseif (is_numeric($this->decodedBody)) {
            $this->decodedBody = ['id' => $this->decodedBody];
        }
        if (!is_array($this->decodedBody)) {
            $this->decodedBody = [];
        }
        if ($this->isError()) {
            $this->makeException();
        }
    }
}