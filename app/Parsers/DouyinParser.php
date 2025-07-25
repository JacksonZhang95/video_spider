<?php

namespace App\Parsers;

use App\Utils\Common;

class DouyinParser extends BaseParser
{
    protected static function getHeaders()
    {
        return [
            'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1 Edg/122.0.0.0'
        ];
    }

    public static function parse($url)
    {
        $loc = Common::getLocation($url);

        preg_match('/[0-9]+/', $loc, $id);

        if (empty($id)) {
            return self::error(400, '无法解析视频 ID');
        }

        $res = Common::getCurl("https://www.iesdouyin.com/share/video/{$id[0]}", null, self::getHeaders());
        $pattern = '/window\._ROUTER_DATA\s*=\s*(.*?)\<\/script>/s';
        preg_match($pattern, $res, $matches);

        $data = json_decode(trim($matches[1]), true);

        $item = $data['loaderData']['video_(id)/page']['videoInfoRes']['item_list'][0];
        if (!$item) {
            return self::error(400, '解析视频信息失败');
        }

        $video_id = $item['video']['play_addr']['uri'];
        $video_url = "http://www.iesdouyin.com/aweme/v1/play/?video_id={$video_id}&ratio=1080p&line=0";

        if ($video_id) {
            return self::success([
                'author' => $item['author']['nickname'],
                'uid' => $item['author']['unique_id'],
                'avatar' => $item['author']['avatar_medium']['url_list'][0],
                'like' => $item['statistics']['digg_count'],
                'time' => $item['create_time'],
                'title' => $item['desc'],
                'cover' => $item['video']['cover']['url_list'][0],
                'url' => $video_url,
                'music' => [
                    'author' => $item['music']['author'],
                    'avatar' => $item['music']['cover_large']['url_list'][0]
                ]
            ]);
        }
        return self::error(201, '未找到视频URL');
    }
}
