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

class HodelaApp implements \Serializable
{
    /**
     * @var string The app ID.
     */
    protected $id;
    /**
     * @var string The app secret.
     */
    protected $secret;
    /**
     * @param string $id
     * @param string $secret
     *
     * @throws HodelaSDKException
     */
    public function __construct($id, $secret)
    {
        if (!is_string($id)
            // Keeping this for BC. Integers greater than PHP_INT_MAX will make is_int() return false
            && !is_int($id)) {
            throw new HodelaSDKException('The "app_id" must be formatted as a string since many app ID\'s are greater than PHP_INT_MAX on some systems.');
        }
        // We cast as a string in case a valid int was set on a 64-bit system and this is unserialised on a 32-bit system
        $this->id = (string) $id;
        $this->secret = $secret;
    }
    /**
     * Returns the app ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Returns the app secret.
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }
    /**
     * Serializes the HodelaApp entity as a string.
     *
     * @return string
     */
    public function serialize()
    {
        return implode('|', [$this->id, $this->secret]);
    }
    /**
     * Unserializes a string as a HodelaApp entity.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list($id, $secret) = explode('|', $serialized);
        $this->__construct($id, $secret);
    }
}