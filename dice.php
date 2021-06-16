<?php

function dice_mdn($m, $n): array
{
    $m_max = 100;
    $n_max = 10000;

    $output = array();

    if(!is_numeric($n) or !is_numeric($m)){
        return $output;
    }

    $m = intval($m);
    $n = intval($n);

    if($m<=0 or $m >$m_max or $n<=0 or $n >$n_max){
        return $output;
    }

    for($i=0;$i<$m;++$i){
        array_push($output,rand(1,$n));
    }

    return $output;
}


// Dice state
// 0 -> no inputs
// 1 -> we have m
// 2 -> we have d
// 3 -> we have n
// 4 -> we have + (unused)
// 5 -> we have eof
// 6 -> we have an error
function handle_dice_string($s):array{
    $m_max = 100;
    $n_max = 10000;
    $len_max = 1000;

    $result_dn = array();
    $result = array();

    if(strlen($s)>$len_max){
        return array($result_dn,$result);
    }

    $state_initial = 0;
    $state_m = 1;
    $state_d = 2;
    $state_n = 3;
    // $state_plus = 4; unused
    $state_eof = 5;
    $state_error = 6;



    $current_m = 0;
    $current_n = 0;

    $state = $state_initial;

    for($i=0;$i<strlen($s);++$i){
        switch ($s[$i]){
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
            {
                if($state === $state_initial){
                    $state = $state_m;
                    $current_m = intval($s[$i]);
                }else if($state === $state_d){
                    $state = $state_n;
                    $current_n = intval($s[$i]);
                }else if($state === $state_m){
                    $current_m = $current_m*10 + intval($s[$i]);
                }else if($state === $state_n){
                    $current_n = $current_n*10 + intval($s[$i]);
                }else{
                    $state = $state_error;
                }
                if($current_m>$m_max or $current_n>$n_max){
                    $state = $state_error;
                }
                break;
            }
            case 'd':
            {
                if($state === $state_m){
                    $state = $state_d;
                }else if($state === $state_initial) {
                    $state = $state_d;
                    $current_m = 1;
                }else{
                    $state = $state_error;
                }
                break;
            }
            case '+':
            {
                if($state===$state_n){
                    $state = $state_initial;
                    array_push($result, dice_mdn($current_m,$current_n));
                    array_push($result_dn,"d{$current_n}");
                }else{
                    $state = $state_error;
                }
                break;
            }
        }
    }

    // now we are at eof
    if($state === $state_n){
        if($current_m>$m_max or $current_n>$n_max){
            $state = $state_error;
        }else{
            array_push($result, dice_mdn($current_m,$current_n));
            array_push($result_dn,"d{$current_n}");
            $state = $state_eof;
        }
    }else{
        $state = $state_error;
    }
    if($state === $state_error){
        $result_dn = array();
        $result = array();
    }
    return array($result_dn,$result);
}

function content_replace_helper_dice($s):String{
    $result = handle_dice_string($s);
    $dn = $result[0];
    $dn_number = $result[1];
    if(count($dn)===0){
        return $s;
    }

    $output_td = "";
    $number_sum = 0;
    for($i=0;$i<count($dn);++$i){
        for($j=0;$j<count($dn_number[$i]);++$j){
            $output_td .= "{$dn[$i]}({$dn_number[$i][$j]})+";
            $number_sum += $dn_number[$i][$j];
        }
    }
    $output_td = substr($output_td,0,-1);
    return "<div><table><tbody><tr><td><b>ROLL : {$s}</b>={$output_td}=<b>{$number_sum}</b></td></tr></tbody></table></div>";
}


// 0 -> initial
// 1 -> found begin
// 2 -> found end
// 3 -> eof
function handle_content_replace($content,$pattern_begin,$pattern_end,$helper_function):String{
    $modified_content = "";

    $content_len = strlen($content);

    $len_begin = strlen($pattern_begin);
    $len_end = strlen($pattern_end);

    $state_initial = 0;
    $state_found_begin = 1;
    $state_found_end = 2;
    $state_eof = 3;

    $state = $state_initial;

    $begin = 0;
    $end = 0;

    while($state !== $state_eof){
        switch ($state){
            case $state_initial:{
                $begin = strpos($content,$pattern_begin,$end);
                if($begin !== false){
                    $state = $state_found_begin;
                    $modified_content .= substr($content,$end,$begin-$end);
                }else{
                    $state = $state_eof;
                }
                break;
            }
            case $state_found_begin:{
                $end = strpos($content,$pattern_end,$begin);
                if($end){
                    $state = $state_found_end;
                    $s = substr($content,$begin+$len_begin,$end-$begin-$len_begin);
                    $modified_content .= $helper_function($s);
                }else{
                    $state = $state_eof;
                }
            }
            case $state_found_end:{
                $end += $len_end;
                $state = $state_initial;
            }
        }
    }
    $modified_content .= substr($content,$end);
    return $modified_content;
}

print(handle_content_replace('1[dice]d100[/dice]1456[dice]d100[/dice]123[dice]2d100[/dice][dice]20d1000[/dice]','[dice]','[/dice]',function ($s){
    return content_replace_helper_dice($s);
}));
