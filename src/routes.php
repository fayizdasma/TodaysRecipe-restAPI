<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    $container = $app->getContainer();

    $app->get('/hello/[{name}]', function (Request $request, Response $response, array $args) use ($container) {
        // Sample log message
        $container->get('logger')->info("Slim-Skeleton '/' route");

        // Render index view
        return $container->get('renderer')->render($response, 'index.phtml', $args);
    });

    //get list of all recipes
    $app->get('/recipes', function ($request, $response, $args) {
       $sth = $this->db->prepare("SELECT * FROM ingredients_recipes_table");
       $sth->execute();
       $recipes = $sth->fetchAll();
       return $this->response->withJson($recipes);
   });

    //post search by passing ingredient names
    $app->post('/search', function ($request, $response) {
        $input = $request->getParsedBody();
        $ingredients_list = $input['ingredients'];
        $isExactSearch = $input['exactSearch'];
        $ingredients_id_array = array();
        // find the ingredient id
        foreach($ingredients_list as $item_name) {
            $sql = "SELECT id FROM ingredients_table WHERE ingredient_name = '$item_name'";
            $sth = $this->db->prepare($sql);
            $sth->execute();
            $ingredient_id = $sth->fetchAll();
            array_push($ingredients_id_array, $ingredient_id[0]['id']);
        }

        $string_ids="";
        // append ingredient for 'where in' clause
        foreach($ingredients_id_array as $ingredients_id) {
            $string_ids= $string_ids.','.$ingredients_id;
        }
        $new_string_ids = substr($string_ids, 1);
        // var_dump($new_string_ids);
        $sth2='';
        if ($isExactSearch){
            // find the recipe's id containing exclusievely those ingredients only
            $ingredients_count= count($ingredients_id_array);
            $sth2 = $this->db->prepare("SELECT ro.recipe_id FROM (SELECT DISTINCT r.recipe_id, count(ri.ingredient_id) AS icount FROM ingredients_table AS i INNER JOIN recipe_table AS ri ON i.id = ri.ingredient_id INNER JOIN ingredients_recipes_table AS r ON r.recipe_id = ri.recipe_id WHERE i.id IN ($new_string_ids) AND r.ingredient_count = $ingredients_count GROUP BY ri.recipe_id ORDER BY ri.recipe_id) AS ro WHERE ro.icount = $ingredients_count");
        }else{
            // find the recipe's id containing those ingredients
            $sth2 = $this->db->prepare("SELECT DISTINCT r.recipe_id
                FROM ingredients_table AS i
                INNER JOIN recipe_table AS ri ON i.id = ri.ingredient_id
                INNER JOIN ingredients_recipes_table AS r ON r.recipe_id = ri.recipe_id
                WHERE i.id IN ($new_string_ids)");
        }
        $sth2->execute();
        $recipe_id = $sth2->fetchAll();
        if(empty($recipe_id)){
            return $this->response->withJson(['status' => 0, 'error' => 'Sorry,no recipes found matching your ingredients','data' =>null]);
        }else{
          $string_ids="";
        // append ingredient for 'where in' clause
          foreach($recipe_id as $ids) {
            $string_ids= $string_ids.','.$ids['recipe_id'];
        }
        $new_string_ids = substr($string_ids, 1);

        // find the recipe from the id
        $sth3 = $this->db->prepare("SELECT *
            FROM ingredients_recipes_table 
            WHERE recipe_id IN ($new_string_ids)");
        $sth3->execute();
        $recipes = $sth3->fetchAll();
        return $this->response->withJson($recipes);
    }
});
};