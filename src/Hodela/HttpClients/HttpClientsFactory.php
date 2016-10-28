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

use InvalidArgumentException;
use Exception;

class HttpClientsFactory
{
    private function __construct(){
        // a factory constructor should never be invoked
    }
    /**
     * HTTP client generation.
     *
     * @param HodelaHttpClientInterface|string|null $handler
     *
     * @throws Exception                If the cURL extension or the Guzzle client aren't available (if required).
     * @throws InvalidArgumentException If the http client handler isn't "curl", "stream" or an instance of Hodela\HttpClients\HodelaHttpClientInterface.
     *
     * @return HodelaHttpClientInterface
     */
    public static function createHttpClient($handler)
    {
        if (!$handler) {
            return self::detectDefaultClient();
        }
        if ($handler instanceof HodelaHttpClientInterface) {
            return $handler;
        }
        if ('stream' === $handler) {
            return new HodelaStreamHttpClient();
        }
        if ('curl' === $handler) {
            if (!extension_loaded('curl')) {
                throw new Exception('The cURL extension must be loaded in order to use the "curl" handler.');
            }
            return new HodelaCurlHttpClient();
        }
        throw new InvalidArgumentException('The http client handler must be set to "curl", "stream", be an an instance of Hodela\HttpClients\HodelaHttpClientInterface');
    }
    /**
     * Detect default HTTP client.
     *
     * @return HodelaHttpClientInterface
     */
    private static function detectDefaultClient()
    {
        if (extension_loaded('curl')) {
            return new HodelaCurlHttpClient();
        }
        return new HodelaStreamHttpClient();
    }
}