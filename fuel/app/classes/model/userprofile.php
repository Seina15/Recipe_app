<?php
use Fuel\Core\DB;

class Model_UserProfile extends \Model
{
    // ユーザープロフィールを保存 or 更新する関数
    public static function get_prefs(int $userId): array
    {
        $sql = "SELECT avoid, cook_time, budget
                  FROM user_profile
                 WHERE user_id = :uid
                 LIMIT 1";

        $res = DB::query($sql)->parameters([':uid' => $userId])->execute();
        $row = $res->current();

        $avoid  = null;
        $cook_time = null;
        $budget = null;

        if (is_array($row)) {
            if (array_key_exists('avoid', $row)) {
                $tmp = trim((string)$row['avoid']);
                if ($tmp !== '') $avoid = $tmp;
            }
            if (array_key_exists('cook_time', $row)) {
                $val = $row['cook_time'];
                if ($val !== '' && $val !== null) {
                    $t = filter_var($val, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
                    if ($t !== false){
                        $cook_time = (int)$t;
                    }
                }
            }
            if (array_key_exists('budget', $row)) {
                $val = $row['budget'];
                if ($val !== '' && $val !== null) {
                    $b = filter_var($val, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
                    if ($b !== false) {
                        $budget = (int)$b;
                    }
                }
            }
        }
        return ['avoid'=>$avoid, 'cook_time'=>$cook_time, 'budget'=>$budget];
    }
}
