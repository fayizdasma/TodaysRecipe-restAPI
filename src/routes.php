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

	$app->get('/recipes', function ($request, $response, $args) {
	    $sth = $this->db->prepare("SELECT * FROM ingredients_recipes_table");
	    $sth->execute();
	    $recipes = $sth->fetchAll();
	    return $this->response->withJson($recipes);
	});

      // search for recipe
    $app->get('/search?ingredients=[{ingredients}]', function ($request, $response, $args) {
        $sth = $this->db->prepare("SELECT r.recipe_id
                FROM recipe_table AS r
                INNER JOIN recipe_table AS ri ON r.recipe_id = ri.recipe_id
                INNER JOIN ingredients_table AS i ON i.id = ri.ingredient_id
                WHERE i.id IN (2,3)
                GROUP BY r.recipe_id");
        $sth->execute();
        $search = $sth->fetchAll();
        return $this->response->withJson(['status' => 1, 'error' => null,'data' => $search]);
    });

     // post search
    $app->post('/search', function ($request, $response) {
        $input = $request->getParsedBody();
        $ingredients_list = $input['ingredients'];
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
        // find the recipe's id containing those ingredients
        $sth2 = $this->db->prepare("SELECT r.recipe_id
                FROM recipe_table AS r
                INNER JOIN recipe_table AS ri ON r.recipe_id = ri.recipe_id
                INNER JOIN ingredients_table AS i ON i.id = ri.ingredient_id
                WHERE i.id IN ($new_string_ids)
                GROUP BY r.recipe_id");
        $sth2->execute();
        $recipe_id = $sth2->fetchAll();

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
    });
};