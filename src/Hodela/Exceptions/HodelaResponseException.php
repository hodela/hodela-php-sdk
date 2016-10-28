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

namespace Hodela\Exceptions;

use Hodela\HodelaResponse;

/**
 * Class HodelaResponseException
 *
 * @package Hodela
 */
class HodelaResponseException extends HodelaSDKException
{
    /**
     * @var HodelaResponse The response that threw the exception.
     */
    protected $response;
    /**
     * @var array Decoded response.
     */
    protected $responseData;
    /**
     * Creates a HodelaResponseException.
     *
     * @param HodelaResponse     $response          The response that threw the exception.
     * @param HodelaSDKException $previousException The more detailed exception.
     */
    public function __construct(HodelaResponse $response, HodelaSDKException $previousException = null)
    {
        $this->response = $response;
        $this->responseData = $response->getDecodedBody();
        $errorMessage = $this->get('message', 'Unknown error from API.');
        $errorCode = $this->get('code', -1);
        parent::__construct($errorMessage, $errorCode, $previousException);
    }
    /**
     * A factory for creating the appropriate exception based on the response from API.
     *
     * @param HodelaResponse $response The response that threw the exception.
     *
     * @return HodelaResponseException
     */
    public static function create(HodelaResponse $response)
    {
        $data = $response->getDecodedBody();
        if (!isset($data['error']['code']) && isset($data['code'])) {
            $data = ['error' => $data];
        }
        $code = isset($data['error']['code']) ? $data['error']['code'] : null;
        $message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error from API.';
        switch ($code) {
            // Server issue, possible downtime
            case 1:
            case 2:
                return new static($response, new HodelaServerException($message, $code));
            // API Throttling
            case 3:
                return new static($response, new HodelaThrottleException($message, $code));
        }
        // All others
        return new static($response, new HodelaOtherException($message, $code));
    }
    /**
     * Checks isset and returns that or a default value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function get($key, $default = null)
    {
        if (isset($this->responseData['error'][$key])) {
            return $this->responseData['error'][$key];
        }
        return $default;
    }
    /**
     * Returns the HTTP status code
     *
     * @return int
     */
    public function getHttpStatusCode()
    {
        return $this->response->getHttpStatusCode();
    }
    /**
     * Returns the error type
     *
     * @return string
     */
    public function getErrorType()
    {
        return $this->get('type', '');
    }
    /**
     * Returns the raw response used to create the exception.
     *
     * @return string
     */
    public function getRawResponse()
    {
        return $this->response->getBody();
    }
    /**
     * Returns the decoded response used to create the exception.
     *
     * @return array
     */
    public function getResponseData()
    {
        return $this->responseData;
    }
    /**
     * Returns the response entity used to create the exception.
     *
     * @return HodelaResponse
     */
    public function getResponse()
    {
        return $this->response;
    }
}