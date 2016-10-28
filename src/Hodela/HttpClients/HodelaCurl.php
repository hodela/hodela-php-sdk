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
 * Class HodelaCurl
 *
 * Abstraction for the procedural curl elements so that curl can be mocked and the implementation can be tested.
 *
 * @package Hodela
 */
class HodelaCurl
{
    /**
     * @var resource Curl resource instance
     */
    protected $curl;
    /**
     * Make a new curl reference instance
     */
    public function init()
    {
        $this->curl = curl_init();
    }
    /**
     * Set a curl option
     *
     * @param $key
     * @param $value
     */
    public function setopt($key, $value)
    {
        curl_setopt($this->curl, $key, $value);
    }
    /**
     * Set an array of options to a curl resource
     *
     * @param array $options
     */
    public function setoptArray(array $options)
    {
        curl_setopt_array($this->curl, $options);
    }
    /**
     * Send a curl request
     *
     * @return mixed
     */
    public function exec()
    {
        return curl_exec($this->curl);
    }
    /**
     * Return the curl error number
     *
     * @return int
     */
    public function errno()
    {
        return curl_errno($this->curl);
    }
    /**
     * Return the curl error message
     *
     * @return string
     */
    public function error()
    {
        return curl_error($this->curl);
    }
    /**
     * Get info from a curl reference
     *
     * @param $type
     *
     * @return mixed
     */
    public function getinfo($type)
    {
        return curl_getinfo($this->curl, $type);
    }
    /**
     * Get the currently installed curl version
     *
     * @return array
     */
    public function version()
    {
        return curl_version();
    }
    /**
     * Close the resource connection to curl
     */
    public function close()
    {
        curl_close($this->curl);
    }
}