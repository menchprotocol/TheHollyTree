<?php

if(!isset($_GET['i__id'])){
    die('Missing Idea ID i__id');
}


//Generate list & settings:
$list_settings = list_settings($_GET['i__id'], true);
echo '<h1 class="no-print">' . view_title($list_settings['i']) . '</h1>';


if(!$list_settings['list_config'][34513]){
    die('Missing Pin Link @34513');
}


foreach($this->X_model->fetch(array(
    'x__access IN (' . join(',', $this->config->item('n___7359')) . ')' => null, //PUBLIC
    'x__type IN (' . join(',', $this->config->item('n___33602')) . ')' => null, //Idea/Source Links Active
    'x__up' => $list_settings['list_config'][34513],
    'i__access IN (' . join(',', $this->config->item('n___31871')) . ')' => null, //ACTIVE
), array('x__right'), 0, 0, array('x__weight' => 'ASC')) as $link_i){

    $sub_list_settings = list_settings($link_i['i__id'], true);
    if(!count($sub_list_settings['query_string'])){
        continue;
    }

    echo '<div class="this_frame">';
    echo '<h3 style="margin-top: 55px;"><a href="/~'.$link_i['i__id'].'">'.view_title($link_i).'</a></h3>';
    echo '<table class="table table-sm table-striped stats-table mini-stats-table">';
    foreach($sub_list_settings['query_string'] as $x){
        echo '<tr class="panel-title down-border" style="font-weight:bold !important;">';
        echo '<td><div class="this_name">'.$x['extension_name'].'</div></td>';
        echo '<td>&nbsp;</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';

}

?>

<style>
    .this_name { padding: 8px !important; font-size:1.3em; }
    .this_frame {
        page-break-inside: avoid;
    }
</style>
