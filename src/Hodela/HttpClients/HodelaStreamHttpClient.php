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

class HodelaStreamHttpClient implements HodelaHttpClientInterface
{
    /**
     * @var HodelaStream Procedural stream wrapper as object.
     */
    protected $hodelaStream;
    /**
     * @param HodelaStream|null Procedural stream wrapper as object.
     */
    public function __construct(HodelaStream $hodelaStream = null)
    {
        $this->hodelaStream = $hodelaStream ?: new HodelaStream();
    }
    /**
     * @inheritdoc
     */
    public function send($url, $method, $body, array $headers, $timeOut)
    {
        $options = [
            'http' => [
                'method' => $method,
                'header' => $this->compileHeader($headers),
                'content' => $body,
                'timeout' => $timeOut,
                'ignore_errors' => true
            ]
        ];
        $this->hodelaStream->streamContextCreate($options);
        $rawBody = $this->hodelaStream->fileGetContents($url);
        $rawHeaders = $this->hodelaStream->getResponseHeaders();
        if ($rawBody === false || empty($rawHeaders)) {
            throw new HodelaSDKException('Stream returned an empty response', 660);
        }
        $rawHeaders = implode("\r\n", $rawHeaders);
        return new RawResponse($rawHeaders, $rawBody);
    }
    /**
     * Formats the headers for use in the stream wrapper.
     *
     * @param array $headers The request headers.
     *
     * @return string
     */
    public function compileHeader(array $headers)
    {
        $header = [];
        foreach ($headers as $k => $v) {
            $header[] = $k . ': ' . $v;
        }
        return implode("\r\n", $header);
    }
}