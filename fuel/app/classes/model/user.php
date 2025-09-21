<?php
use Fuel\Core\DB;

// ユーザーの管理

class Model_User extends \Model
{
    public static function find_by_username(string $username): ?array
    {
        $row = DB::query("
            SELECT id, username, password AS password_hash
            FROM users
            WHERE username = :username
            LIMIT 1
        ")

        ->bind("username", $username)
        ->execute()
        ->current();

        if ($row) {
            return $row;
        } else {
            return null;
        }
    }


    public static function find_by_id(int $id): ?array
    {
        $query = "
            SELECT id, username
            FROM users
            WHERE id = :id
            LIMIT 1
        ";

        $result = DB::query($query)
            ->bind("id", $id)
            ->execute()
            ->as_array();

        if (isset($result[0])) {
            return $result[0];
            
        } else {
            return null;
        }
    }
}
