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
/**
 * Class HodelaStream
 *
 * Abstraction for the procedural stream elements so that the functions can be
 * mocked and the implementation can be tested.
 *
 * @package Hodela
 */
class HodelaStream
{
    /**
     * @var resource Context stream resource instance
     */
    protected $stream;
    /**
     * @var array Response headers from the stream wrapper
     */
    protected $responseHeaders = [];
    /**
     * Make a new context stream reference instance
     *
     * @param array $options
     */
    public function streamContextCreate(array $options)
    {
        $this->stream = stream_context_create($options);
    }
    /**
     * The response headers from the stream wrapper
     *
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }
    /**
     * Send a stream wrapped request
     *
     * @param string $url
     *
     * @return mixed
     */
    public function fileGetContents($url)
    {
        $rawResponse = file_get_contents($url, false, $this->stream);
        $this->responseHeaders = $http_response_header ?: [];
        return $rawResponse;
    }
}