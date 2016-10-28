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

namespace Hodela\Url;
/**
 * Class HodelaUrlDetectionHandler
 *
 * @package Hodela
 */
class HodelaUrlDetectionHandler implements UrlDetectionInterface
{
    /**
     * @inheritdoc
     */
    public function getCurrentUrl()
    {
        return $this->getHttpScheme() . '://' . $this->getHostName() . $this->getServerVar('REQUEST_URI');
    }
    /**
     * Get the currently active URL scheme.
     *
     * @return string
     */
    protected function getHttpScheme()
    {
        return $this->isBehindSsl() ? 'https' : 'http';
    }
    /**
     * Tries to detect if the server is running behind an SSL.
     *
     * @return boolean
     */
    protected function isBehindSsl()
    {
        // Check for proxy first
        $protocol = $this->getHeader('X_FORWARDED_PROTO');
        if ($protocol) {
            return $this->protocolWithActiveSsl($protocol);
        }
        $protocol = $this->getServerVar('HTTPS');
        if ($protocol) {
            return $this->protocolWithActiveSsl($protocol);
        }
        return (string)$this->getServerVar('SERVER_PORT') === '443';
    }
    /**
     * Detects an active SSL protocol value.
     *
     * @param string $protocol
     *
     * @return boolean
     */
    protected function protocolWithActiveSsl($protocol)
    {
        $protocol = strtolower((string)$protocol);
        return in_array($protocol, ['on', '1', 'https', 'ssl'], true);
    }
    /**
     * Tries to detect the host name of the server.
     *
     * Some elements adapted from
     *
     * @see https://github.com/symfony/HttpFoundation/blob/master/Request.php
     *
     * @return string
     */
    protected function getHostName()
    {
        // Check for proxy first
        $header = $this->getHeader('X_FORWARDED_HOST');
        if ($header && $this->isValidForwardedHost($header)) {
            $elements = explode(',', $header);
            $host = $elements[count($elements) - 1];
        } elseif (!$host = $this->getHeader('HOST')) {
            if (!$host = $this->getServerVar('SERVER_NAME')) {
                $host = $this->getServerVar('SERVER_ADDR');
            }
        }
        // trim and remove port number from host
        // host is lowercase as per RFC 952/2181
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));
        // Port number
        $scheme = $this->getHttpScheme();
        $port = $this->getCurrentPort();
        $appendPort = ':' . $port;
        // Don't append port number if a normal port.
        if (($scheme == 'http' && $port == '80') || ($scheme == 'https' && $port == '443')) {
            $appendPort = '';
        }
        return $host . $appendPort;
    }
    protected function getCurrentPort()
    {
        // Check for proxy first
        $port = $this->getHeader('X_FORWARDED_PORT');
        if ($port) {
            return (string)$port;
        }
        $protocol = (string)$this->getHeader('X_FORWARDED_PROTO');
        if ($protocol === 'https') {
            return '443';
        }
        return (string)$this->getServerVar('SERVER_PORT');
    }
    /**
     * Returns the a value from the $_SERVER super global.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getServerVar($key)
    {
        return isset($_SERVER[$key]) ? $_SERVER[$key] : '';
    }
    /**
     * Gets a value from the HTTP request headers.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getHeader($key)
    {
        return $this->getServerVar('HTTP_' . $key);
    }
    /**
     * Checks if the value in X_FORWARDED_HOST is a valid hostname
     * Could prevent unintended redirections
     *
     * @param string $header
     *
     * @return boolean
     */
    protected function isValidForwardedHost($header)
    {
        $elements = explode(',', $header);
        $host = $elements[count($elements) - 1];

        return preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $host) //valid chars check
        && 0 < strlen($host) && strlen($host) < 254 //overall length check
        && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $host); //length of each label
    }
}