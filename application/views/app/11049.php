<?php

if(!isset($_GET['i__hashtag'])){
    die('Missing Idea ID i__hashtag');
}


//Generate list & settings:
$list_settings = list_settings($_GET['i__hashtag']);
echo '<h1>' . view_i_title($list_settings['i']) . '</h1>';

//Display idea info:
$recursive_down_ids = $this->I_model->recursive_down_ids($list_settings['i'], 'ALL');


//Main Idea:
echo '<h2><a href="/~'.$list_settings['i']['i__hashtag'].'">'.view_i_title($list_settings['i'], true).'</a> '.count($recursive_down_ids['recursive_i_ids']).' IDEAS</h2>';

echo '<div class="row justify-content">';
foreach($recursive_down_ids['recursive_i_ids'] as $recursive_down_id){
    foreach($this->I_model->fetch(array(
        'i__id' => $recursive_down_id,
    ), 0) as $this_i){
        echo view_card_i(12273, 0, null, $this_i);
    }
}
echo '</div>';