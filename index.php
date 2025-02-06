<?php
require_once __DIR__ . '/src/BaleBot.php'; 
use MohammadSoltani\BaleBot\BaleBot;
$bot = new BaleBot("107052537:w8BwV144Q8SSV5uI8LgFvxYQqRl869lhY59ldmVL");

while(true){
    $update = $bot -> live_stream(0.1 , true);
    if($update){
            $bot -> send_photo($update , $update , $update);
    }
}
?>