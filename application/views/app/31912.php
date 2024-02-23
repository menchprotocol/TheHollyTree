<?php
$e___6206 = $this->config->item('e___6206'); //Source Cache
$e___11035 = $this->config->item('e___11035'); //Encyclopedia
?>

<!-- Edit Source Modal -->
<div class="modal fade" id="modal31912" tabindex="-1" role="dialog" aria-labelledby="modal31912Label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content slim_flat">

            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <button type="button" class="e_editor_save btn btn-default post_button" onclick="e_editor_save()">SAVE</button>
            </div>

            <div class="modal-body">

                <div class="save_results hideIfEmpty zq6255 alert alert-danger" style="margin:8px 0;"></div>

                <input type="hidden" class="save_e__id" value="0" />
                <input type="hidden" class="save_x__id" value="0" />


                <!-- Source Title -->
                <div class="dynamic_editing_input">
                    <h3 class="mini-font"><?= '<span class="icon-block">'.$e___6206[6197]['m__cover'].'</span>'.$e___6206[6197]['m__title'].': ';  ?></h3>
                    <textarea class="form-control unsaved_warning save_e__title main__title" placeholder="..." style="margin:0; width:100%; background-color: #FFFFFF !important;"></textarea>
                </div>


                <!-- Source Handle -->
                <div class="dynamic_editing_input">
                    <h3 class="mini-font"><?= '<span class="icon-block">'.$e___6206[32338]['m__cover'].'</span>'.$e___6206[32338]['m__title'].': ';  ?></h3>
                    <input type="text" class="form-control unsaved_warning save_e__handle" placeholder="...">
                </div>

                <!-- Source Privacy -->
                <div class="dynamic_editing_input">
                    <h3 class="mini-font"><?= '<span class="icon-block">'.$e___6206[6177]['m__cover'].'</span>'.$e___6206[6177]['m__title'].': ';  ?></h3>
                    <div class="dynamic_selector"><?= view_single_select_form(6177, 6181, true, true); ?></div>
                </div>

                <!-- SOURCE COVER -->
                <div class="message_controllers">
                    <table class="emoji_table">
                        <tr>
                            <td>
                                <!-- Upload Cover -->
                                <a class="uploader_42359" class="icon-block-sm" href="javascript:void(0);" title="<?= $e___11035[42359]['m__title'] ?>"><?= $e___11035[42359]['m__cover'] ?></a>
                            </td>
                            <td class="superpower__13758">
                                <!-- EMOJI -->
                                <div class="icon-block-sm">
                                    <div class="dropdown emoji_selector" style="max-height: 21px; margin-top: -18px;">
                                        <button type="button" class="btn no-left-padding no-right-padding" id="emoji_e" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="far fa-face-smile"></i></button>
                                        <div class="dropdown-menu emoji_e" aria-labelledby="emoji_e"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="superpower__13758 fa_search hidden">
                                <!-- Font Awesome Search -->
                                <a href="https://fontawesome.com/search?q=circle&o=r&s=solid" class="icon-block-sm" target="_blank" title="Open New Window to Search on Font Awesome"><i class="far fa-search-plus zq12274"></i></a>
                            </td>
                            <td class="superpower__13758">
                                <!-- Font Awesome Insert -->
                                <a href="javascript:void(0);" class="icon-block-sm" onclick="update__cover('far fa-icons');$('.fa_search').removeClass('hidden');" title="Add a Sample Font Awesome Icon to Get Started"><i class="far fa-icons"></i></a>
                            </td>
                            <td class="superpower__13758 cover_history_button">
                                <!-- History -->
                                <a href="javascript:void(0);" class="icon-block-sm" onclick="$('.cover_history_content').toggleClass('hidden');" title="Toggle Previously Used Covers"><i class="far fa-clock-rotate-left"></i></a>
                            </td>
                            <td>
                                <!-- Ramdom Animal -->
                                <a href="javascript:void(0);" class="random_animal" onclick="update__cover('deleted '+random_animal())" title="Set a random animal"></a>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="dynamic_editing_input">
                    <h3 class="mini-font"><?= '<span class="icon-block">'.$e___6206[6198]['m__cover'].'</span>'.$e___6206[6198]['m__title'].': ';  ?></h3>
                    <!-- Cover HIDDEN Input (Editable for font awesome icons only) -->
                    <input type="text" class="form-control unsaved_warning save_e__cover superpower__13758" data-lpignore="true" placeholder="Emoji, Image URL or Cover Code">
                    <div>
                        <!-- Cover Settings/Selectors -->
                        <div class="icons_small font_awesome hidden section_subframe">
                            <div><a href="https://fontawesome.com/search" target="_blank">Search FontAwesome <i class="far fa-external-link"></i></a></div>
                        </div>
                        <div class="icons_small cover_history_content hidden section_subframe"></div>
                        <!-- Cover Demo -->
                        <div class="section_demo">
                            <div class="card_cover demo_cover">
                                <div class="cover-wrapper"><div class="black-background-obs cover-link" style=""><div class="cover-btn"></div></div></div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Link Note -->
                <div class="dynamic_editing_input save_x__frame hidden">
                    <h3 class="mini-font"><?= '<span class="icon-block">'.$e___11035[4372]['m__cover'].'</span>'.$e___11035[4372]['m__title'].': ';  ?></h3>
                    <textarea class="form-control border unsaved_warning save_x__message" data-lpignore="true" placeholder="..."></textarea>
                </div>


                <!-- Dynamic Loader -->
                <div class="dynamic_editing_loading hidden"><span class="icon-block"><i class="far fa-yin-yang fa-spin"></i></span>Loading</div>

                <!-- Dynamic Inputs -->
                <div class="dynamic_frame"><?= $dynamic_edit ?></div>

            </div>
            <div class="modal-footer hideIfEmpty"></div>
        </div>
    </div>
</div>