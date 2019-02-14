<?php
$total_pedido = $data['pedido']['total'];

$parcelas = '';

$min = $data['min'];
$div = $data['div'];
$sem = $data['sem'];
$taxa = $data['taxa'];

if($div>=1){
    $regras = array(1=>2.50,2=>2.50,3=>2.50,4=>2.50,5=>2.50,6=>2.50,7=>2.50,8=>2.50,9=>2.50,10=>2.50,11=>2.50,12=>2.50);
    for($i=1;$i<=$div;$i++){
        if(($total_pedido/$i)>=$min){

            if($i<=$sem){

                $parcelas .= '<option value="'.$i.'">'.$i.'x de '.$this->formatar(number_format((($total_pedido)/$i), 2, '.', '')).' sem juros ('.$this->formatar(number_format(($total_pedido), 2, '.', '')).')</option>';

            }else{

                //regra de parcelamento
                $fator = $regras[$i]*$i;
                $total_parcelar =  $total_pedido*((1-($taxa/100))/(1-(($taxa+$fator)/100)));

                $parcelas .= '<option value="'.$i.'">'.$i.'x de '.$this->formatar(number_format(($total_parcelar/$i), 2, '.', '')).' com juros ('.$this->formatar(number_format(($total_parcelar), 2, '.', '')).')</option>';

            }

        }
    }
}
?>