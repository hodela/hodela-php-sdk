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

use Hodela\HttpClients\HodelaHttpClientInterface;
use Hodela\HttpClients\HodelaCurlHttpClient;
use Hodela\HttpClients\HodelaStreamHttpClient;
use Hodela\Exceptions\HodelaSDKException;

/**
 * Class HodelaClient
 *
 * @package Hodela
 */
class HodelaClient
{
    /**
     * @const string Production API URL.
     */
    const BASE_API_URL = 'http://api.page.mava';
    /**
     * @const int The timeout in seconds for a normal request.
     */
    const DEFAULT_REQUEST_TIMEOUT = 60;
    /**
     * @const int The timeout in seconds for a request that contains file uploads.
     */
    const DEFAULT_FILE_UPLOAD_REQUEST_TIMEOUT = 3600;
    /**
     * @const int The timeout in seconds for a request that contains video uploads.
     */
    const DEFAULT_VIDEO_UPLOAD_REQUEST_TIMEOUT = 7200;
    /**
     * @var HodelaHttpClientInterface HTTP client handler.
     */
    protected $httpClientHandler;
    /**
     * @var int The number of calls that have been made to API.
     */
    public static $requestCount = 0;
    /**
     * Instantiates a new HodelaClient object.
     *
     * @param HodelaHttpClientInterface|null $httpClientHandler
     */
    public function __construct(HodelaHttpClientInterface $httpClientHandler = null)
    {
        $this->httpClientHandler = $httpClientHandler ?: $this->detectHttpClientHandler();
    }
    /**
     * Sets the HTTP client handler.
     *
     * @param HodelaHttpClientInterface $httpClientHandler
     */
    public function setHttpClientHandler(HodelaHttpClientInterface $httpClientHandler)
    {
        $this->httpClientHandler = $httpClientHandler;
    }
    /**
     * Returns the HTTP client handler.
     *
     * @return HodelaHttpClientInterface
     */
    public function getHttpClientHandler()
    {
        return $this->httpClientHandler;
    }
    /**
     * Detects which HTTP client handler to use.
     *
     * @return HodelaHttpClientInterface
     */
    public function detectHttpClientHandler()
    {
        return extension_loaded('curl') ? new HodelaCurlHttpClient() : new HodelaStreamHttpClient();
    }
    /**
     * Returns the base API URL.
     * @return string
     */
    public function getBaseApiUrl()
    {
        return static::BASE_API_URL;
    }
    /**
     * Prepares the request for sending to the client handler.
     *
     * @param HodelaRequest $request
     *
     * @return array
     */
    public function prepareRequestMessage(HodelaRequest $request)
    {
        $url = $this->getBaseApiUrl() . $request->getUrl();
        // If we're sending files they should be sent as multipart/form-data
        if ($request->containsFileUploads()) {
            $requestBody = $request->getMultipartBody();
            $request->setHeaders([
                'Content-Type' => 'multipart/form-data; boundary=' . $requestBody->getBoundary(),
            ]);
        } else {
            $requestBody = $request->getUrlEncodedBody();
            $request->setHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]);
        }
        return [
            $url,
            $request->getMethod(),
            $request->getHeaders(),
            $requestBody->getBody(),
        ];
    }
    /**
     * Makes the request to API and returns the result.
     *
     * @param HodelaRequest $request
     *
     * @return HodelaResponse
     *
     * @throws HodelaSDKException
     */
    public function sendRequest(HodelaRequest $request)
    {
        list($url, $method, $headers, $body) = $this->prepareRequestMessage($request);
        // Since file uploads can take a while, we need to give more time for uploads
        $timeOut = static::DEFAULT_REQUEST_TIMEOUT;
        if ($request->containsFileUploads()) {
            $timeOut = static::DEFAULT_FILE_UPLOAD_REQUEST_TIMEOUT;
        } elseif ($request->containsVideoUploads()) {
            $timeOut = static::DEFAULT_VIDEO_UPLOAD_REQUEST_TIMEOUT;
        }
        // Should throw `HodelaSDKException` exception on HTTP client error.
        // Don't catch to allow it to bubble up.
        $rawResponse = $this->httpClientHandler->send($url, $method, $body, $headers, $timeOut);
        static::$requestCount++;
        $returnResponse = new HodelaResponse(
            $request,
            $rawResponse->getBody(),
            $rawResponse->getHttpResponseCode(),
            $rawResponse->getHeaders()
        );
        if ($returnResponse->isError()) {
            throw $returnResponse->getThrownException();
        }
        return $returnResponse;
    }
    /**
     * Makes a batched request to API and returns the result.
     *
     * @param HodelaBatchRequest $request
     *
     * @return HodelaBatchResponse
     *
     * @throws HodelaSDKException
     */
    public function sendBatchRequest(HodelaBatchRequest $request)
    {
        $request->prepareRequestsForBatch();
        $hodelaResponse = $this->sendRequest($request);
        return new HodelaBatchResponse($request, $hodelaResponse);
    }
}