<?php

class Model_Recipe extends \Model
{
    public static function category_ranking(?string $categoryId = null): array
    {
        $app_id = getenv('RAKUTEN_APP_ID');
        if (!$app_id) {
            return ['success'=>false, 'stage'=>'config', 'error'=>'RAKUTEN_APP_ID is not set in .env'];
        }

        $url = 'https://app.rakuten.co.jp/services/api/Recipe/CategoryRanking/20170426'
             . '?applicationId=' . rawurlencode($app_id)
             . ($categoryId ? '&categoryId=' . rawurlencode($categoryId) : '');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $raw      = curl_exec($ch);
        $http     = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errorMsg = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['success'=>false, 'stage'=>'curl', 'error'=>$errorMsg];
        }
        if ($http >= 400) {
            return ['success'=>false, 'stage'=>'upstream', 'http'=>$http, 'raw_head'=>mb_substr($raw, 0, 200)];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['success'=>false, 'stage'=>'parse', 'error'=>'non-json', 'raw_head'=>mb_substr($raw, 0, 200)];
        }

        return ['success'=>true, 'data'=>$data];
    }
}
