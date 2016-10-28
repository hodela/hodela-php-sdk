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

use ArrayIterator;
use IteratorAggregate;
use ArrayAccess;

/**
 * Class HodelaBatchResponse
 *
 * @package Hodela
 */
class HodelaBatchResponse extends HodelaResponse implements IteratorAggregate, ArrayAccess
{
    /**
     * @var HodelaBatchRequest The original entity that made the batch request.
     */
    protected $batchRequest;
    /**
     * @var array An array of HodelaResponse entities.
     */
    protected $responses = [];
    /**
     * Creates a new Response entity.
     *
     * @param HodelaBatchRequest $batchRequest
     * @param HodelaResponse     $response
     */
    public function __construct(HodelaBatchRequest $batchRequest, HodelaResponse $response)
    {
        $this->batchRequest = $batchRequest;
        $request = $response->getRequest();
        $body = $response->getBody();
        $httpStatusCode = $response->getHttpStatusCode();
        $headers = $response->getHeaders();
        parent::__construct($request, $body, $httpStatusCode, $headers);
        $responses = $response->getDecodedBody();
        $this->setResponses($responses);
    }
    /**
     * Returns an array of HodelaResponse entities.
     *
     * @return array
     */
    public function getResponses()
    {
        return $this->responses;
    }
    /**
     * The main batch response will be an array of requests so
     * we need to iterate over all the responses.
     *
     * @param array $responses
     */
    public function setResponses(array $responses)
    {
        $this->responses = [];
        foreach ($responses as $key => $apiResponse) {
            $this->addResponse($key, $apiResponse);
        }
    }
    /**
     * Add a response to the list.
     *
     * @param int        $key
     * @param array|null $response
     */
    public function addResponse($key, $response)
    {
        $originalRequestName = isset($this->batchRequest[$key]['name']) ? $this->batchRequest[$key]['name'] : $key;
        $originalRequest = isset($this->batchRequest[$key]['request']) ? $this->batchRequest[$key]['request'] : null;
        $httpResponseBody = isset($response['body']) ? $response['body'] : null;
        $httpResponseCode = isset($response['code']) ? $response['code'] : null;
        $httpResponseHeaders = isset($response['headers']) ? $this->normalizeBatchHeaders($response['headers']) : [];
        $this->responses[$originalRequestName] = new HodelaResponse(
            $originalRequest,
            $httpResponseBody,
            $httpResponseCode,
            $httpResponseHeaders
        );
    }
    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new ArrayIterator($this->responses);
    }
    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->addResponse($offset, $value);
    }
    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->responses[$offset]);
    }
    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->responses[$offset]);
    }
    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return isset($this->responses[$offset]) ? $this->responses[$offset] : null;
    }
    /**
     * Converts the batch header array into a standard format.
     *
     * @param array $batchHeaders
     *
     * @return array
     */
    private function normalizeBatchHeaders(array $batchHeaders)
    {
        $headers = [];
        foreach ($batchHeaders as $header) {
            $headers[$header['name']] = $header['value'];
        }
        return $headers;
    }
}