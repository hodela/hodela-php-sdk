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
 * Class HodelaUrlManipulator
 *
 * @package Hodela
 */
class HodelaUrlManipulator
{
    /**
     * Remove params from a URL.
     *
     * @param string $url            The URL to filter.
     * @param array  $paramsToFilter The params to filter from the URL.
     *
     * @return string The URL with the params removed.
     */
    public static function removeParamsFromUrl($url, array $paramsToFilter)
    {
        $parts = parse_url($url);
        $query = '';
        if (isset($parts['query'])) {
            $params = [];
            parse_str($parts['query'], $params);
            // Remove query params
            foreach ($paramsToFilter as $paramName) {
                unset($params[$paramName]);
            }
            if (count($params) > 0) {
                $query = '?' . http_build_query($params, null, '&');
            }
        }
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = isset($parts['host']) ? $parts['host'] : '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = isset($parts['path']) ? $parts['path'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        return $scheme . $host . $port . $path . $query . $fragment;
    }
    /**
     * Gracefully appends params to the URL.
     *
     * @param string $url       The URL that will receive the params.
     * @param array  $newParams The params to append to the URL.
     *
     * @return string
     */
    public static function appendParamsToUrl($url, array $newParams = [])
    {
        if (empty($newParams)) {
            return $url;
        }
        if (strpos($url, '?') === false) {
            return $url . '?' . http_build_query($newParams, null, '&');
        }
        list($path, $query) = explode('?', $url, 2);
        $existingParams = [];
        parse_str($query, $existingParams);
        // Favor params from the original URL over $newParams
        $newParams = array_merge($newParams, $existingParams);
        // Sort for a predicable order
        ksort($newParams);
        return $path . '?' . http_build_query($newParams, null, '&');
    }
    /**
     * Returns the params from a URL in the form of an array.
     *
     * @param string $url The URL to parse the params from.
     *
     * @return array
     */
    public static function getParamsAsArray($url)
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (!$query) {
            return [];
        }
        $params = [];
        parse_str($query, $params);
        return $params;
    }
    /**
     * Adds the params of the first URL to the second URL.
     *
     * Any params that already exist in the second URL will go untouched.
     *
     * @param string $urlToStealFrom The URL harvest the params from.
     * @param string $urlToAddTo     The URL that will receive the new params.
     *
     * @return string The $urlToAddTo with any new params from $urlToStealFrom.
     */
    public static function mergeUrlParams($urlToStealFrom, $urlToAddTo)
    {
        $newParams = static::getParamsAsArray($urlToStealFrom);
        // Nothing new to add, return as-is
        if (!$newParams) {
            return $urlToAddTo;
        }
        return static::appendParamsToUrl($urlToAddTo, $newParams);
    }
    /**
     * Check for a "/" prefix and prepend it if not exists.
     *
     * @param string|null $string
     *
     * @return string|null
     */
    public static function forceSlashPrefix($string)
    {
        if (!$string) {
            return $string;
        }
        return strpos($string, '/') === 0 ? $string : '/' . $string;
    }
    /**
     * Trims off the hostname and API version from a URL.
     *
     * @param string $urlToTrim The URL the needs the surgery.
     *
     * @return string The $urlToTrim with the hostname and API version removed.
     */
    public static function baseApiUrlEndpoint($urlToTrim)
    {
        return '/' . preg_replace('/^https:\/\/.+\.hodela\.com(\/v.+?)?\//', '', $urlToTrim);
    }
}