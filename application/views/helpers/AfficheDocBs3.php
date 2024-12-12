<?php

class View_Helper_AfficheDocBs3
{
    public function afficheDoc($verrou, $natureId, $id, $libelle, $ref = null, $date = null, $type = null): string
    {
        if (!$date) {
            // document n'ayant PAS d'enregistrement dans la BD
            $styleInput = 'display:none;';
            $etatCheck = '';
            $styleChecked = '';
            $styleValid = '';
            $styleDate = '';
        } else {
            // document ayant un enregistrement dans la BD
            $dateTab = explode('-', $date);
            $date = $dateTab[2].'/'.$dateTab[1].'/'.$dateTab[0];

            $styleInput = '';
            $etatCheck = "disabled='disabled'";
            $styleChecked = "checked='checked'";
            $styleValid = 'display:none;';
            $styleDate = "disabled='disabled'";
        }

        if ('00/00/0000' == $date) {
            $date = '';
        }

        $return = "
            <li class='divDoc col-md-12' name='divDoc' id='".$natureId.'_'.$id.$type."'>
                <div class='col-md-5'>
                    <div class='checkbox'>
                        <label>
                            <input type='checkbox' ".$styleChecked.' '.$etatCheck." name='check_".$natureId.'_'.$id.$type."' id='check_".$natureId.'_'.$id.$type."' ".((1 == $verrou) ? "disabled='disabled'" : '')." />
        ";
        if ($type) {
            $return .= "<textarea class='form-control' name='libelle_".$natureId.'_'.$id.$type."' id='libelle_".$natureId.'_'.$id.$type."' rows='3' style='display:none;'>".nl2br($libelle).'</textarea>';
        }

        return $return.('
                            <strong '.(($type) ? "id='libelleView_".$natureId.'_'.$id.$type."'" : '').'>'.nl2br($libelle)."</strong>
                        </label>
                    </div>
                </div>
                <div id='div_input_".$natureId.'_'.$id.$type."' class='col-md-7' style='".$styleInput."'>
                    <div class='col-md-4'>
                        <input class='form-control' type='text' readonly='true' name='ref_".$natureId.'_'.$id.$type."' id='ref_".$natureId.'_'.$id.$type."' value=\"".$ref."\" style='width: 100%;' />
                    </div>
                    <div class='col-md-3'>
                        <input type='text' readonly='true' ".$styleDate."  class='form-control date' name='date_".$natureId.'_'.$id.$type."' id='date_".$natureId.'_'.$id.$type."' value='".$date."' />
                    </div>
                    <div class='col-md-3'>
                        <span class='modif' id='modif_".$natureId.'_'.$id.$type."' style='".((1 == $verrou) ? 'display:none;' : '')."' >
                                <button class='editDoc btn btn-default' id='".'edit_'.$natureId.'_'.$id.$type."'><span class='glyphicon glyphicon-pencil' aria-hidden='true'></span>&nbsp;</button>
                                <button class='deleteDoc btn btn-default' name='".$natureId.'_'.$id.$type."'><span class='glyphicon glyphicon-trash' aria-hidden='true'></span>&nbsp;</button>
                        </span>
                        <span id='valid_".$natureId.'_'.$id.$type."' style='".$styleValid."'>
                                <button class='validDoc btn btn-default'><span class='glyphicon glyphicon-ok' aria-hidden='true'></span>&nbsp;</button>
                                <button class='cancelDoc btn btn-default'><span class='glyphicon glyphicon-remove' aria-hidden='true'></span>&nbsp;</button>
                            </a>
                        </span>
                    </div>
                </div>
            </li>
        ");
    }
}
