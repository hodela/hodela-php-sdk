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

use Hodela\Exceptions\HodelaSDKException;
use Hodela\FileUpload\HodelaFile;
use Hodela\FileUpload\HodelaVideo;
use Hodela\HttpClients\HttpClientsFactory;
use Hodela\Url\UrlDetectionInterface;


class Hodela {
    const VERSION = '1.0';
    const APP_ID_ENV_NAME = 'HODELA_APP_ID';
    const APP_SECRET_ENV_NAME = 'HODELA_APP_SECRET';
    protected $app;
    protected $client;
    protected $urlDetectionHandler;
    protected $lastResponse;


    function __construct(array $config = []){
        $config = array_merge([
            'app_id' => getenv(static::APP_ID_ENV_NAME),
            'app_secret' => getenv(static::APP_SECRET_ENV_NAME),
            'http_client_handler' => null,
            'url_detection_handler' => null
        ], $config);

        if(!$config['app_id']){
            throw new HodelaSDKException('Required "app_id" key not supplied in config and could not find fallback environment variable "' . static::APP_ID_ENV_NAME . '"');
        }

        if(!$config['app_secret']){
            throw new HodelaSDKException('Required "app_secret" key not supplied in config and could not find fallback environment variable "' . static::APP_SECRET_ENV_NAME . '"');
        }

        $this->app = new HodelaApp($config['app_id'], $config['app_secret']);

        $this->client = new HodelaClient(
            HttpClientsFactory::createHttpClient($config['http_client_handler'])
        );
    }

    /**
     * Returns the HodelaApp entity.
     *
     * @return HodelaApp
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Returns the HodelaClient service.
     *
     * @return HodelaClient
     */
    public function getClient()
    {
        return $this->client;
    }
    /**
     * Returns the last response returned from API.
     *
     * @return HodelaResponse|HodelaBatchResponse|null
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }
    /**
     * Returns the URL detection handler.
     *
     * @return UrlDetectionInterface
     */
    public function getUrlDetectionHandler()
    {
        return $this->urlDetectionHandler;
    }
    /**
     * Changes the URL detection handler.
     *
     * @param UrlDetectionInterface $urlDetectionHandler
     */
    private function setUrlDetectionHandler(UrlDetectionInterface $urlDetectionHandler)
    {
        $this->urlDetectionHandler = $urlDetectionHandler;
    }
    /**
     * Sends a GET request to API and returns the result.
     *
     * @param string                  $endpoint
     * @param string|null             $eTag
     *
     * @return HodelaResponse
     *
     * @throws HodelaSDKException
     */
    public function get($endpoint, $eTag = null)
    {
        return $this->sendRequest(
            'GET',
            $endpoint,
            $params = [],
            $eTag
        );
    }
    /**
     * Sends a POST request to API and returns the result.
     *
     * @param string                  $endpoint
     * @param array                   $params
     * @param string|null             $eTag
     *
     * @return HodelaResponse
     *
     * @throws HodelaSDKException
     */
    public function post($endpoint, array $params = [], $eTag = null)
    {
        return $this->sendRequest(
            'POST',
            $endpoint,
            $params,
            $eTag
        );
    }
    /**
     * Sends a DELETE request to API and returns the result.
     *
     * @param string                  $endpoint
     * @param array                   $params
     * @param string|null             $eTag
     *
     * @return HodelaResponse
     *
     * @throws HodelaSDKException
     */
    public function delete($endpoint, array $params = [], $eTag = null)
    {
        return $this->sendRequest(
            'DELETE',
            $endpoint,
            $params,
            $eTag
        );
    }
    /**
     * Sends a request to API and returns the result.
     *
     * @param string                  $method
     * @param string                  $endpoint
     * @param array                   $params
     * @param string|null             $eTag
     *
     * @return HodelaResponse
     *
     * @throws HodelaSDKException
     */
    public function sendRequest($method, $endpoint, array $params = [], $eTag = null)
    {
        $request = $this->request($method, $endpoint, $params, $eTag);
        return $this->lastResponse = $this->client->sendRequest($request);
    }
    /**
     * Sends a batched request to API and returns the result.
     *
     * @param array                   $requests
     *
     * @return HodelaBatchResponse
     *
     * @throws HodelaSDKException
     */
    public function sendBatchRequest(array $requests)
    {
        $batchRequest = new HodelaBatchRequest(
            $this->app,
            $requests
        );
        return $this->lastResponse = $this->client->sendBatchRequest($batchRequest);
    }
    /**
     * Instantiates a new HodelaRequest entity.
     *
     * @param string                  $method
     * @param string                  $endpoint
     * @param array                   $params
     * @param string|null             $eTag
     *
     * @return HodelaRequest
     *
     * @throws HodelaSDKException
     */
    public function request($method, $endpoint, array $params = [], $eTag = null)
    {
        return new HodelaRequest(
            $this->app,
            $method,
            $endpoint,
            $params,
            $eTag
        );
    }
    /**
     * Factory to create HodelaFile's.
     *
     * @param string $pathToFile
     *
     * @return HodelaFile
     *
     * @throws HodelaSDKException
     */
    public function fileToUpload($pathToFile)
    {
        return new HodelaFile($pathToFile);
    }
    /**
     * Factory to create HodelaVideo's.
     *
     * @param string $pathToFile
     *
     * @return HodelaVideo
     *
     * @throws HodelaSDKException
     */
    public function videoToUpload($pathToFile)
    {
        return new HodelaVideo($pathToFile);
    }
}