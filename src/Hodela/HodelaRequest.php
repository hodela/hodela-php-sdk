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

use Hodela\Url\HodelaUrlManipulator;
use Hodela\FileUpload\HodelaFile;
use Hodela\FileUpload\HodelaVideo;
use Hodela\Http\RequestBodyMultipart;
use Hodela\Http\RequestBodyUrlEncoded;
use Hodela\Exceptions\HodelaSDKException;

/**
 * Class Request
 *
 * @package Hodela
 */
class HodelaRequest
{
    /**
     * @var HodelaApp The Hodela app entity.
     */
    protected $app;
    /**
     * @var string The HTTP method for this request.
     */
    protected $method;
    /**
     * @var string The API endpoint for this request.
     */
    protected $endpoint;
    /**
     * @var array The headers to send with this request.
     */
    protected $headers = [];
    /**
     * @var array The parameters to send with this request.
     */
    protected $params = [];
    /**
     * @var array The files to send with this request.
     */
    protected $files = [];
    /**
     * @var string ETag to send with this request.
     */
    protected $eTag;
    /**
     * Creates a new Request entity.
     *
     * @param HodelaApp|null        $app
     * @param string|null             $method
     * @param string|null             $endpoint
     * @param array|null              $params
     * @param string|null             $eTag
     */
    public function __construct(HodelaApp $app = null, $method = null, $endpoint = null, array $params = [], $eTag = null)
    {
        $this->setApp($app);
        $this->setMethod($method);
        $this->setEndpoint($endpoint);
        $this->setParams($params);
        $this->setETag($eTag);
    }
    /**
     * Set the HodelaApp entity used for this request.
     *
     * @param HodelaApp|null $app
     */
    public function setApp(HodelaApp $app = null)
    {
        $this->app = $app;
    }
    /**
     * Return the HodelaApp entity used for this request.
     *
     * @return HodelaApp
     */
    public function getApp()
    {
        return $this->app;
    }
    /**
     * Set the HTTP method for this request.
     *
     * @param string
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }
    /**
     * Return the HTTP method for this request.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
    /**
     * Validate that the HTTP method is set.
     *
     * @throws HodelaSDKException
     */
    public function validateMethod()
    {
        if (!$this->method) {
            throw new HodelaSDKException('HTTP method not specified.');
        }
        if (!in_array($this->method, ['GET', 'POST', 'DELETE'])) {
            throw new HodelaSDKException('Invalid HTTP method specified.');
        }
    }
    /**
     * Set the endpoint for this request.
     *
     * @param string
     *
     * @return HodelaRequest
     *
     * @throws HodelaSDKException
     */
    public function setEndpoint($endpoint)
    {
        // Clean the app id & app secret from the endpoint.
        $filterParams = ['app_id', 'app_secret'];
        $this->endpoint = HodelaUrlManipulator::removeParamsFromUrl($endpoint, $filterParams);
        return $this;
    }
    /**
     * Return the endpoint for this request.
     *
     * @return string
     */
    public function getEndpoint()
    {
        // For batch requests, this will be empty
        return $this->endpoint;
    }
    /**
     * Generate and return the headers for this request.
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = static::getDefaultHeaders();
        if ($this->eTag) {
            $headers['If-None-Match'] = $this->eTag;
        }
        return array_merge($this->headers, $headers);
    }
    /**
     * Set the headers for this request.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }
    /**
     * Sets the eTag value.
     *
     * @param string $eTag
     */
    public function setETag($eTag)
    {
        $this->eTag = $eTag;
    }
    /**
     * Set the params for this request.
     *
     * @param array $params
     *
     * @return HodelaRequest
     *
     * @throws HodelaSDKException
     */
    public function setParams(array $params = [])
    {
        // Don't let these buggers slip in.
        unset($params['app_id'], $params['app_secret']);
        $params = $this->sanitizeFileParams($params);
        $this->dangerouslySetParams($params);
        return $this;
    }
    /**
     * Set the params for this request without filtering them first.
     *
     * @param array $params
     *
     * @return HodelaRequest
     */
    public function dangerouslySetParams(array $params = [])
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }
    /**
     * Iterate over the params and pull out the file uploads.
     *
     * @param array $params
     *
     * @return array
     */
    public function sanitizeFileParams(array $params)
    {
        foreach ($params as $key => $value) {
            if ($value instanceof HodelaFile) {
                $this->addFile($key, $value);
                unset($params[$key]);
            }
        }
        return $params;
    }
    /**
     * Add a file to be uploaded.
     *
     * @param string       $key
     * @param HodelaFile $file
     */
    public function addFile($key, HodelaFile $file)
    {
        $this->files[$key] = $file;
    }
    /**
     * Removes all the files from the upload queue.
     */
    public function resetFiles()
    {
        $this->files = [];
    }
    /**
     * Get the list of files to be uploaded.
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }
    /**
     * Let's us know if there is a file upload with this request.
     *
     * @return boolean
     */
    public function containsFileUploads()
    {
        return !empty($this->files);
    }
    /**
     * Let's us know if there is a video upload with this request.
     *
     * @return boolean
     */
    public function containsVideoUploads()
    {
        foreach ($this->files as $file) {
            if ($file instanceof HodelaVideo) {
                return true;
            }
        }
        return false;
    }
    /**
     * Returns the body of the request as multipart/form-data.
     *
     * @return RequestBodyMultipart
     */
    public function getMultipartBody()
    {
        $params = $this->getPostParams();
        return new RequestBodyMultipart($params, $this->files);
    }
    /**
     * Returns the body of the request as URL-encoded.
     *
     * @return RequestBodyUrlEncoded
     */
    public function getUrlEncodedBody()
    {
        $params = $this->getPostParams();
        return new RequestBodyUrlEncoded($params);
    }
    /**
     * Generate and return the params for this request.
     *
     * @return array
     */
    public function getParams()
    {
        if(!isset($this->params['app_id']) || $this->params['app_id'] == ''){
            $this->params['app_id'] = $this->getApp()->getId();
        }
        if(!isset($this->params['app_secret']) || $this->params['app_secret'] == ''){
            $this->params['app_secret'] = $this->getApp()->getSecret();
        }
        return $this->params;
    }
    /**
     * Only return params on POST requests.
     *
     * @return array
     */
    public function getPostParams()
    {
        if ($this->getMethod() === 'POST') {
            return $this->getParams();
        }
        return [];
    }
    /**
     * Generate and return the URL for this request.
     *
     * @return string
     */
    public function getUrl()
    {
        $this->validateMethod();
        $url = HodelaUrlManipulator::forceSlashPrefix($this->getEndpoint());
        if ($this->getMethod() !== 'POST') {
            $params = $this->getParams();
            $url = HodelaUrlManipulator::appendParamsToUrl($url, $params);
        }
        return $url;
    }
    /**
     * Return the default headers that every request should use.
     *
     * @return array
     */
    public static function getDefaultHeaders()
    {
        return [
            'User-Agent' => 'hdl-php-' . Hodela::VERSION,
            'Accept-Encoding' => '*',
        ];
    }
}