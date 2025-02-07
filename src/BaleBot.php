<?php
namespace MohammadSoltani\BaleBot;
use \CURLFile;
/**
 * The Client class is designed to interact with the Bale Bot API, handling the sending and receiving of messages.
 * It includes methods for sending messages, sending files, receiving updates, and handling responses from users.
 * @package BaleBotLib
 * @author محمد محمد سلطانی
 * @version 1.0.0
 * @since 2025-01-05
 * @license MIT
 * Properties:
 * @property string $botToken The bot token used for API interaction.
 * @property string $base_url The base URL for the API.
 * @property int $lastUpdateId The ID of the last update received by the bot.
 */
class BaleBot {
    /**
     * @var string The bot token for API interaction.
     */
    public $botToken;
    
    /**
     * @var string The base URL for the API.
     */
    public $base_url;
    
    /**
     * @var int The ID of the last update received.
     */
    private $lastUpdateId = 0;

    /**
     * The constructor method sets the bot token and base URL for the API.
     * 
     * @param string $botToken The bot token.
     * @param bool $live The live status of the bot.
     * @param float $sleep The sleep time between requests.
     * @param string $base_url The base URL for the API.
     */
    public function __construct(string $botToken, bool $live = false, $sleep = 1, string $base_url = "https://tapi.bale.ai") {
        $this->botToken = $botToken;
        $this->base_url = $base_url;
        define("FILE_DIR" , "FILE_DIR");
        define("BY_URL" , "BY_URL");
    }

    /**
     * Makes a request to the Telegram Bot API.
     *
     * @param string $method The API method to be called.
     * @param array $data The data to be sent in the request.
     * @param string $content_type The content type (default is "application/json").
     * @return array The decoded JSON response from the API.
     */
    function bot(string $method, array $data, string $content_type = "application/json") {
        $url = "{$this->base_url}/bot{$this->botToken}/{$method}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        
        if ($content_type === "application/json") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: ' . $content_type,
            ]);
        } else { // For multipart/form-data
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * Handles the bot's update requests in a loop.
     * 
     * @param float $sleep The sleep time between each request.
     * @return array|null The response to the update data.
     */
    function handle($sleep) {
        while (true) {
            $updates = $this->bot("getUpdates", [
                "offset" => $this->lastUpdateId + 1,
            ]);
            if (!empty($updates['result'])) {
                foreach ($updates['result'] as $update) {
                    $this->lastUpdateId = $update['update_id'];
                    if (isset($update['update_id'])) {
                        return $this->response_to_data($update);
                    }
                }
            }
        }
    }

    /**
     * Processes the response data from an update.
     * 
     * @param array $data The data received from the update.
     * @return array Processed data including status and message information.
     */
    function response_to_data($data) {
        $return_data = [];
        if (isset($data["message"]["text"])) {
            $return_data["status"] = true;
            $return_data["type"] = "simple_text_message";
            $return_data["id"] = $data["update_id"] ?? null;
            $return_data["message_id"] = $data["message"]["message_id"] ?? null;
            $return_data["chat_id"] = $data["message"]["chat"]["id"];
            $return_data["from"] = $data["message"]["from"];
            $return_data["request_time"] = $data["message"]["date"];
            $return_data["data"] = $data["message"]["text"];
        } elseif (isset($data["callback_query"])) {
            $return_data["status"] = true;
            $return_data["type"] = "callback_message";
            $return_data["id"] = $data["update_id"] ?? null;
            $return_data["message_id"] = $data["callback_query"]["message"]["message_id"] ?? null;
            $return_data["chat_id"] = $data["callback_query"]["message"]["chat"]["id"];
            $return_data["from"] = $data["callback_query"]["from"];
            $return_data["request_time"] = $data["callback_query"]["date"];
            $return_data["data"] = $data["callback_query"]["data"];
        } elseif(isset($data["message"]["photo"])){
            $return_data["status"] = true;
            $return_data["type"] = "simple_photo_message";
            $return_data["id"] = $data["update_id"] ?? null;
            $return_data["message_id"] = $data["message"]["message_id"] ?? null;
            $return_data["chat_id"] = $data["message"]["chat"]["id"];
            $return_data["from"] = $data["message"]["from"];
            $return_data["request_time"] = $data["message"]["date"];
            $return_data["data"] = $data["message"]["photo"];
            $return_data["caption"] = $data["message"]["caption"] ?? null;
        }else {
            $return_data["status"] = false;
            $return_data["type"] = "unknown_message";
        }
        return $return_data;
    }

    /**
     * Stream live updates and handle them.
     *
     * @param float $time The time interval between each update.
     * @param bool $log Disable or Enable terminal log.
     * @return array|null The response to the update data.
     */
    function live_stream($time = 0.1 , $log) {
        $data = $this->handle($time);
        if($log){
            echo "New Message From : {$data['chat_id']}".PHP_EOL;
        }
        return $data;
    }
    
    /**
     * Generates the inline keyboard JSON structure.
     * 
     * @param array $key The inline keyboard structure.
     * @return string JSON-encoded inline keyboard.
     */
    function inline_key_gen($key) {
        return json_encode(["inline_keyboard" => $key]);
    }

    /**
     * Replies to a message with the specified message ID.
     * 
     * @param array $message The message object.
     * @return int|null The message ID if set, otherwise null.
     */
    function reply_message($message) {
        if (isset($message["message_id"])) {
            return $message["message_id"];
        } else {
            return null;
        }
    }

    /**
     * Executes the specified work if the message data matches the condition.
     * 
     * @param array $message The received message data.
     * @param string $condition The condition to match.
     * @param callable $work The function to be executed if the condition is met.
     * @return bool True if the work was executed, otherwise false.
     */
    function on_message($message, $condition, $work) {
        if (isset($message["data"]) && $message["data"] == $condition) {
            $work($message, $this);
        } else {
            return false;
        }
    }

    /**
     * Extracts the command data from the message or callback data.
     * 
     * @param string $command The command to search for.
     * @param mixed $data The data to search in.
     * @return string|false The extracted command data, or false if not found.
     */
    function get_command_data(string $command, mixed $data) {
        if (!is_array($data)) {
            return explode($command, $data)[1] ?? null;
        } elseif (isset($data["data"])) {
            return explode($command, $data["data"])[1] ?? null;
        } else {
            return false;
        }
    }

    /**
     * Sends a message to a user.
     * 
     * @param mixed $user_id The user ID or chat object.
     * @param string $text The message text.
     * @param int|null $reply_message The message ID to reply to (optional).
     * @param string|null $reply_markup The reply markup (optional).
     * @return array The response data.
     */
    function send_message($user_id, $text, $reply_message = null, $reply_markup = null) {
        if (isset($user_id["chat_id"])) {
            $chat_id = $user_id["chat_id"];
        } else {
            $chat_id = $user_id;
        }
        $response = $this->bot("sendMessage", ["chat_id" => $chat_id, "text" => $text, "reply_markup" => $reply_markup, "reply_to_message_id" => $reply_message]);
        if (isset($response['ok']) && $response['ok'] === true) {
            return [
                "ok" => true,
                "message_id" => $response["result"]["message_id"],
                "chat_id" => $chat_id
            ];
        }
        return [
            "ok" => false,
            "error" => $response['description'] ?? "Unknown error",
            "error_code" => $response['error_code'] ?? null
        ];
    }


    function send_photo($user_id, $photo, $caption = null ,  $reply = null , $reply_markup = null, $photo_type = FILE_DIR ) {
        $chat_id = isset($user_id["chat_id"]) ? $user_id["chat_id"] : $user_id;
        if($caption !== null && is_array($caption) && isset($caption["caption"])) $caption = $caption["caption"];
        if(is_array($photo)){
            

            $response = $this->bot("sendPhoto", [
                "chat_id" => $chat_id,
                "photo" => $photo["data"][0]["file_id"],
                "reply_to_message_id" => $reply,
                "caption" => $caption,
                "reply_markup" => $reply_markup,
            ]);
        
            if (isset($response['ok']) && $response['ok'] === true) {            return [
                    "ok" => true,
                    "message_id" => $response["result"]["message_id"],
                    "photo" => $response["result"]["photo"],
                    "chat_id" => $chat_id
                ];
            }
        
            return [
                "ok" => false,
                "error" => $response['description'] ?? "Unknown error",
                "error_code" => $response['error_code'] ?? null
            ];
        }else{
            if (!file_exists($photo)) {
                return [
                    "ok" => false,
                    "error" => "File not found: $photo"
                ];
            }
        
            $photo_file = new CURLFile($photo, mime_content_type($photo), basename($photo));
        
            $response = $this->bot("sendPhoto", [
                "chat_id" => $chat_id,
                "photo" => $photo_file,
                "reply_to_message_id" => $reply,
                "caption" => $caption,
                "reply_markup" => $reply_markup,
            ], "multipart/form-data");
        
            if (isset($response['ok']) && $response['ok'] === true) {            return [
                    "ok" => true,
                    "message_id" => $response["result"]["message_id"],
                    "photo" => $response["result"]["photo"],
                    "chat_id" => $chat_id
                ];
            }
        
            return [
                "ok" => false,
                "error" => $response['description'] ?? "Unknown error",
                "error_code" => $response['error_code'] ?? null
            ];
        }
    }    
    function forward_message($from_caht_id , $chat_id , $message_id = null){
        if($message_id == null){
            $message_id = $from_caht_id["message_id"];
            $from_caht_id = $from_caht_id["chat_id"];

        }
        $response = $this -> bot("forwardMessage" , [
            "from_chat_id" => $from_caht_id,
            "message_id" => $message_id,
            "chat_id" => $chat_id
        ]);
        if (isset($response['ok']) && $response['ok'] === true) {            return [
            "ok" => true,
            "message_id" => $response["result"]["message_id"],
            "chat_id" => $chat_id
        ];
        }

        return [
            "ok" => false,
            "error" => $response['description'] ?? "Unknown error",
            "error_code" => $response['error_code'] ?? null
        ];
    }
    function copy_message($from_caht_id , $chat_id , $message_id = null){
        if($message_id == null){
            $message_id = $from_caht_id["message_id"];
            $from_caht_id = $from_caht_id["chat_id"];

        }
        $response = $this -> bot("copyMessage" , [
            "from_chat_id" => $from_caht_id,
            "message_id" => $message_id,
            "chat_id" => $chat_id
        ]);
        if (isset($response['ok']) && $response['ok'] === true) {            return [
            "ok" => true,
            "message_id" => $response["result"]["message_id"],
            "chat_id" => $chat_id
        ];
        }

        return [
            "ok" => false,
            "error" => $response['description'] ?? "Unknown error",
            "error_code" => $response['error_code'] ?? null
        ];
    }
    function send_audio($user_id, $audio, $caption = null ,  $reply = null , $reply_markup = null, $photo_type = FILE_DIR ) {
        $chat_id = isset($user_id["chat_id"]) ? $user_id["chat_id"] : $user_id;
    
        if (!file_exists($audio)) {
            return [
                "ok" => false,
                "error" => "File not found: $audio"
            ];
        }
    
        $audio_file = new CURLFile($audio, mime_content_type($audio), basename($audio));
    
        $response = $this->bot("sendAudio", [
            "chat_id" => $chat_id,
            "audio" => $audio_file,
            "reply_to_message_id" => $reply,
            "caption" => $caption,
            "reply_markup" => $reply_markup,
        ], "multipart/form-data");
    
        if (isset($response['ok']) && $response['ok'] === true) {            return [
                "ok" => true,
                "message_id" => $response["result"]["message_id"],
                "audio" => $response["result"]["audio"],
                "chat_id" => $chat_id
            ];
        }
    
        return [
            "ok" => false,
            "error" => $response['description'] ?? "Unknown error",
            "error_code" => $response['error_code'] ?? null
        ];
    }   
    function send_document($user_id, $document, $caption = null ,  $reply = null , $reply_markup = null, $photo_type = FILE_DIR ) {
        $chat_id = isset($user_id["chat_id"]) ? $user_id["chat_id"] : $user_id;
    
        if (!file_exists($document)) {
            return [
                "ok" => false,
                "error" => "File not found: $document"
            ];
        }
    
        $document_file = new CURLFile($document, mime_content_type($document), basename($document));
    
        $response = $this->bot("sendDocument", [
            "chat_id" => $chat_id,
            "document" => $document_file,
            "reply_to_message_id" => $reply,
            "caption" => $caption,
            "reply_markup" => $reply_markup,
        ], "multipart/form-data");
    
        if (isset($response['ok']) && $response['ok'] === true) {            return [
                "ok" => true,
                "message_id" => $response["result"]["message_id"],
                "document" => $response["result"]["document"],
                "chat_id" => $chat_id
            ];
        }
    
        return [
            "ok" => false,
            "error" => $response['description'] ?? "Unknown error",
            "error_code" => $response['error_code'] ?? null
        ];
    }   
    function send_video($user_id, $video, $caption = null ,  $reply = null , $reply_markup = null, $photo_type = FILE_DIR ) {
        $chat_id = isset($user_id["chat_id"]) ? $user_id["chat_id"] : $user_id;
    
        if (!file_exists($video)) {
            return [
                "ok" => false,
                "error" => "File not found: $video"
            ];
        }
    
        $video_file = new CURLFile($video, mime_content_type($video), basename($video));
    
        $response = $this->bot("sendVideo", [
            "chat_id" => $chat_id,
            "video" => $video_file,
            "reply_to_message_id" => $reply,
            "caption" => $caption,
            "reply_markup" => $reply_markup,
        ], "multipart/form-data");
    
        if (isset($response['ok']) && $response['ok'] === true) {            return [
                "ok" => true,
                "message_id" => $response["result"]["message_id"],
                "video" => $response["result"]["video"],
                "chat_id" => $chat_id
            ];
        }
    
        return [
            "ok" => false,
            "error" => $response['description'] ?? "Unknown error",
            "error_code" => $response['error_code'] ?? null
        ];
    }   
    function send_animation($user_id, $animation,  $reply = null , $reply_markup = null, $photo_type = FILE_DIR ) {
        $chat_id = isset($user_id["chat_id"]) ? $user_id["chat_id"] : $user_id;
    
        if (!file_exists($animation)) {
            return [
                "ok" => false,
                "error" => "File not found: $animation"
            ];
        }
    
        $animation_file = new CURLFile($animation, mime_content_type($animation), basename($animation));
    
        $response = $this->bot("sendAnimation", [
            "chat_id" => $chat_id,
            "animation" => $animation_file,
            "reply_to_message_id" => $reply,
            "reply_markup" => $reply_markup,
        ], "multipart/form-data");
    
        if (isset($response['ok']) && $response['ok'] === true) {            return [
                "ok" => true,
                "message_id" => $response["result"]["message_id"],
                "video" => $response["result"]["animation"],
                "chat_id" => $chat_id
            ];
        }
    
        return [
            "ok" => false,
            "error" => $response['description'] ?? "Unknown error",
            "error_code" => $response['error_code'] ?? null
        ];
    }   
    function send_voice($user_id, $voice, $caption = null ,  $reply = null , $reply_markup = null, $photo_type = FILE_DIR ) {
        $chat_id = isset($user_id["chat_id"]) ? $user_id["chat_id"] : $user_id;
    
        if (!file_exists($voice)) {
            return [
                "ok" => false,
                "error" => "File not found: $voice"
            ];
        }
   
        $voice_file = new CURLFile($voice, mime_content_type($voice), basename($voice));
    
        $response = $this->bot("sendVoice", [
            "chat_id" => $chat_id,
            "voice" => $voice_file,
            "reply_to_message_id" => $reply,
            "caption" => $caption,
            "reply_markup" => $reply_markup,
        ], "multipart/form-data");
        if (isset($response['ok']) && $response['ok'] === true) {            return [
                "ok" => true,
                "message_id" => $response["result"]["message_id"],
                "voice" => $response["result"]["voice"],
                "chat_id" => $chat_id
            ];
        }
    
        return [
            "ok" => false,
            "error" => $response['description'] ?? "Unknown error",
            "error_code" => $response['error_code'] ?? null
        ];
    }  
    function get_info(){
        echo "

 ____  _   _ ____    ____    _    _     _____   ____   ___ _____ 
|  _ \| | | |  _ \  | __ )  / \  | |   | ____| | __ ) / _ \_   _|
| |_) | |_| | |_) | |  _ \ / _ \ | |   |  _|   |  _ \| | | || |  
|  __/|  _  |  __/  | |_) / ___ \| |___| |___  | |_) | |_| || |  
|_|   |_| |_|_|     |____/_/   \_\_____|_____| |____/ \___/ |_|  

Created By : Mohammad Mohammad Soltani

";
    }
}
?>