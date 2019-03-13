<?php

namespace mirkhamidov\telegramBot;


class TelegramBot
{
    /** @var string Bot's token */
    public $botToken;

    /** @var null|integer Default chat-id for sending message, will be used if not set directly */
    public $defaultChatId = null;

    /** @var string API url base */
    public $url = 'https://api.telegram.org';

    /** @var array Telegram's default options */
    public $tgDefaultOptions = [
        'disable_notification' => false,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => false,
    ];

    /** @var array Allowed HTML tags for messages */
    public $stripTags = [
        '<i>',
        '<b>',
        '<a>',
        '<code>',
        '<pre>',
    ];

    public $curlUserAgent = "TGBotApp 1.0";

    /** @var null|string Example: socks5://LOGIN:PASS@PROXY_ADDRESS:PROXY_PORT */
    public $curlProxy = null;

    /**
     * CURL options to set by curl_setopt_array
     *
     * 'curlOptions' => [
     *      CURLOPT_PROXY =>'socks5://LOGIN:PASS@PROXY_ADDRESS:PROXY_PORT',
     * ],
     *
     * @var null|array CURL options
     * @see http://php.net/curl_setopt_array
     */
    public $curlOptions = null;

    /** @var TelegramBot */
    private static $_tg;
    
    private $async = false;
    
    private $debug = false;
    
    private $logsDirPath = null;

    public function __construct($botToken, $defaultChatId = null)
    {
        $this->botToken = $botToken;

        if ($defaultChatId) {
            $this->defaultChatId = $defaultChatId;
        }
    }

    /**
     * Init self
     * @return TelegramBot
     */
    public static function init($botToken, $defaultChatId = null)
    {
        if (!self::$_tg) {
            self::$_tg = new self($botToken, $defaultChatId);
        }
        return self::$_tg;
    }
    
    /**
     * Set async mode
     * @param bool $status
     * @return $this
     */
    public function async($status = true)
    {
        $this->async = $status;
        return $this;
    }
    
    /**
     * Set debug mode
     * @param bool $status
     * @return $this
     */
    public function debug($status = true)
    {
        $this->debug = $status;
        return $this;
    }
    
    /**
     * Set Logs Directory full Path
     * @param string $path
     * @return $this
     */
    public function setLogDirPath($path)
    {
        $this->logsDirPath = $path;
        return $this;
    }

    public function getMe()
    {
        return $this->query("getMe");
    }

    /**
     * Send Message
     *
     * sendMessage("text message", 1234, [
     *      'reply_markup' => json_encode($reply_markup)
     *      'reply_to_message_id' => $reply_to_message_id,
     *      'disable_web_page_preview' => $disable_web_page_preview,
     * ])
     *
     * @param string $message
     * @param null|integer $chatId
     * @param array $options
     * @return mixed
     */
    public function sendMessage($message, $chatId = null, array $options = [])
    {
        if ($chatId === null) {
            $chatId = $this->defaultChatId;
        }
        $options = array_merge($this->tgDefaultOptions, $options, [
            'text' => $this->prepareText($message),
            'chat_id' => $chatId,
        ]);
        return $this->query('sendMessage', $options);
    }

    /**
     *   forwardMessage([
     *       'chat_id' => $chat_id,
     *       'from_chat_id' => $from_chat_id,
     *       'message_id' => $message_id,
     *   ]);
     * @param array $option
     * @return mixed
     */
    public function forwardMessage(array $option){
        return $this->query("forwardMessage", $option);
    }


    /**
     * sendPhoto('path/to/test.jpg', $chatId, [
     *      'caption' => $caption,
     *      'reply_to_message_id' => $reply_to_message_id,
     *      'reply_markup' => $reply_markup
     * ])
     * @param string $pathToFile
     * @param integer $chatId
     * @param array $options
     * @return mixed
     */
    public function sendPhoto($pathToFile, $chatId, array $options = [])
    {
        $options = array_merge($options, [
            'chat_id' => $chatId,
            'photo' => $pathToFile,
        ]);
        return $this->query('sendPhoto', $options);
    }

    /**
     *   sendAudio[
     *       'chat_id' => $chat_id,
     *       'audio' => 'path/to/test.ogg',//realpath
     *       'caption' => '',
     *       'duration' => 0,
     *       'reply_to_message_id' => $reply_to_message_id,
     *       'reply_markup' => $reply_markup
     *   ]);
     * @param array $option
     * @return mixed
     */
    public function sendAudio(array $option){
        return $this->query("sendAudio", $option);
    }

    /**
     *   sendDocument([
     *       'chat_id' => $chat_id,
     *       'document' => 'path/to/test.pdf',//realpath
     *       'caption' => '',
     *       'reply_to_message_id' => $reply_to_message_id,
     *       'reply_markup' => $reply_markup
     *   ]);
     * @param array $option
     * @return mixed
     */
    public function sendDocument(array $option){
        return $this->query("sendDocument", $option);
    }

    /**
     *   sendSticker([
     *       'chat_id' => $chat_id,
     *       'sticker' => 'path/to/test.webp',//realpath
     *       'reply_to_message_id' => $reply_to_message_id,
     *       'reply_markup' => $reply_markup
     *   ]);
     * @param array $option
     * @return mixed
     */
    public function sendSticker(array $option){
        return $this->query("sendSticker", $option);
    }

    /**
     *   sendVideo([
     *       'chat_id' => $chat_id,
     *       'video' => 'path/to/test.mp4',//realpath
     *       'duration' => 0,
     *       'caption' => $caption,
     *       'reply_to_message_id' => $reply_to_message_id,
     *       'reply_markup' => $reply_markup
     *   ]);
     * @param array $option
     * @return mixed
     */
    public function sendVideo(array $option){
        return $this->query("sendVideo", $option);
    }

    /**
     *   sendVideo([
     *       'chat_id' => $chat_id,
     *       'latitude' => 37.7576793,
     *       'longitude' => -122.5076402,
     *       'disable_notification' => true,//true||false,
     *       'reply_to_message_id' => $reply_to_message_id,
     *       'reply_markup' => $reply_markup
     *   ]);
     * @param array $option
     * @return mixed
     */
    public function sendLocation(array $option)
    {
        return $this->query("sendLocation", $option);
    }

    /**
     *   sendChatAction([
     *       'chat_id' => $chat_id,
     *       'action' => 'upload_photo',// upload_photo or  record_video  or  upload_video or record_audio or
     *       // upload_audio or upload_document or find_location
     *   ]);
     * @param array $option
     * @return mixed
     */
    public function sendChatAction(array $option){
        return $this->query("sendChatAction", $option);
    }

    /**
     *   getUserProfilePhotos([
     *       'user_id' => $chat_id,
     *       'offset' => $offset,//Sequential number of the first photo to be returned. By default, all photos are returned.
     *       'limit' => $limit, //Limits the number of photos to be retrieved. Values between 1—100 are accepted. Defaults to 100.
     *   ]);
     * @param array $option
     * @return mixed
     */
    public function getUserProfilePhotos(array $option){
        return $this->query("getUserProfilePhotos", $option);
    }

    /**
     *   getUpdates([
     *       'offset' => $offset,//Identifier of the first update to be returned. Must be greater by one than the highest among the *            //identifiers of previously received updates.
     *           //By default, updates starting with the earliest unconfirmed update are returned.
     *           //An update is considered confirmed as soon as getUpdates is called with an offset higher than its update_id.
     *           //The negative offset can be specified to retrieve updates starting from -offset
     *           //update from the end of the updates queue.
     *       'limit' => $limit,//Limits the number of updates to be retrieved. Values between 1—100 are accepted. Defaults to 100.
     *       'timeout' => $timeout,//Timeout in seconds for long polling. Defaults to 0, i.e. usual short polling
     *   ]);
     * @param array $option
     * @return mixed
     */
    public function getUpdates(array $option = [])
    {
        return $this->query("getUpdates", $option);
    }

    /**
     *   setWebhook([
     *       'url' => $url,
     *   ]);
     * @param array $option
     * @return mixed
     */
    public function setWebhook(array $option = []){
        return $this->query("setWebhook", $option);
    }

    /**
     *   getChat([
     *       'chat_id' => '3343545121',
     *   ]);
     * @param array $option
     * @return mixed
     */
    public function getChat(array $option = [])
    {
        return $this->query("getChat", $option);
    }

    /**
     *   getChatAdministrators([
     *       'chat_id' => '3343545121',
     *   ]);
     *   Use this method to get a list of administrators in a chat.
     * @param array $option
     * @return mixed
     */
    public function getChatAdministrators(array $option = [])
    {
        return $this->query("getChatAdministrators", $option);
    }

    /**
     *   getChatMembersCount([
     *       'chat_id' => '3343545121',
     *   ]);
     *   Use this method to get the number of members in a chat. Returns Int on success.
     * @param array $option
     * @return mixed
     */
    public function getChatMembersCount(array $option = [])
    {
        return $this->query("getChatMembersCount", $option);
    }

    /**
     *   getChatMember([
     *       'chat_id' => '3343545121', //Unique identifier for the target chat or
     *            //username of the target supergroup or channel (in  the format @channelusername)
     *       'user_id' => 243243,//Unique identifier of the target user
     *   ]);
     *   Use this method to get information about a member of a chat.
     * @param array $option
     * @return mixed
     */
    public function getChatMember(array $option = [])
    {
        return $this->query("getChatMember", $option);
    }

    /**
     *   answerCallbackQuery([
     *       'callback_query_id' => '3343545121', //require
     *       'text' => 'text', //Optional
     *       'show_alert' => 'my alert',  //Optional
     *       'url' => 'http://sample.com', //Optional
     *       'cache_time' => 123231,  //Optional
     *   ]);
     *   Use this method to send answers to callback queries sent from inline keyboards.
     *   The answer will be displayed to the user as a notification at the top of the chat screen or as an alert.
     *  On success, True is returned.
     * @param array $option
     * @return mixed
     */
    public function answerCallbackQuery(array $option = [])
    {
        return $this->query("answerCallbackQuery", $option);
    }

    /**
     *   editMessageText([
     *       'chat_id' => '3343545121', //Optional
     *       'message_id' => 13123, //Optional
     *       'inline_message_id' => 'my alert',  //Optional
     *       'text' => 'my text', //require
     *       'parse_mode' => 123231,  //Optional
     *       'disable_web_page_preview' => false or true,  //Optional
     *       'reply_markup' => Type InlineKeyboardMarkup,  //Optional
     *   ]);
     *   Use this method to edit text and game messages sent by the bot or via the bot (for inline bots). On success,
     *  if edited message is sent by the bot, the edited Message is returned, otherwise True is returned.
     * @param array $option
     * @return mixed
     */
    public function editMessageText(array $option = [])
    {
        return $this->query("editMessageText", $option);
    }

    /**
     *   editMessageText([
     *       'chat_id' => '3343545121', //Required
     *       'message_id' => 13123, //Optional
     *       'inline_message_id' => 'my alert',  //Optional
     *       'caption' => 'my text', //require
     *       'reply_markup' => Type InlineKeyboardMarkup,  //Optional
     *   ]);
     *
     *   Use this method to edit captions of messages sent by the bot or via the bot (for inline bots). On success,
     *    if edited message is sent by the bot, the edited Message is returned, otherwise True is returned.
     * @param array $option
     * @return mixed
     */
    public function editMessageCaption(array $option = [])
    {
        return $this->query("editMessageCaption", $option);
    }

    /**
     *   sendGame([
     *       'chat_id' => '3343545121', //Required
     *       'game_short_name' => 'myGame', //Required
     *       'disable_notification' => true,  //true or false Optional
     *       'reply_to_message_id' => 123121, //Optional
     *       'reply_markup' => Type InlineKeyboardMarkup,  //Optional
     *   ]);
     *
     *   Use this method to send a game. On success, the sent Message is returned.
     * @param array $option
     * @return mixed
     */
    public function sendGame(array $option = [])
    {
        return $this->query("sendGame", $option);
    }

    /**
     *   Game([
     *       'title' => 'Title of the game', //Required
     *       'description' => 'String', //Required
     *       'photo' => Array of PhotoSize,  //Photo that will be displayed in the game message in chats
     *       'text' => 'String', //Optional
     *       'text_entities' => Array of MessageEntity,  //Optional
     *        'animation' => instance of Animation, //Optional
     *   ]);
     *
     *   Use this method to send a game. On success, the sent Message is returned.
     * @param array $option
     * @return mixed
     */
    public function Game(array $option = [])
    {
        return $this->query("Game", $option);
    }

    /**
     *   Animation([
     *       'file_id' => String, //Required
     *       'thumb' => PhotoSize, //Optional
     *       'file_name' => String,  //Optional
     *       'mime_type' => String, //Optional
     *   ]);
     *
     *   You can provide an animation for your game so that it looks stylish
     *    in chats (check out Lumberjack for an example). This object represents an animation file to
     *    be displayed in the message containing a game.
     * @param array $option
     * @return mixed
     */
    public function Animation(array $option = [])
    {
        return $this->query("Animation", $option);
    }

    /**
     *   CallbackGame([
     *       'user_id' => Integer, //Required
     *       'score' => Integer, //Required
     *       'force' => Boolean,  //Optional
     *       'disable_edit_message' => Boolean, //Optional
     *       'chat_id' => Integer,  //Integer
     *       'message_id' => Integer,  //Optional
     *       'inline_message_id' => String,  //Optional
     *   ]);
     *
     *   Use this method to set the score of the specified user in a game. On success,
     *    if the message was sent by the bot, returns the edited Message, otherwise returns True.
     *    Returns an error, if the new score is not greater than the user's current score in the chat and force is False.
     * @param array $option
     * @return mixed
     */
    public function CallbackGame(array $option = [])
    {
        return $this->query("CallbackGame", $option);
    }

    /**
     *   getGameHighScores([
     *       'user_id' => Integer, //Required
     *       'chat_id' => Integer, //Optional
     *       'message_id' => Integer,  //Optional
     *       'inline_message_id' => String, //Optional
     *   ]);
     *
     *   Use this method to get data for high score tables.
     *    Will return the score of the specified user and several of his neighbors in a game.
     *    On success, returns an Array of GameHighScore objects.
     * @param array $option
     * @return mixed
     */
    public function getGameHighScores(array $option = [])
    {
        return $this->query("getGameHighScores", $option);
    }

    /**
     *   GameHighScore([
     *       'position' => Integer, //Required-Position in high score table for the game
     *       'user' => User, //Optional
     *       'score' => Integer,  //Optional
     *   ]);
     *
     *   This object represents one row of the high scores table for a game.
     * @param array $option
     * @return mixed
     */
    public function GameHighScore(array $option = [])
    {
        return $this->query("GameHighScore", $option);
    }

    //----------------------begin inline method--------------------------//

    /**
     *   answerInlineQuery([
     *       'inline_query_id' => Integer, //Required-Position in high score table for the game
     *       'user' => User, //Optional
     *       'score' => Integer,  //Optional
     *   ]);
     *
     *   This object represents one row of the high scores table for a game.
     * @param array $option
     * @return mixed
     */
    public function answerInlineQuery(array $option = [])
    {
        return $this->query("answerInlineQuery", $option);
    }


    /**
     *   kickChatMember([
     *       'chat_id' => Integer, //Unique identifier for the target group or username of the target supergroup or channel
     *       'user_id' => Integer, //Unique identifier of the target user
     *       'until_date' => Integer,  //Date when the user will be unbanned, unix time.
     *                                 //If user is banned for more than 366 days or less than 30 seconds from the
     *                                //current time they are considered to be banned forever
     *   ]);
     *
     *   This object represents one row of the high scores table for a game.
     * @param array $option
     * @return mixed
     */
    public function kickChatMember(array $option = [])
    {
        return $this->query("kickChatMember", $option);
    }


    /**
     *   restrictChatMember([
     *       'chat_id' => Integer, //Unique identifier for the target group or username of the target supergroup or *                              //channel
     *       'user_id' => Integer, //Unique identifier of the target user
     *       'until_date' => Integer,  //Date when the user will be unbanned, unix time.
     *                                 //If user is banned for more than 366 days or less than 30 seconds from the
     *                                //current time they are considered to be banned forever
     *       'can_send_messages' => Boolean    //Pass True, if the user can send text messages,
     *                                        //contacts, locations and venues
     *   ]);
     *
     *   Use this method to restrict a user in a supergroup. The bot must be an administrator in the supergroup for *    this to work and must have the appropriate admin rights. Pass True for all boolean parameters to lift *        restrictions from a user. Returns True on success.
     * @param array $option
     * @return mixed
     */
    public function restrictChatMember(array $option = [])
    {
        return $this->query("restrictChatMember", $option);
    }


    public function promoteChatMember(array $option = [])
    {
        return $this->query("promoteChatMember", $option);
    }

    public function exportChatInviteLink(array $option = [])
    {
        return $this->query("exportChatInviteLink", $option);
    }

    public function deleteChatPhoto(array $option = [])
    {
        return $this->query("deleteChatPhoto", $option);
    }

    public function setChatTitle(array $option = [])
    {
        return $this->query("setChatTitle", $option);
    }

    public function setChatDescription(array $option = [])
    {
        return $this->query("setChatDescription", $option);
    }

    public function unpinChatMessage(array $option = [])
    {
        return $this->query("unpinChatMessage", $option);
    }



    public function pinChatMessage(array $option = [])
    {
        return $this->query("pinChatMessage", $option);
    }

    public function leaveChat(array $option = [])
    {
        return $this->query("leaveChat", $option);
    }

    public function setChatStickerSet(array $option = [])
    {
        return $this->query("setChatStickerSet", $option);
    }
    public function deleteChatStickerSet(array $option = [])
    {
        return $this->query("deleteChatStickerSet", $option);
    }



    public function hook()
    {
        $json = file_get_contents('php://input');
        return json_decode($json);
    }

    /**
     * getFile([
     *        'file_id' => $file_id
     *    ]);
     * @param $option
     * @return mixed
     */
    public function getFile($option) {
        return $this->query("getFile", $option);
    }

    /**
     * Make Query
     * @param $path
     * @param array $option
     * @return mixed
     */
    private function query($path, array $option = []){
        $attachments = ['photo', 'sticker', 'audio', 'document', 'video'];

        $ch = curl_init($this->genUrl($path));

        if ($this->curlProxy !== null) {
            curl_setopt($ch, CURLOPT_PROXY, $this->curlProxy);
        }

        if ($this->curlOptions !== null) {
            curl_setopt_array($ch, $this->curlOptions);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->curlUserAgent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        if ($this->async) {
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        }
        
        if (count($option) > 0) {
            curl_setopt($ch, CURLOPT_POST, true);

            foreach($attachments as $attachment){
                if(isset($option[$attachment])){
                    $option[$attachment] = $this->curlFile($option[$attachment]);
                    break;
                }
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $option);
        }
        $r = curl_exec($ch);
        if($r == false){
            $text = '[CURL eroror]: ' . curl_error($ch);
        } else {
            $text = '[TRACE]:' . $r;
        }
        $this->log($text);
        
        curl_close($ch);
        return json_decode($r);
    }
    
    /**
     * Write a log
     * @param mixed $text
     */
    private function log($text)
    {
        if (false === $this->debug || !is_dir($this->logsDirPath)) {
            return;
        }
        $myfile = fopen($this->logsDirPath . "/telegram.log", "a") or die("Unable to open file!");
        fwrite($myfile, $text);
        fclose($myfile);
    }

    private function curlFile($path){
        if (is_array($path))
            return $path['file_id'];

        $realPath = realpath($path);

        if (class_exists('CURLFile'))
            return new \CURLFile($realPath);

        return '@' . $realPath;
    }

    /**
     * Prepare message to send
     *  - strips not used tags
     *  - urlencodes
     * @param string $txt
     * @return string
     */
    private function prepareText($txt)
    {
        return (strip_tags($txt, implode('', $this->stripTags)));
    }

    /**
     * Generate full url to make query
     * @param string $path
     * @return string
     */
    private function genUrl($path)
    {
        return $this->url . '/bot' . $this->botToken . '/' . $path;
    }
}
