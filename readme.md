
# 🎉 BaleBot PHP Client 🚀

The **BaleBot PHP Client** is a versatile and easy-to-use library designed to interact with the **Bale Bot API**. It facilitates sending and receiving messages, handling file uploads, and managing bot updates. Whether you're building a Telegram bot or integrating Bale AI capabilities, this client has got you covered!

# Download From Composer
```bash
 composer require mohammad-soltani/php-bale-bot
```

# Example
```
<?php
require_once __DIR__ . '/vendor/autoload.php'; 
use MohammadSoltani\BaleBot\BaleBot;
$bot = new BaleBot("YOUR_BOT_TOKEN");

while(true){
    $update = $bot -> live_stream(0.1 , true);
    if($update){
        $bot -> send_message($update , $update["data"]);
    }
}
?>
```
# Created By
- mohammad mohammad soltani