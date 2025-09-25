<?php

use Fuel\Core\Controller_Rest;
use Fuel\Core\Input;
use Fuel\Core\Response;
use Fuel\Core\Session;
use Fuel\Core\Log;
use Fuel\Core\DB;

// ユーザープロフィールに関するAPI

class Controller_Api_Profile extends Controller_Rest
{


    // セキュリティ関連の前処理
    public function before()
    {
        parent::before();
        $this->response->set_header("Content-Type", "application/json; charset=utf-8");
        $this->response->set_header("X-Frame-Options", "SAMEORIGIN");
       
        $requestSecret = null;

        if ($_SERVER["REQUEST_METHOD"] === "GET") {
            $requestSecret = isset($_GET["login_secret"]) ? $_GET["login_secret"] : null;
        
        } else {
            $rawInput = file_get_contents("php://input");
            
            if ($rawInput) {
                $inputData = json_decode($rawInput, true);
            
                if (is_array($inputData) && isset($inputData["login_secret"])) {
                    $requestSecret = $inputData["login_secret"];
                }
            }
            if (!$requestSecret && isset($_POST["login_secret"])) {
                $requestSecret = $_POST["login_secret"];
            }
        }

        $sessionSecret = Session::get("login_secret");
        if (!$sessionSecret || !$requestSecret || !hash_equals($sessionSecret, $requestSecret)) {
            echo json_encode(["success" => false, "error" => "unauthorized-error"]);
            http_response_code(401);
            exit;
        }
    }



    // プロフィール[一覧]取得用関数
    public function get_list()
    {
        try {
            $loginUserId = (int) \Fuel\Core\Session::get("user_id");

            if (!$loginUserId) {
                return $this->response(["success" => false, "error" => "unauthorized-error"], 401);
            }

            $profiles = \Model_UserProfile::list_profiles($loginUserId);
            return $this->response(["success" => true, "profiles" => $profiles], 200);

        } catch (\Throwable $e) {
            return $this->response(["success" => false, "error" => "internal error"], 500);
        }
    }

    
    // プロフィール取得用関数
    public function get_view()
    {
        try {
            $loginUserId = (int) \Fuel\Core\Session::get("user_id");
            
            if (!$loginUserId) {
                return $this->response(["success" => false, "error" => "unauthorized-error"], 401);
            }

            $name = \Fuel\Core\Input::get("name", null);
            if ($name === null || trim((string)$name) === "") {
                return $this->response(["success" => false, "error" => "name-required"], 400);
            }

            $profile = \Model_UserProfile::get_profile($loginUserId, (string)$name);
            return $this->response(["success" => true, "profile" => $profile], 200);

        } catch (\Throwable $e) {
            return $this->response(["success" => false, "error" => "internal error"], 500);
        }
    }


    // プロフィール追加・更新用関数
    public function post_index()
    {
        try {
            $loginUserId = (int) Session::get("user_id");
            
            if (!$loginUserId) {
                return $this->response(["success" => false, "error" => "unauthorized-error"], 401);
            }

            $rawInput = file_get_contents("php://input");
            if ($rawInput === false) {
                return $this->response(["success" => false, "error" => "failed to read input data"], 400);
            }

            $inputData = json_decode($rawInput, true);
            if ($inputData === null && trim($rawInput) !== "null") {
                return $this->response(["success" => false, "error" => "cannot parse JSON"], 400);
            }
            
            if (!is_array($inputData)) {
                $inputData = [];
            }

            $normalized = \Model_UserProfile::normalize($inputData);

            \Model_UserProfile::upsert(
                $loginUserId,
                $normalized["profile_name"],
                $normalized["avoid"],
                $normalized["cook_time"],
                $normalized["budget"]
            );

            return $this->response(["success" => true], 200);

        } catch (\DomainException $e) {
            
            if ((int)$e->getCode() === 1062 || $e->getMessage() === "duplicate_profile_name") {
                return $this->response(["success" => false, "error" => "duplicate profile_name"], 409);
            }
            
            return $this->response(["success" => false, "error" => "domain-error"], 400);

        } catch (\InvalidArgumentException $e) {
            return $this->response(["success" => false, "error" => $e->getMessage()], 400);

        } catch (\Throwable $e) {
            return $this->response(["success" => false, "error" => "internal-error"], 500);
        }
    }

    
    // プロフィール削除用関数
    public function delete_index()
    {
        try {
            # ユーザーIDの取得
            $loginUserId = (int) Session::get("user_id");
            if (!$loginUserId) {
                return $this->response(["success" => false, "error" => "unauthorized-error"], 401);
            }
            
            # CSRF対策(delete_token確認)
            $deleteToken = Input::post("delete_token");
            $sessionDeleteToken = Session::get("delete_token");
            if (!$deleteToken || !$sessionDeleteToken || !hash_equals($sessionDeleteToken, $deleteToken)) {
                return $this->response(["success" => false, "error" => "invalid-token"], 403);
            }

            # 再認証
            $inputPassword = (string) Input::post("password", "");
            $userData = \Model_User::find_by_id($loginUserId);
            
            if (!$userData || !password_verify($inputPassword, $userData["password_hash"])) {
                return $this->response(["success" => false, "error" => "authorization is failed"], 403);
            }

            $profileName = Input::post("profile_name", null);
            $profileId   = (int) Input::post("profile_id", 0);

            if (!is_null($profileName) && trim((string)$profileName) !== "") {
                \Model_UserProfile::delete_profile($loginUserId, (string)$profileName);

            } elseif ($profileId > 0) {
                DB::query("
                    DELETE FROM user_profile
                     WHERE id = :pid
                       AND user_id = :uid
                     LIMIT 1
                ")->parameters([
                    ":pid" => $profileId,
                    ":uid" => $loginUserId,
                ])->execute();

            } else {
                return $this->response(["success" => false, "error" => "profile_name or profile_id is required"], 400);
            }

            Session::delete("delete_token");
            return $this->response(["success" => true], 200);

        } catch (\Throwable $e) {
            return $this->response(["success" => false, "error" => "internal-error"], 500);
        }
    }
}
