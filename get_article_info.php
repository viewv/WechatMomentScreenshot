<?php
/* 自动获取微信公众号文章标题/封面 */
/* https://github.com/TransparentLC/WechatMomentScreenshot */

//允许跨域
header('Access-Control-Allow-Origin: *');

if (empty($_GET['url'])) {
    $result['title'] = '';
    $result['cover'] = '';
} else {
    $ch = curl_init(htmlspecialchars_decode($_GET['url']));
    curl_setopt_array($ch, [
        CURLOPT_VERBOSE => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko',
    ]);
    $response = curl_exec($ch);

    preg_match('/var msg_title = "[\S\s]*?";/', $response, $matches);
    $result['title'] = empty($matches[0]) ? '' : str_replace(array('var msg_title = "', '";'), '', $matches[0]);
    preg_match('/var msg_cdn_url = "[\S\s]*?";/', $response, $matches);
    $result['cover'] = empty($matches[0]) ? '' : str_replace(array('var msg_cdn_url = "', '";'), '', $matches[0]);
    curl_close($ch);
};

//下载封面图，转存到alicdn
if (!empty($result['cover'])) {
    $ch = curl_init($result['cover']);
    curl_setopt_array($ch, [
        CURLOPT_VERBOSE => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko',
    ]);
    $filename = md5(mt_rand()) . '.jpg';
    file_put_contents($filename, curl_exec($ch));
    curl_close($ch);

    $ch = curl_init('https://api.superbed.cn/upload');
    curl_setopt_array($ch, [
        CURLOPT_VERBOSE => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'token' => 'fd9379f57917407f8239dd03a3de0248',
            'sync' => 1,
            'src' => $result['cover'],
        ],
    ]);
    $result['cover'] = json_decode(curl_exec($ch), true)['url'];
    curl_close($ch);

    unlink($filename);
}

$result['success'] = !empty($result['title']) && !empty($result['cover']);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
