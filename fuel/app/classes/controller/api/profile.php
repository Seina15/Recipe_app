<?php

use Fuel\Core\Controller_Rest;

// プロフィール情報を保存するAPI




class Controller_Api_Profile extends Controller_Rest
{
    protected $format = "json";

    public function before()
    {
        parent::before();
        \Response::set_header("Content-Type", "application/json; charset=utf-8");
        \Response::set_header("X-Frame-Options", "SAMEORIGIN");
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
        $sessionSecret = \Session::get("login_secret");
        
        if (!$sessionSecret || !$requestSecret || !hash_equals($sessionSecret, $requestSecret)) {
            echo json_encode(["success" => false, "error" => "unauthorized-error"]);
            http_response_code(401);
            exit;
        }
    }

    public function delete_index()
    {
        try {
            $loginUserId = \Session::get("user_id");
            
            if (!$loginUserId) {
                return $this->response(["success" => false, "error" => "unauthorized-error"], 401);
            }
            
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                return $this->response(["success" => false, "error" => "POST only"], 405);
            }
            $refererUrl = $_SERVER["HTTP_REFERER"] ?? "";
            
            if (strpos($refererUrl, "/home/profile") !== 0 && strpos($refererUrl, "http") !== 0) {
                return $this->response(["success" => false, "error" => "invalid referer"], 403);
            }
            $deleteToken = \Input::post("delete_token");
            $sessionDeleteToken = \Session::get("delete_token");
            
            if (!$deleteToken || !$sessionDeleteToken || !hash_equals($sessionDeleteToken, $deleteToken)) {
                return $this->response(["success" => false, "error" => "invalid token"], 403);
            }
            $inputPassword = \Input::post("password");
            $userData = \Model_User::find_by_id($loginUserId);
            
            if (!$userData || !password_verify($inputPassword, $userData["password_hash"])) {
                return $this->response(["success" => false, "error" => "re-auth failed"], 403);
            }
            $profileId = (int)\Input::post("profile_id", 0);
            
            if (!$profileId) {
                return $this->response(["success" => false, "error" => "profile_id required"], 400);
            }
            
            \Model_UserProfile::delete_for_user($loginUserId, $profileId);
            \Session::delete("delete_token");
            
            return $this->response(["success" => true], 200);
        
        } catch (\Throwable $e) {
            \Log::error($e->getMessage() . " " . $e->getFile() . ":" . $e->getLine());
            return $this->response(["success" => false, "error" => "internal error"], 500);
        }
    }


    // プロフィール情報を保存する関数
    public function post_index()
    {
        try {
            $loginUserId = \Session::get("user_id");
            
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
            
            $normalizedProfile = \Model_UserProfile::normalize($inputData);
            \Model_UserProfile::upsert((int)$loginUserId, $normalizedProfile["avoid"], $normalizedProfile["cook_time"], $normalizedProfile["budget"]);
            return $this->response(["success" => true], 200);
        
        } catch (\InvalidArgumentException $e) {
            return $this->response(["success" => false, "error" => $e->getMessage()], 400);
        
        } catch (\Throwable $e) {
            \Log::error($e->getMessage() . " " . $e->getFile() . ":" . $e->getLine());
            return $this->response(["success" => false, "error" => "internal error"], 500);
        }
    }
}
