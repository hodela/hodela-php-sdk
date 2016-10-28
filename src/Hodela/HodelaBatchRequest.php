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
use Hodela\Exceptions\HodelaSDKException;

/**
 * Class BatchRequest
 *
 * @package Hodela
 */
class HodelaBatchRequest extends HodelaRequest implements IteratorAggregate, ArrayAccess
{
    const BATCH_REQUEST_LIMIT = 50;
    /**
     * @var array An array of HodelaRequest entities to send.
     */
    protected $requests;
    /**
     * @var array An array of files to upload.
     */
    protected $attachedFiles;
    /**
     * Creates a new Request entity.
     *
     * @param HodelaApp|null        $app
     * @param array                   $requests
     */
    public function __construct(HodelaApp $app = null, array $requests = [])
    {
        parent::__construct($app, 'POST', '', [], null);
        $this->add($requests);
    }
    /**
     * A a new request to the array.
     *
     * @param HodelaRequest|array $request
     * @param string|null           $name
     *
     * @return HodelaBatchRequest
     *
     * @throws \InvalidArgumentException
     */
    public function add($request, $name = null)
    {
        if (is_array($request)) {
            foreach ($request as $key => $req) {
                $this->add($req, $key);
            }
            return $this;
        }
        if (!$request instanceof HodelaRequest) {
            throw new \InvalidArgumentException('Argument for add() must be of type array or HodelaRequest.');
        }
        $this->addFallbackDefaults($request);
        $requestToAdd = [
            'name' => $name,
            'request' => $request,
        ];
        // File uploads
        $attachedFiles = $this->extractFileAttachments($request);
        if ($attachedFiles) {
            $requestToAdd['attached_files'] = $attachedFiles;
        }
        $this->requests[] = $requestToAdd;
        return $this;
    }
    /**
     * Ensures that the HodelaApp fall back when missing.
     *
     * @param HodelaRequest $request
     *
     * @throws HodelaSDKException
     */
    public function addFallbackDefaults(HodelaRequest $request)
    {
        if (!$request->getApp()) {
            $app = $this->getApp();
            if (!$app) {
                throw new HodelaSDKException('Missing HodelaApp on HodelaRequest and no fallback detected on HodelaBatchRequest.');
            }
            $request->setApp($app);
        }
    }
    /**
     * Extracts the files from a request.
     *
     * @param HodelaRequest $request
     *
     * @return string|null
     *
     * @throws HodelaSDKException
     */
    public function extractFileAttachments(HodelaRequest $request)
    {
        if (!$request->containsFileUploads()) {
            return null;
        }
        $files = $request->getFiles();
        $fileNames = [];
        foreach ($files as $file) {
            $fileName = uniqid();
            $this->addFile($fileName, $file);
            $fileNames[] = $fileName;
        }
        $request->resetFiles();
        return implode(',', $fileNames);
    }
    /**
     * Return the HodelaRequest entities.
     *
     * @return array
     */
    public function getRequests()
    {
        return $this->requests;
    }
    /**
     * Prepares the requests to be sent as a batch request.
     */
    public function prepareRequestsForBatch()
    {
        $this->validateBatchRequestCount();
        $params = [
            'batch' => $this->convertRequestsToJson(),
            'include_headers' => true,
        ];
        $this->setParams($params);
    }
    /**
     * Converts the requests into a JSON(P) string.
     *
     * @return string
     */
    public function convertRequestsToJson()
    {
        $requests = [];
        foreach ($this->requests as $request) {
            $attachedFiles = isset($request['attached_files']) ? $request['attached_files'] : null;
            $requests[] = $this->requestEntityToBatchArray($request['request'], $request['name'], $attachedFiles);
        }
        return json_encode($requests);
    }
    /**
     * Validate the request count before sending them as a batch.
     *
     * @throws HodelaSDKException
     */
    public function validateBatchRequestCount()
    {
        $batchCount = count($this->requests);
        if ($batchCount === 0) {
            throw new HodelaSDKException('There are no batch requests to send.');
        } elseif ($batchCount > static::BATCH_REQUEST_LIMIT) {
            throw new HodelaSDKException('You cannot send more than '. static::BATCH_REQUEST_LIMIT .' batch requests at a time.');
        }
    }
    /**
     * Converts a Request entity into an array that is batch-friendly.
     *
     * @param HodelaRequest   $request       The request entity to convert.
     * @param string|null     $requestName   The name of the request.
     * @param string|null     $attachedFiles Names of files associated with the request.
     *
     * @return array
     */
    public function requestEntityToBatchArray(HodelaRequest $request, $requestName = null, $attachedFiles = null)
    {
        $compiledHeaders = [];
        $headers = $request->getHeaders();
        foreach ($headers as $name => $value) {
            $compiledHeaders[] = $name . ': ' . $value;
        }
        $batch = [
            'headers' => $compiledHeaders,
            'method' => $request->getMethod(),
            'relative_url' => $request->getUrl(),
        ];
        // Since file uploads are moved to the root request of a batch request,
        // the child requests will always be URL-encoded.
        $body = $request->getUrlEncodedBody()->getBody();
        if ($body) {
            $batch['body'] = $body;
        }
        if (isset($requestName)) {
            $batch['name'] = $requestName;
        }
        if (isset($attachedFiles)) {
            $batch['attached_files'] = $attachedFiles;
        }
        return $batch;
    }
    /**
     * Get an iterator for the items.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->requests);
    }
    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->add($value, $offset);
    }
    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->requests[$offset]);
    }
    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->requests[$offset]);
    }
    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return isset($this->requests[$offset]) ? $this->requests[$offset] : null;
    }
}