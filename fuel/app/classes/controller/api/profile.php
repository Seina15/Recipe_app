<?php
class Controller_Api_Profile extends Controller_Rest
{
    protected $format = 'json';

    public function get_index()
    {
        return $this->response(['ok' => true, 'ping' => 'pong']);
    }

    
    public function post_index()
    {
        try {
            $raw  = file_get_contents('php://input');
            
            # データを取得
            $data = null;
            if ($raw !== false) {
                $data = json_decode($raw, true);

                if ($data === null) {
                    $data = [];
                }
            }

            
            $avoid = null;
            if (isset($data['avoid']) && $data['avoid'] !== '') {
                $avoid = trim((string)$data['avoid']);
            }

            $cook_time = null;
            if (isset($data['cook_time']) && $data['cook_time'] !== '') {
                $cook_time = (int)$data['cook_time'];
            }

            $budget = null;
            if (isset($data['budget']) && $data['budget'] !== '') {
                $budget = (int)$data['budget'];
            }

            $servings = null;
            if (isset($data['servings']) && $data['servings'] !== '') {
                $servings = (int)$data['servings'];
            }


            $userId = 1; // ★後で変える！！！！！


            $params = [
                'userid'   => $userId,
                'avoid'    => ($avoid !== null && $avoid !== '') ? $avoid : null,
                'cook_time'     => $cook_time,
                'budget'   => $budget,
                'servings' => $servings,
            ];

            $sql = "
            INSERT INTO user_profile (user_id, avoid, cook_time, budget, servings, updated_at)
            VALUES (:userid, :avoid, :cook_time, :budget, :servings, NOW())
            ON DUPLICATE KEY UPDATE
                avoid      = VALUES(avoid),
                cook_time       = VALUES(cook_time),
                budget     = VALUES(budget),
                servings   = VALUES(servings),
                updated_at = NOW()
            ";

            
            list($affected, $insertId) = \DB::query($sql)->parameters($params)->execute();


            return $this->response(['ok' => true]);

        } catch (\Throwable $e) {
            return $this->response(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}