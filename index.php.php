<?php
session_start();

/* PHP BACKEND: save the missing ingredients from the recipe page */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "save_missing") {
    header("Content-Type: application/json; charset=UTF-8");
    $items = $_POST["ingredients"] ?? [];
    if (!is_array($items)) $items = [];
    $items = array_values(array_unique(array_filter(array_map(
        fn($item) => trim(strip_tags((string)$item)),
        $items
    ), fn($item) => $item !== "")));
    $_SESSION["missing_ingredients"] = $items;
    echo json_encode(["count" => count($items), "ingredients" => $items]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GRANNY'S RECIPE</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Georgia,serif;background:#fff8ed;color:#422c20}
header{background:#633b27;color:white;text-align:center;padding:25px}
header h1{font-size:38px;margin:5px}
header p{font-size:17px}
nav{background:#e8c9a5;padding:12px;text-align:center}
nav button,.card button,.action{background:#633b27;color:white;border:0;padding:10px 18px;margin:5px;border-radius:5px;cursor:pointer}
nav button:hover,.card button:hover,.action:hover{background:#936344}
.page{display:none;padding:25px}.active{display:block}
h2{text-align:center;color:#633b27}
.intro{text-align:center;font-size:18px;line-height:1.7;max-width:850px;margin:auto}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:22px;margin-top:25px}
.card{background:white;border-radius:12px;overflow:hidden;box-shadow:0 3px 10px #0002;text-align:center;padding-bottom:15px}
.card img{width:100%;height:180px;object-fit:cover;background:#f1dfca}
.card h3{color:#633b27;padding:0 8px}
.recipe-image{display:block;width:100%;max-width:650px;height:350px;object-fit:cover;border-radius:12px;margin:20px auto;background:#f1dfca}
.recipe-info{text-align:center;background:#f1dfca;padding:15px;border-radius:8px;margin:15px auto;max-width:750px}
.recipe-content{max-width:850px;margin:auto;line-height:1.8}
.recipe-content h3{color:#633b27}
.ingredient{display:block;margin:8px 0}
input[type=checkbox]{accent-color:#633b27;margin-right:10px}
.tip{background:#f1dfca;padding:15px;border-left:5px solid #936344;margin-top:20px}
#shoppingResult{display:none;background:#fff;padding:15px;border-radius:8px;margin-top:15px}
footer{background:#633b27;color:white;text-align:center;padding:18px;margin-top:30px}
.small-note{font-size:13px;color:#765b49;text-align:center}
@media(max-width:600px){header h1{font-size:28px}.page{padding:15px}.recipe-image{height:230px}}
</style>
</head>
<body>
<header><h1>GRANNY'S RECIPE</h1><p>Every recipe has a story, every meal has a memory!</p></header>
<nav><button onclick="showHome()">Home</button><button onclick="showCuisines()">Explore Cuisines</button></nav>

<section id="home" class="page active">
<h2>Welcome to Granny's Kitchen!</h2>
<p class="intro">Food is more than just something we eat. It brings families together and creates beautiful memories. Granny's Kitchen brings you delicious recipes from different parts of the world. Explore different cuisines, discover famous dishes and learn how to prepare them at home with love.</p>
<img class="recipe-image" src="https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=1000" alt="Home cooking">
<div style="text-align:center"><button class="action" onclick="showCuisines()">Explore Recipes</button></div>
</section>
<section id="cuisines" class="page"><h2>Explore World Cuisines</h2><p class="intro">Choose a cuisine to discover its famous dishes.</p><div class="grid" id="cuisineGrid"></div></section>
<section id="foods" class="page"><h2 id="cuisineTitle"></h2><p class="intro">Choose a dish to view its recipe.</p><div class="grid" id="foodGrid"></div></section>
<section id="recipe" class="page">
<div class="recipe-content">
<h2 id="recipeTitle"></h2>
<img id="recipeImage" class="recipe-image" src="" alt="">
<p class="small-note" id="imageNote">Recipe image selected for this dish. Image results are loaded by the exact dish name.</p>
<div class="recipe-info" id="recipeTime"></div>
<h3>Ingredients</h3><p>Tick the ingredients you already have:</p><div id="ingredientList"></div>
<button class="action" onclick="buyMissing()">Shop Missing Ingredients</button>
<div id="shoppingResult"></div>
<h3>Preparation Method</h3><ol id="recipeSteps"></ol>
<div class="tip"><h3>Granny's Tip</h3><p id="recipeTip"></p></div>
</div></section>
<footer><p>GRANNY'S RECIPE | Made with Love ❤️</p></footer>

<script>
const cuisines = [
{name:"Chinese Cuisine",imageQuery:"Chinese cuisine dishes"},
{name:"North Indian Cuisine",imageQuery:"North Indian thali food"},
{name:"South Indian Cuisine",imageQuery:"South Indian dosa idli food"},
{name:"Thai Cuisine",imageQuery:"Thai cuisine food"},
{name:"Korean Cuisine",imageQuery:"Korean cuisine food"},
{name:"American Cuisine",imageQuery:"American cuisine food"},
{name:"Italian Cuisine",imageQuery:"Italian cuisine food"},
{name:"French Cuisine",imageQuery:"French cuisine food"}
];

/* Each recipe has a dish-specific image search term. Wikimedia Commons is queried
   for that exact dish, rather than reusing a generic or unrelated food photo. */
const recipeData = {
"Chinese Cuisine": [
["Veg Fried Rice","15 minutes","15 minutes","30 minutes",["2 cups cooked rice","1 carrot, diced","1/2 cup beans","1/2 cup cabbage","2 tbsp soy sauce","1 tbsp oil","Salt"],["Chop the vegetables finely.","Heat oil in a wok and stir-fry the vegetables.","Add the cooked rice and soy sauce.","Season with salt, toss well and serve hot."],"Use cooled rice so the grains stay separate."],
["Veg Manchurian","20 minutes","25 minutes","45 minutes",["1 cup grated cabbage","1/2 cup grated carrot","3 tbsp flour","2 tbsp cornflour","2 tbsp soy sauce","1 tbsp tomato sauce","Garlic","Oil and salt"],["Mix cabbage, carrot, flour, cornflour and salt.","Shape into small balls and cook until golden.","Sauté garlic; add soy sauce, tomato sauce and a little water.","Add the balls and coat them in the sauce."],"Add the balls to the sauce just before serving."],
["Hakka Noodles","15 minutes","15 minutes","30 minutes",["200 g noodles","1 carrot","1 capsicum","1 onion","2 tbsp soy sauce","1 tbsp oil","Pepper and salt"],["Boil noodles until just tender and drain.","Heat oil and stir-fry sliced vegetables.","Add noodles, soy sauce, pepper and salt.","Toss over high heat and serve."],"Do not overcook the noodles."],
["Spring Rolls","25 minutes","15 minutes","40 minutes",["8 spring roll wrappers","1 cup shredded cabbage","1 carrot","1/2 capsicum","1 tbsp soy sauce","1 tsp cornflour","Oil"],["Stir-fry the vegetables with soy sauce until just tender.","Cool the filling completely.","Place filling on each wrapper and fold the sides in.","Seal with cornflour paste and cook until crisp."],"Cool the filling before wrapping to keep wrappers crisp."],
["Hot and Sour Soup","15 minutes","15 minutes","30 minutes",["4 cups vegetable stock","1/2 cup cabbage","1 carrot","4 mushrooms","1 tbsp soy sauce","1 tbsp vinegar","1 tbsp cornflour","Pepper"],["Bring vegetable stock to a simmer.","Add finely sliced vegetables and cook until tender.","Stir in soy sauce, vinegar and pepper.","Add cornflour mixed with water and simmer until lightly thickened."],"Adjust vinegar and pepper to your taste."],
["Schezwan Fried Rice","15 minutes","15 minutes","30 minutes",["2 cups cooked rice","1/2 cup mixed vegetables","2 tbsp Schezwan sauce","2 spring onions","1 tbsp oil","Salt"],["Heat oil in a wok.","Stir-fry vegetables and the white parts of spring onion.","Add rice and Schezwan sauce.","Toss thoroughly and finish with spring onion greens."],"Add sauce gradually because its heat level varies."],
["Chilli Paneer","15 minutes","20 minutes","35 minutes",["200 g paneer cubes","1 capsicum","1 onion","1 tbsp soy sauce","1 tbsp chilli sauce","1 tbsp tomato ketchup","1 tsp cornflour","Oil"],["Lightly pan-sear the paneer cubes.","Stir-fry onion and capsicum in a little oil.","Add soy sauce, chilli sauce and ketchup.","Add paneer and cornflour slurry; toss until coated."],"Keep the capsicum slightly crunchy."],
["Vegetable Momos","30 minutes","15 minutes","45 minutes",["1 cup flour","1 cup finely chopped cabbage","1 carrot","2 spring onions","1 tsp ginger","Salt","Water"],["Knead flour, water and a pinch of salt into a firm dough.","Mix vegetables, ginger and salt for the filling.","Roll small wrappers, add filling and pleat closed.","Steam for about 12–15 minutes and serve with chutney."],"Roll the wrappers thinly for delicate momos."],
["Sweet and Sour Vegetables","15 minutes","15 minutes","30 minutes",["2 cups mixed vegetables","2 tbsp tomato ketchup","1 tbsp vinegar","1 tbsp soy sauce","1 tbsp sugar","1 tsp cornflour","1 tbsp oil"],["Cut vegetables into bite-sized pieces.","Stir-fry them in hot oil until tender-crisp.","Mix ketchup, vinegar, soy sauce, sugar and cornflour with water.","Pour sauce over vegetables and simmer until glossy."],"Keep vegetables colourful and slightly crisp."],
["Vegetable Chow Mein","15 minutes","15 minutes","30 minutes",["200 g noodles","1 cup cabbage","1 carrot","1 capsicum","2 tbsp soy sauce","1 tsp vinegar","1 tbsp oil"],["Boil noodles, drain and toss with a little oil.","Stir-fry sliced vegetables in a hot pan.","Add noodles, soy sauce and vinegar.","Toss well and serve immediately."],"A hot pan helps give the noodles a good stir-fried texture."]
],
"North Indian Cuisine": [
["Paneer Butter Masala","15 minutes","25 minutes","40 minutes",["200 g paneer","2 tomatoes","1 onion","1 tbsp butter","1/2 cup cream","1 tsp ginger-garlic paste","1 tsp garam masala","Salt"],["Cook onion and tomatoes with ginger-garlic paste.","Cool and blend into a smooth paste.","Heat butter, add the paste and spices.","Add cream and paneer; simmer gently for 5 minutes."],"Add cream at the end for a smooth gravy."],
["Chole Masala","8 hours soaking","40 minutes","8 hours 40 minutes",["1 cup chickpeas","2 onions","2 tomatoes","1 tsp ginger-garlic paste","1 tsp chole masala","1/2 tsp turmeric","Oil and salt"],["Soak chickpeas overnight and pressure-cook until soft.","Cook chopped onions in oil.","Add ginger-garlic paste, tomatoes and spices.","Add chickpeas and water; simmer for 15 minutes."],"Mash a few chickpeas to thicken the gravy."],
["Aloo Paratha","20 minutes","20 minutes","40 minutes",["2 cups wheat flour","3 potatoes","1 onion","1 tsp chilli powder","1/2 tsp garam masala","Coriander leaves","Ghee and salt"],["Boil and mash the potatoes.","Mix with onion, spices, coriander and salt.","Knead flour with water into a soft dough.","Fill dough portions, roll gently and cook with ghee."],"Seal the filling well before rolling."],
["Dal Makhani","8 hours soaking","60 minutes","9 hours",["1/2 cup whole black urad dal","2 tbsp kidney beans","2 tomatoes","1 tbsp butter","1 tsp ginger-garlic paste","1/4 cup cream","Salt"],["Soak dal and kidney beans overnight.","Pressure-cook until very soft.","Cook tomato, ginger-garlic paste and spices in butter.","Add cooked dal and simmer slowly; finish with cream."],"Slow simmering develops a richer flavour."],
["Palak Paneer","15 minutes","25 minutes","40 minutes",["250 g spinach","200 g paneer","1 onion","1 tomato","1 tsp ginger-garlic paste","1 tbsp cream","Garam masala and salt"],["Blanch spinach briefly and cool it.","Blend spinach into a smooth purée.","Cook onion, tomato and ginger-garlic paste.","Add spinach purée, spices and paneer; simmer and finish with cream."],"Do not overcook spinach if you want a bright green colour."],
["Rajma Masala","8 hours soaking","45 minutes","8 hours 45 minutes",["1 cup kidney beans","2 onions","2 tomatoes","1 tsp ginger-garlic paste","1 tsp cumin","1 tsp garam masala","Oil and salt"],["Soak kidney beans overnight.","Pressure-cook until completely tender.","Prepare a masala with cumin, onion, ginger-garlic and tomato.","Add beans and cooking liquid; simmer until thick."],"Rajma should be soft all the way through."],
["Butter Naan","60 minutes resting","20 minutes","1 hour 20 minutes",["2 cups flour","1/2 cup curd","1 tsp sugar","1/2 tsp baking powder","1/4 tsp baking soda","Water","Butter and salt"],["Mix flour, curd, sugar, raising agents and salt.","Add water gradually and knead a soft dough.","Cover and rest for about an hour.","Roll naan and cook on a hot tawa; brush with butter."],"Resting makes the dough easier to roll."],
["Malai Kofta","25 minutes","30 minutes","55 minutes",["2 potatoes, boiled","100 g paneer","2 tbsp cornflour","2 tomatoes","1 onion","1/4 cup cream","Cashews and spices"],["Mash potato and paneer with cornflour; shape into balls.","Cook the kofta balls until golden and set aside.","Prepare a smooth onion-tomato-cashew gravy.","Add cream and place koftas in the gravy just before serving."],"Add koftas to the gravy shortly before serving."],
["Vegetable Biryani","20 minutes","35 minutes","55 minutes",["1.5 cups basmati rice","2 cups mixed vegetables","1 onion","1/2 cup curd","Biryani masala","Mint","Coriander","Oil and salt"],["Wash and soak rice for 20 minutes.","Cook onion, vegetables, curd and biryani masala.","Add rice and measured water.","Cover and cook on low heat until rice is tender; rest before serving."],"Let the biryani rest covered for 10 minutes."],
["Kadhi Pakora","15 minutes","35 minutes","50 minutes",["1 cup curd","3 tbsp gram flour","1 onion","1/2 tsp turmeric","1 tsp chilli powder","1/2 tsp mustard seeds","Curry leaves","Salt"],["Whisk curd, gram flour, turmeric and water until smooth.","Simmer gently, stirring often, until the kadhi thickens.","Make small onion pakoras with gram flour and cook until golden.","Add pakoras to kadhi and finish with a mustard-seed tempering."],"Stir continuously at first to keep the kadhi smooth."]
],
"South Indian Cuisine": [
["Masala Dosa","10 minutes","20 minutes","30 minutes",["2 cups dosa batter","3 potatoes","1 onion","1/2 tsp mustard seeds","Curry leaves","Turmeric","Oil and salt"],["Boil and mash potatoes.","Temper mustard seeds and curry leaves; cook onion and potato with turmeric.","Spread batter thinly on a hot tawa.","Add potato masala, fold and serve."],"Make sure the tawa is hot before spreading batter."],
["Idli Sambar","Overnight fermentation","20 minutes","Overnight + 20 minutes",["2 cups idli batter","1/2 cup toor dal","1 tomato","1 onion","Sambar powder","Tamarind","Mustard seeds","Salt"],["Pour batter into greased idli moulds and steam for 10–12 minutes.","Pressure-cook toor dal until soft.","Cook onion and tomato with sambar powder.","Add tamarind water and dal; simmer and serve with idlis."],"Allow idlis to cool slightly before removing from moulds."],
["Ven Pongal","10 minutes","25 minutes","35 minutes",["1/2 cup rice","1/4 cup moong dal","1 tsp pepper","1 tsp cumin","Cashews","Ghee","Curry leaves","Salt"],["Wash rice and dal; lightly roast the dal.","Pressure-cook rice and dal with water until creamy.","Heat ghee and add pepper, cumin, cashews and curry leaves.","Pour seasoning over pongal and mix."],"A little extra ghee gives a traditional aroma."],
["Medu Vada","4 hours soaking","25 minutes","4 hours 25 minutes",["1 cup urad dal","1 green chilli","1 tsp grated ginger","Curry leaves","Black pepper","Salt","Oil"],["Soak urad dal for about 4 hours and drain well.","Grind with very little water to a thick fluffy batter.","Mix in chilli, ginger, curry leaves, pepper and salt.","Shape into rings and cook in hot oil until golden."],"Keep the batter thick so the vadas hold their shape."],
["Vegetable Upma","10 minutes","15 minutes","25 minutes",["1 cup rava","1 onion","1 carrot","1/4 cup peas","1 tsp mustard seeds","Curry leaves","2 tbsp oil","Salt"],["Dry-roast rava lightly and set aside.","Temper mustard seeds and curry leaves in oil.","Cook onion and vegetables until tender.","Add water and salt; stir in rava gradually and cook until fluffy."],"Roast rava gently to prevent a raw taste."],
["Appam with Vegetable Stew","Overnight fermentation","25 minutes","Overnight + 25 minutes",["2 cups appam batter","1 cup coconut milk","1 carrot","1 potato","1/2 cup beans","1 onion","Curry leaves","Salt"],["Prepare or ferment appam batter until airy.","Cook vegetables with onion and curry leaves until tender.","Add coconut milk and simmer gently.","Pour batter into an appam pan, swirl and cook covered; serve with stew."],"Do not boil coconut milk vigorously."],
["Lemon Rice","10 minutes","15 minutes","25 minutes",["2 cups cooked rice","1 lemon","1 tsp mustard seeds","1 tbsp peanuts","2 dried red chillies","Curry leaves","Turmeric","Oil and salt"],["Cool the cooked rice so the grains remain separate.","Heat oil; add mustard seeds, peanuts, chillies and curry leaves.","Add turmeric and switch off the heat.","Mix in rice, salt and lemon juice."],"Add lemon juice after turning off the heat."],
["Curd Rice","10 minutes","15 minutes","25 minutes",["1 cup rice","1 cup curd","1/4 cup milk","1 tsp mustard seeds","Ginger","Curry leaves","Coriander","Salt"],["Cook rice until soft and mash lightly.","Cool until warm, then mix in milk and curd.","Prepare a tempering with mustard seeds, ginger and curry leaves.","Mix the tempering into rice and garnish with coriander."],"Add milk to keep the curd rice creamy."],
["Pesarattu","4 hours soaking","20 minutes","4 hours 20 minutes",["1 cup whole green gram","2 tbsp rice","1 green chilli","1 inch ginger","1 onion","Cumin","Salt","Oil"],["Soak green gram and rice for about 4 hours.","Grind with chilli, ginger, cumin and water to a pourable batter.","Spread a ladle of batter on a hot tawa.","Sprinkle onion and cook both sides until crisp."],"The batter does not need fermentation."],
["Rasam","10 minutes","20 minutes","30 minutes",["1 tomato","1 tbsp tamarind pulp","2 tbsp cooked toor dal","1 tsp rasam powder","1/2 tsp mustard seeds","Curry leaves","Coriander","Salt"],["Simmer tomato with tamarind water and turmeric.","Add rasam powder, salt and cooked dal.","Add water and heat until frothy; avoid a hard boil.","Temper mustard seeds and curry leaves and pour over rasam."],"Switch off when the rasam becomes frothy and aromatic."]
],
"Thai Cuisine": [
["Pad Thai","15 minutes","15 minutes","30 minutes",["200 g rice noodles","1 carrot","1 capsicum","2 tbsp soy sauce","1 tbsp lime juice","1 tbsp sugar","Peanuts","Oil"],["Soak rice noodles until pliable.","Stir-fry vegetables in a hot pan.","Add noodles and a sauce of soy, lime and sugar.","Toss until noodles are tender and garnish with peanuts."],"Do not soak noodles for too long."],
["Thai Green Curry","15 minutes","25 minutes","40 minutes",["1 cup coconut milk","1 tbsp green curry paste","1 capsicum","1 carrot","1/2 cup beans","1 tbsp soy sauce","Basil","Salt"],["Heat a little coconut milk in a pan.","Stir in green curry paste until fragrant.","Add remaining coconut milk and vegetables.","Simmer until tender; season and finish with basil."],"Keep vegetables slightly firm for texture."],
["Mango Sticky Rice","20 minutes","25 minutes","45 minutes",["1 cup sticky rice","1 ripe mango","1 cup coconut milk","3 tbsp sugar","Pinch of salt","Sesame seeds"],["Wash and soak sticky rice.","Steam until soft and translucent.","Warm coconut milk with sugar and salt.","Mix some coconut sauce into rice; serve with sliced mango and remaining sauce."],"Use a ripe sweet mango."],
["Tom Yum Soup","10 minutes","20 minutes","30 minutes",["4 cups vegetable stock","2 lemongrass stalks","3 kaffir lime leaves","1 cup mushrooms","2 tbsp lime juice","1 tbsp soy sauce","Chilli","Coriander"],["Simmer stock with bruised lemongrass and lime leaves.","Add mushrooms and cook until tender.","Season with soy sauce, chilli and lime juice.","Remove tough aromatics and garnish with coriander."],"Add lime juice near the end to keep its fresh flavour."],
["Thai Basil Fried Rice","15 minutes","15 minutes","30 minutes",["2 cups cooked jasmine rice","1 cup mixed vegetables","2 garlic cloves","1 tbsp soy sauce","1 tsp sugar","Fresh basil","Oil"],["Heat oil and sauté garlic briefly.","Add vegetables and stir-fry until just tender.","Add rice, soy sauce and sugar.","Toss over high heat; fold in basil just before serving."],"Use cold rice for separate grains."],
["Som Tam Green Papaya Salad","20 minutes","0 minutes","20 minutes",["2 cups shredded green papaya","1 carrot","6 cherry tomatoes","1 tbsp lime juice","1 tbsp soy sauce","1 tsp sugar","Roasted peanuts"],["Shred the green papaya and carrot.","Lightly crush tomatoes to release their juice.","Mix lime juice, soy sauce and sugar.","Toss everything together and top with peanuts."],"Dress the salad just before serving."],
["Thai Peanut Noodles","15 minutes","15 minutes","30 minutes",["200 g noodles","3 tbsp peanut butter","1 tbsp soy sauce","1 tbsp lime juice","1 tsp honey","1 carrot","Spring onion"],["Cook noodles according to packet instructions.","Whisk peanut butter, soy sauce, lime and honey with warm water.","Toss noodles with sliced carrot and the sauce.","Garnish with spring onion."],"Loosen the sauce with warm water until pourable."],
["Thai Red Curry","15 minutes","25 minutes","40 minutes",["1 tbsp red curry paste","1 cup coconut milk","1 cup tofu cubes","1 capsicum","1 cup bamboo shoots","Basil","Soy sauce"],["Warm curry paste in a pan with a little coconut milk.","Add remaining coconut milk and bring to a gentle simmer.","Add tofu, capsicum and bamboo shoots.","Cook until vegetables are tender; season and add basil."],"Simmer gently so the coconut milk stays smooth."],
["Thai Vegetable Spring Rolls","25 minutes","15 minutes","40 minutes",["8 spring roll wrappers","1 cup cabbage","1 carrot","1/2 cup bean sprouts","1 tbsp soy sauce","1 tsp cornflour","Oil"],["Stir-fry shredded vegetables and soy sauce briefly.","Cool the filling.","Roll filling tightly in wrappers and seal with cornflour paste.","Cook until golden and crisp."],"Avoid overfilling the wrappers."],
["Coconut Thai Soup","10 minutes","20 minutes","30 minutes",["2 cups coconut milk","2 cups vegetable stock","1 cup mushrooms","1 carrot","1 tbsp ginger","1 tbsp lime juice","Soy sauce","Coriander"],["Bring stock and coconut milk to a gentle simmer.","Add ginger, mushrooms and sliced carrot.","Cook until vegetables are tender.","Season with soy sauce and lime; garnish with coriander."],"Keep the soup at a gentle simmer."]
],
"Korean Cuisine": [
["Bibimbap","20 minutes","20 minutes","40 minutes",["2 cups cooked rice","1 carrot","1 cucumber","1 cup spinach","1 egg","1 tbsp soy sauce","1 tbsp sesame oil","Gochujang"],["Cook rice and keep warm.","Prepare carrot, cucumber and spinach separately.","Cook the egg to your preference.","Arrange vegetables over rice and serve with gochujang and sesame oil."],"Arrange toppings separately for a colourful bowl."],
["Korean Vegetable Pancake","10 minutes","15 minutes","25 minutes",["1 cup flour","1 carrot","1 capsicum","1 onion","3/4 cup water","Salt","Oil"],["Slice vegetables thinly.","Mix flour, water and salt into a batter.","Fold vegetables into the batter.","Spread in an oiled pan and cook both sides until crisp."],"Thinly sliced vegetables cook evenly."],
["Kimchi Fried Rice","10 minutes","15 minutes","25 minutes",["2 cups cooked rice","1/2 cup kimchi","1 onion","1 tbsp soy sauce","1 tsp sesame oil","Spring onion"],["Chop kimchi and onion.","Cook onion in a little oil.","Add kimchi and stir-fry briefly.","Add rice and soy sauce; toss and finish with sesame oil and spring onion."],"Day-old rice works well."],
["Japchae","20 minutes","15 minutes","35 minutes",["200 g sweet potato glass noodles","1 carrot","1 capsicum","1 cup spinach","2 tbsp soy sauce","1 tbsp sesame oil","Sesame seeds"],["Cook glass noodles according to packet directions and drain.","Stir-fry carrot, capsicum and spinach separately.","Toss noodles with soy sauce and sesame oil.","Combine with vegetables and sprinkle sesame seeds."],"Do not overcook the glass noodles."],
["Tteokbokki","10 minutes","20 minutes","30 minutes",["300 g Korean rice cakes","2 tbsp gochujang","1 tbsp soy sauce","1 tbsp sugar","2 cups water or stock","Spring onion","Sesame seeds"],["Soak rice cakes if they are firm.","Bring water or stock to a simmer.","Whisk in gochujang, soy sauce and sugar.","Add rice cakes and simmer until soft and sauce thickens; garnish."],"Stir regularly so the rice cakes do not stick."],
["Korean Cucumber Salad","10 minutes","0 minutes","10 minutes",["2 cucumbers","1 tsp salt","1 tbsp rice vinegar","1 tsp sesame oil","1 tsp gochugaru","1 tsp sugar","Sesame seeds"],["Slice cucumbers thinly and toss with salt.","Rest briefly, then drain excess liquid.","Mix vinegar, sesame oil, gochugaru and sugar.","Toss cucumbers in dressing and sprinkle sesame seeds."],"Chill briefly for a refreshing side dish."],
["Korean Glass Noodle Soup","15 minutes","20 minutes","35 minutes",["150 g glass noodles","4 cups vegetable stock","1 carrot","1 cup mushrooms","2 tbsp soy sauce","Spring onion","Sesame oil"],["Soak or cook glass noodles as directed.","Bring stock to a simmer.","Add carrot and mushrooms and cook until tender.","Add noodles and soy sauce; finish with sesame oil and spring onion."],"Add noodles near the end so they keep their texture."],
["Korean Potato Pancakes","15 minutes","20 minutes","35 minutes",["3 potatoes","1 tbsp flour","1/2 onion","Salt","Pepper","Oil"],["Grate potatoes and onion.","Drain excess liquid, reserving the starch if desired.","Mix with flour, salt and pepper.","Spoon into a hot oiled pan and cook both sides until crisp."],"Drain excess moisture for crisp pancakes."],
["Korean Soy Garlic Tofu","15 minutes","15 minutes","30 minutes",["250 g firm tofu","2 tbsp soy sauce","1 tbsp honey","2 garlic cloves","1 tsp sesame oil","1 tsp cornflour","Spring onion"],["Pat tofu dry and cut into cubes.","Pan-sear tofu until golden.","Simmer soy sauce, honey, garlic and a little water.","Add tofu and cornflour slurry; coat and garnish."],"Dry tofu well before pan-searing."],
["Korean Vegetable Kimbap","25 minutes","10 minutes","35 minutes",["2 cups cooked short-grain rice","4 gim seaweed sheets","1 carrot","1 cucumber","1 egg","Pickled radish","Sesame oil"],["Season warm rice with sesame oil and salt.","Prepare thin strips of carrot, cucumber, egg and radish.","Spread rice over seaweed and arrange fillings in a line.","Roll firmly and slice with a lightly oiled knife."],"Do not overfill the roll."]
],
"American Cuisine": [
["Classic Veggie Burger","15 minutes","15 minutes","30 minutes",["2 burger buns","2 vegetable patties","2 cheese slices","Lettuce","1 tomato","1 onion","Mayonnaise"],["Cook patties until hot and golden.","Toast the buns lightly.","Spread mayonnaise on the buns.","Layer lettuce, tomato, onion, patty and cheese; close and serve."],"Toast buns lightly to prevent sogginess."],
["Pancakes","10 minutes","15 minutes","25 minutes",["1 cup flour","1 egg","3/4 cup milk","2 tbsp sugar","1 tsp baking powder","1 tbsp butter","Honey"],["Mix flour, sugar and baking powder.","Whisk in egg and milk until just combined.","Heat a lightly buttered pan and pour a small ladle of batter.","Flip when bubbles appear and cook the other side."],"Do not overmix the batter."],
["Mac and Cheese","10 minutes","20 minutes","30 minutes",["2 cups macaroni","1 cup milk","1 cup grated cheese","1 tbsp butter","1 tbsp flour","Pepper and salt"],["Boil macaroni until tender and drain.","Melt butter and stir in flour.","Slowly whisk in milk until smooth and thickened.","Add cheese, then stir in macaroni and season."],"Add cheese gradually for a smooth sauce."],
["Grilled Cheese Sandwich","5 minutes","8 minutes","13 minutes",["4 bread slices","4 cheese slices","2 tbsp butter"],["Place cheese between bread slices.","Butter the outside of each sandwich.","Cook in a pan over medium-low heat.","Turn when golden and cook until cheese melts."],"Use medium-low heat so the cheese melts before bread browns too much."],
["Coleslaw","15 minutes","0 minutes","15 minutes",["2 cups shredded cabbage","1 carrot","1/4 cup mayonnaise","1 tsp vinegar","1 tsp sugar","Salt and pepper"],["Shred cabbage and carrot finely.","Whisk mayonnaise, vinegar, sugar, salt and pepper.","Mix dressing through the vegetables.","Chill before serving."],"Chill for at least 15 minutes for better flavour."],
["Baked Potato Wedges","10 minutes","35 minutes","45 minutes",["4 potatoes","2 tbsp olive oil","1 tsp paprika","1/2 tsp garlic powder","Salt and pepper"],["Heat oven to 220°C.","Cut potatoes into wedges and pat dry.","Toss with oil and seasonings.","Arrange in one layer and bake, turning once, until crisp."],"Dry potatoes well before seasoning."],
["Corn Chowder","15 minutes","25 minutes","40 minutes",["2 cups sweet corn","1 potato","1 onion","2 cups vegetable stock","1 cup milk","1 tbsp butter","Salt and pepper"],["Cook onion in butter until soft.","Add diced potato, corn and stock.","Simmer until potato is tender.","Stir in milk, season and lightly mash some corn to thicken."],"Mash a little of the potato and corn for a thicker soup."],
["Apple Pie","25 minutes","50 minutes","1 hour 15 minutes",["2 pie crusts","4 apples","1/2 cup sugar","1 tsp cinnamon","1 tbsp flour","1 tbsp lemon juice"],["Heat oven to 190°C.","Slice apples and toss with sugar, cinnamon, flour and lemon.","Fill the pie crust and cover with the top crust.","Cut steam vents and bake until golden and bubbling."],"Let the pie cool before slicing."],
["Macaroni Salad","15 minutes","10 minutes","25 minutes",["2 cups elbow macaroni","1/2 cup capsicum","1/2 cup corn","1/4 cup mayonnaise","1 tsp mustard","1 tsp vinegar","Salt and pepper"],["Cook macaroni and rinse under cool water.","Chop capsicum and prepare corn.","Mix mayonnaise, mustard, vinegar, salt and pepper.","Fold dressing and vegetables through macaroni; chill."],"Chill before serving."],
["Blueberry Muffins","15 minutes","22 minutes","37 minutes",["1 1/2 cups flour","3/4 cup sugar","1 tsp baking powder","1 egg","1/2 cup milk","1/3 cup oil","1 cup blueberries"],["Heat oven to 180°C and line a muffin tray.","Mix flour, sugar and baking powder.","Whisk egg, milk and oil; fold into dry ingredients.","Fold in blueberries and bake until a toothpick comes out clean."],"Fold gently to keep muffins light."]
],
"Italian Cuisine": [
["Margherita Pizza","20 minutes","15 minutes","35 minutes",["1 pizza base","1/2 cup pizza sauce","1 cup mozzarella","1 tomato","Basil leaves","Olive oil"],["Heat oven according to the pizza base instructions.","Spread sauce over the base.","Add mozzarella and tomato slices.","Bake until crust is golden; finish with basil and olive oil."],"Avoid too much sauce so the base stays crisp."],
["Pasta Alfredo","10 minutes","20 minutes","30 minutes",["200 g pasta","1 cup milk","1/2 cup cream","2 tbsp butter","1/2 cup cheese","2 garlic cloves","Pepper and salt"],["Boil pasta in salted water.","Melt butter and sauté garlic.","Add milk and cream and warm gently.","Stir in cheese, season and toss with pasta."],"Save a little pasta water to loosen the sauce."],
["Bruschetta","10 minutes","5 minutes","15 minutes",["1 baguette","2 tomatoes","2 garlic cloves","Fresh basil","2 tbsp olive oil","Salt and pepper"],["Chop tomatoes and basil.","Mix with olive oil, salt and pepper.","Slice and toast baguette.","Rub toast with garlic and spoon tomato mixture on top."],"Add topping just before serving."],
["Mushroom Risotto","10 minutes","30 minutes","40 minutes",["1 cup arborio rice","200 g mushrooms","1 onion","4 cups warm vegetable stock","2 tbsp butter","1/4 cup parmesan"],["Sauté onion and mushrooms in butter.","Add rice and stir until lightly translucent.","Add warm stock one ladle at a time, stirring often.","When creamy and tender, stir in parmesan."],"Add stock gradually for a creamy texture."],
["Pesto Pasta","10 minutes","15 minutes","25 minutes",["200 g pasta","1/2 cup basil pesto","2 tbsp parmesan","1 tbsp olive oil","Cherry tomatoes","Salt"],["Boil pasta until al dente and reserve some pasta water.","Warm pesto gently with olive oil.","Add pasta and a splash of pasta water.","Toss with parmesan and cherry tomatoes."],"Do not cook pesto over high heat."],
["Lasagna","25 minutes","45 minutes","1 hour 10 minutes",["Lasagna sheets","2 cups tomato sauce","1 cup ricotta","1 cup mozzarella","1/2 cup parmesan","1 zucchini","Italian herbs"],["Heat oven to 180°C.","Spread tomato sauce in a baking dish.","Layer sheets, ricotta, vegetables, sauce and cheese.","Repeat layers and bake covered, then uncovered until bubbling."],"Rest lasagna before cutting neat portions."],
["Caprese Salad","10 minutes","0 minutes","10 minutes",["2 tomatoes","200 g fresh mozzarella","Fresh basil","2 tbsp olive oil","Balsamic vinegar","Salt and pepper"],["Slice tomatoes and mozzarella.","Arrange slices with basil leaves.","Drizzle with olive oil and balsamic vinegar.","Season and serve fresh."],"Use ripe tomatoes for the best flavour."],
["Gnocchi in Tomato Sauce","20 minutes","15 minutes","35 minutes",["500 g potato gnocchi","2 cups tomato sauce","2 garlic cloves","1 tbsp olive oil","Basil","Parmesan"],["Cook gnocchi according to packet instructions.","Sauté garlic in olive oil.","Add tomato sauce and simmer briefly.","Toss gnocchi through sauce and finish with basil and parmesan."],"Remove gnocchi when they float, if cooking fresh."],
["Focaccia","20 minutes plus rising","25 minutes","45 minutes plus rising",["3 cups flour","1 tsp yeast","1 tsp sugar","1 cup warm water","3 tbsp olive oil","Rosemary","Salt"],["Mix flour, yeast, sugar, water and salt into a soft dough.","Knead and let rise until doubled.","Press into an oiled tray and dimple the surface.","Add olive oil, rosemary and salt; bake until golden."],"Generous olive oil helps create the classic crust."],
["Tiramisu","25 minutes","0 minutes","25 minutes plus chilling",["1 cup mascarpone","1 cup cream","2 tbsp sugar","Ladyfinger biscuits","1 cup cooled coffee","Cocoa powder"],["Whip cream and sugar until soft peaks form.","Fold in mascarpone until smooth.","Dip biscuits briefly in cooled coffee and layer in a dish.","Spread cream mixture between layers; dust with cocoa and chill."],"Chill for several hours for clean slices."]
],
"French Cuisine": [
["French Toast","5 minutes","10 minutes","15 minutes",["4 bread slices","2 eggs","1/2 cup milk","1 tbsp sugar","1/2 tsp cinnamon","Butter"],["Beat eggs, milk, sugar and cinnamon.","Dip bread slices briefly into the mixture.","Heat butter in a pan.","Cook both sides until golden and serve."],"Dip bread briefly so it does not become too soft."],
["Ratatouille","20 minutes","35 minutes","55 minutes",["1 zucchini","1 eggplant","2 tomatoes","1 capsicum","1 onion","2 garlic cloves","Olive oil","Salt and pepper"],["Slice vegetables evenly.","Cook onion and garlic in olive oil.","Add chopped tomato to make a sauce.","Arrange sliced vegetables over sauce and bake or cover-cook until tender."],"Slice vegetables evenly so they cook at the same rate."],
["Crepes","10 minutes","15 minutes","25 minutes",["1 cup flour","1 egg","1 cup milk","1 tbsp sugar","1 tbsp melted butter","Pinch of salt"],["Whisk flour, egg, milk, sugar and salt until smooth.","Rest batter briefly.","Pour a thin layer into a lightly buttered pan.","Cook both sides until lightly golden and add your chosen filling."],"Keep the batter thin for delicate crepes."],
["French Onion Soup","15 minutes","45 minutes","1 hour",["4 onions","2 tbsp butter","4 cups vegetable stock","1 tsp thyme","1 baguette","1/2 cup grated cheese","Salt and pepper"],["Slice onions thinly.","Cook slowly in butter until deeply golden and soft.","Add stock and thyme; simmer for 20 minutes.","Serve with toasted baguette and melted cheese."],"Caramelise onions slowly for deep flavour."],
["Quiche Lorraine Style","20 minutes","40 minutes","1 hour",["1 pastry shell","3 eggs","1 cup milk or cream","1/2 cup grated cheese","1 onion","1 cup sautéed mushrooms","Salt and pepper"],["Heat oven to 180°C.","Whisk eggs with milk, salt and pepper.","Spread cooked onion, mushrooms and cheese in pastry shell.","Pour in egg mixture and bake until set."],"Let the quiche rest before slicing."],
["Croque Monsieur","10 minutes","10 minutes","20 minutes",["4 bread slices","4 cheese slices","1/2 cup béchamel sauce","1 tbsp butter","Mustard"],["Spread a little mustard on bread.","Add cheese and close sandwiches.","Spread béchamel on top and add more cheese.","Bake or grill until hot and golden."],"Use a thick béchamel layer for a creamy top."],
["Madeleines","15 minutes","12 minutes","27 minutes",["2 eggs","1/2 cup sugar","3/4 cup flour","1/2 tsp baking powder","1/3 cup melted butter","1 tsp lemon zest"],["Whisk eggs and sugar until pale.","Fold in flour and baking powder.","Fold in melted butter and lemon zest; chill batter briefly.","Spoon into madeleine moulds and bake until lightly golden."],"Chilling the batter helps the cakes keep their shape."],
["French Apple Tart","20 minutes","35 minutes","55 minutes",["1 sheet puff pastry","3 apples","2 tbsp sugar","1 tbsp butter","1/2 tsp cinnamon","Apricot jam"],["Heat oven to 200°C.","Place pastry on a tray and score a border.","Arrange thin apple slices inside the border.","Sprinkle sugar and cinnamon, dot with butter and bake; glaze lightly."],"Slice apples evenly for a neat tart."],
["Gratin Dauphinois","15 minutes","50 minutes","1 hour 5 minutes",["4 potatoes","1 cup cream","1/2 cup milk","1 garlic clove","Nutmeg","Salt and pepper"],["Heat oven to 180°C and rub a baking dish with garlic.","Slice potatoes thinly.","Layer potatoes in the dish and season.","Pour over cream and milk; bake until tender and golden."],"Slice potatoes evenly so they cook through together."],
["Chocolate Mousse","20 minutes","0 minutes","20 minutes plus chilling",["150 g dark chocolate","3 eggs, separated","2 tbsp sugar","1/2 cup cream"],["Melt chocolate gently and cool slightly.","Whisk egg yolks into the melted chocolate.","Whip egg whites with sugar until soft peaks form.","Fold whites and whipped cream gently into chocolate; chill until set."],"Fold gently to keep the mousse airy."]
]
};

const recipes = [];
Object.entries(recipeData).forEach(([cuisine, items]) => {
  items.forEach(([name,prep,cook,total,ingredients,steps,tip]) => {
    recipes.push({cuisine,name,prep,cook,total,ingredients,steps,tip,
      imageQuery:name + " traditional food dish"});
  });
});

/* Load a photograph for the exact dish name. Try Wikimedia Commons first,
   then TheMealDB. Results are cached so each dish is only searched once. */
const imageCache = new Map();
async function getDishImage(dishName) {
  const key = dishName.trim().toLowerCase();
  if (imageCache.has(key)) return imageCache.get(key);
  let imageUrl = "";

  // Wikimedia Commons API: search file titles using the dish name.
  try {
    const commonsUrl = "https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6&gsrlimit=20&gsrsearch=" +
      encodeURIComponent(dishName) + "&prop=imageinfo&iiprop=url&iiurlwidth=900&format=json&origin=*";
    const response = await fetch(commonsUrl);
    if (!response.ok) throw new Error("Commons image search failed");
    const data = await response.json();
    const pages = Object.values(data.query?.pages || {});
    const words = dishName.toLowerCase().split(/[^a-z0-9]+/).filter(w => w.length > 2);
    const ranked = pages.map(page => {
      const title = (page.title || "").toLowerCase().replace(/_/g, " ");
      const score = words.reduce((total, word) => total + (title.includes(word) ? 1 : 0), 0);
      return {page, score};
    }).sort((a,b) => b.score - a.score);
    const best = ranked.find(item => item.score >= Math.max(1, Math.ceil(words.length * 0.45)) && item.page.imageinfo?.[0]?.thumburl);
    if (best) imageUrl = best.page.imageinfo[0].thumburl;
  } catch (error) {
    console.warn("Wikimedia image lookup failed for", dishName, error);
  }

  // TheMealDB is a second source; only accept a result whose meal name
  // substantially matches the requested dish name.
  if (!imageUrl) {
    try {
      const mealUrl = "https://www.themealdb.com/api/json/v1/1/search.php?s=" + encodeURIComponent(dishName);
      const response = await fetch(mealUrl);
      if (!response.ok) throw new Error("Meal image search failed");
      const data = await response.json();
      const meals = data.meals || [];
      const wanted = dishName.toLowerCase().replace(/[^a-z0-9]+/g, " ").trim();
      const match = meals.find(meal => {
        const found = (meal.strMeal || "").toLowerCase().replace(/[^a-z0-9]+/g, " ").trim();
        return found === wanted || found.includes(wanted) || wanted.includes(found);
      });
      if (match?.strMealThumb) imageUrl = match.strMealThumb;
    } catch (error) {
      console.warn("Meal image lookup failed for", dishName, error);
    }
  }

  imageCache.set(key, imageUrl);
  return imageUrl;
}

function showPage(pageId) {
  document.querySelectorAll(".page").forEach(page => page.classList.remove("active"));
  document.getElementById(pageId).classList.add("active");
}
function showHome(){showPage("home")}
function showCuisines(){showPage("cuisines");displayCuisines()}

async function displayCuisines(){
  const grid=document.getElementById("cuisineGrid");
  grid.innerHTML="";
  cuisines.forEach(cuisine=>{
    const card=document.createElement("div");card.className="card";
    const img=document.createElement("img");img.alt=cuisine.name+" food";
    img.src="";
    const title=document.createElement("h3");title.textContent=cuisine.name;
    const btn=document.createElement("button");btn.textContent="View Foods";
    btn.onclick=()=>showFoods(cuisine.name);
    card.append(img,title,btn);grid.append(card);
    const firstRecipe=recipes.find(r=>r.cuisine===cuisine.name);
    if(firstRecipe) getDishImage(firstRecipe.name).then(url=>{if(url)img.src=url;});
  });
}

function showFoods(cuisineName){
  document.getElementById("cuisineTitle").innerText=cuisineName;
  const grid=document.getElementById("foodGrid");grid.innerHTML="";
  const selected=recipes.filter(r=>r.cuisine===cuisineName);
  selected.forEach(recipe=>{
    const card=document.createElement("div");card.className="card";
    const img=document.createElement("img");img.alt=recipe.name+" photograph";
    img.src="";img.dataset.query=recipe.imageQuery;
    const title=document.createElement("h3");title.textContent=recipe.name;
    const btn=document.createElement("button");btn.textContent="View Recipe";
    btn.onclick=()=>showRecipe(recipe.name);
    card.append(img,title,btn);grid.append(card);
    getDishImage(recipe.name).then(url=>{if(url)img.src=url;});
  });
  showPage("foods");
}
async function showRecipe(recipeName){
  const recipe=recipes.find(item=>item.name===recipeName);
  if(!recipe)return;
  document.getElementById("recipeTitle").innerText=recipe.name;
  const image=document.getElementById("recipeImage");
  image.alt=recipe.name+" photograph";
  image.src="";
  const imageUrl=await getDishImage(recipe.name);
  if(imageUrl) image.src=imageUrl;
  else document.getElementById("imageNote").innerText="No matching Commons photo was found for this dish. Try again later; no unrelated food photo is substituted.";
  if(imageUrl) document.getElementById("imageNote").innerText="Photograph retrieved using the exact dish name: "+recipe.name+".";
  document.getElementById("recipeTime").innerHTML="<b>Preparation Time:</b> "+recipe.prep+"<br><b>Cooking Time:</b> "+recipe.cook+"<br><b>Total Time:</b> "+recipe.total;
  const ingredientList=document.getElementById("ingredientList");ingredientList.innerHTML="";
  recipe.ingredients.forEach(ingredient=>{
    const label=document.createElement("label");label.className="ingredient";
    const checkbox=document.createElement("input");checkbox.type="checkbox";checkbox.value=ingredient;
    label.append(checkbox,document.createTextNode(" "+ingredient));ingredientList.append(label);
  });
  const steps=document.getElementById("recipeSteps");steps.innerHTML="";
  recipe.steps.forEach(step=>{const li=document.createElement("li");li.textContent=step;steps.append(li);});
  document.getElementById("recipeTip").innerText=recipe.tip;
  document.getElementById("shoppingResult").style.display="none";
  showPage("recipe");
}
async function buyMissing(){
  const checkboxes=document.querySelectorAll("#ingredientList input[type='checkbox']");
  const missing=[];checkboxes.forEach(box=>{if(!box.checked)missing.push(box.value)});
  const resultBox=document.getElementById("shoppingResult");
  resultBox.style.display="block";
  if(missing.length===0){resultBox.textContent="You have all the ingredients!";return;}
  const formData=new FormData();formData.append("action","save_missing");
  missing.forEach(item=>formData.append("ingredients[]",item));
  try{
    const response=await fetch(window.location.href,{method:"POST",body:formData});
    if(!response.ok)throw new Error("Server error");
    const data=await response.json();
    resultBox.innerHTML="<b>Missing ingredients saved ("+data.count+"):</b><ul>"+
      data.ingredients.map(item=>"<li>"+escapeHTML(item)+"</li>").join("")+
      "</ul><button class='action' id='findIngredients'>Find Ingredients Online</button>";
    document.getElementById("findIngredients").onclick=searchMissingIngredients;
  }catch(error){
    resultBox.textContent="Unable to save the list. Please run this page using XAMPP with Apache.";
  }
}
function escapeHTML(value){
  return String(value).replace(/[&<>"']/g,character=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"}[character]));
}
function searchMissingIngredients(){
  const items=Array.from(document.querySelectorAll("#shoppingResult li")).map(item=>item.textContent);
  if(items.length)window.open("https://www.google.com/search?tbm=shop&q="+encodeURIComponent(items.join(", ")),"_blank");
}
</script>
</body>
</html>
