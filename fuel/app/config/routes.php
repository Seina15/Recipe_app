<?php
return array(
  "_root_"  => "home/index", 
  "_404_"   => "home/404",

  // テスト用
  // "api/recipe(.json)?"            => "api/recipe/index",
  "api/login(.json)?" => "api/login/index",
  "api/recommend_recipe/ranking" => "api/recommend_recipe/ranking",

  "api/profile/list(.:format)?" => "api/profile/list",
  "api/profile/view(.:format)?" => "api/profile/view", 
  "api/profile(.:format)?" => "api/profile/index", 

);
